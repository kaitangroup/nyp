<?php
/**
 * Packs feature — curated bundles of library snippets installed as a group.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for the Packs feature.
 *
 * All install / toggle / remove logic for packs lives here. WordPress hooks
 * are registered in the admin page class and the AJAX handlers — this class
 * is pure logic that can be exercised from PHPUnit.
 */
class WPCode_Packs {

	/**
	 * Meta key stamped on every snippet created by a pack. Stores the
	 * pack's category slug.
	 */
	const META_PACK_SLUG = '_wpcode_pack_slug';

	/**
	 * Meta key stamped on every snippet created by a pack. Stores the
	 * source library snippet ID — used to detect conflicts when a snippet
	 * is already installed by another pack.
	 */
	const META_OPTION_KEY = '_wpcode_pack_option_key';

	/**
	 * WPCode's canonical "this snippet was imported from library ID N" meta.
	 * Stamped by WPCode_Library::create_new_snippet() on every import, so it is
	 * the source of truth for conflict detection — see
	 * find_local_snippet_by_library_id().
	 */
	const META_LIBRARY_ID = '_wpcode_library_id';

	/**
	 * Option name for the packs state — an array keyed by pack slug
	 * with `installed_at` timestamps. Per-option enabled state is *not*
	 * stored here; it is derived from each snippet's `active` flag.
	 */
	const STATE_OPTION = 'wpcode_packs_state';

	/**
	 * Singleton instance.
	 *
	 * @var WPCode_Packs
	 */
	private static $instance;

	/**
	 * Per-request memo for get_packs() output.
	 *
	 * @var array|null
	 */
	private $packs_cache;

	/**
	 * Get the singleton instance.
	 *
	 * @return WPCode_Packs
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get every pack the library currently exposes.
	 *
	 * A pack is a `wpcode_pack` term on library.wpcode.com. The library
	 * writes packs to its static JSON under a dedicated `packs` key, each
	 * carrying the library ids of its member snippets. The plugin's library
	 * cache already pulls that file, so we resolve each pack's members against
	 * the cached snippets list.
	 *
	 * @return array
	 */
	public function get_packs() {
		if ( isset( $this->packs_cache ) ) {
			return $this->packs_cache;
		}

		$library = wpcode()->library->get_data();
		if ( empty( $library['packs'] ) || ! is_array( $library['packs'] ) ) {
			$this->packs_cache = array();
			return $this->packs_cache;
		}

		$state = $this->get_installed_state();

		// Map every library snippet by its id so pack members resolve in O(1).
		$snippet_map = array();
		if ( ! empty( $library['snippets'] ) && is_array( $library['snippets'] ) ) {
			foreach ( $library['snippets'] as $snippet ) {
				if ( isset( $snippet['library_id'] ) ) {
					$snippet_map[ (int) $snippet['library_id'] ] = $snippet;
				}
			}
		}

		$packs = array();
		foreach ( $library['packs'] as $pack ) {
			$slug         = isset( $pack['slug'] ) ? $pack['slug'] : '';
			$installed_at = isset( $state[ $slug ]['installed_at'] ) ? (int) $state[ $slug ]['installed_at'] : 0;
			$members      = isset( $pack['members'] ) && is_array( $pack['members'] ) ? $pack['members'] : array();

			$packs[] = array(
				'slug'         => $slug,
				'name'         => isset( $pack['name'] ) ? $pack['name'] : '',
				'description'  => isset( $pack['description'] ) ? $pack['description'] : '',
				'icon'         => isset( $pack['pack_icon'] ) ? $pack['pack_icon'] : '',
				'group'        => isset( $pack['pack_group'] ) ? $pack['pack_group'] : '',
				'requirements' => isset( $pack['pack_requirements'] ) ? (array) $pack['pack_requirements'] : array(),
				'snippets'     => $this->resolve_members( $members, $snippet_map ),
				'installed'    => $installed_at > 0,
				'installed_at' => $installed_at,
			);
		}

		$this->packs_cache = $packs;
		return $this->packs_cache;
	}

	/**
	 * Clear the per-request packs memo. Called from lifecycle methods that
	 * mutate state (install / add / remove) so subsequent reads see fresh data.
	 *
	 * @return void
	 */
	protected function flush_packs_cache() {
		$this->packs_cache = null;
	}

	/**
	 * Resolve a pack's member library ids against the cached snippets map into
	 * the slim shape used for rendering. Members missing from the library list
	 * (e.g. deleted snippets) are skipped.
	 *
	 * @param int[] $members     Member library snippet ids.
	 * @param array $snippet_map Map of library_id => snippet data.
	 *
	 * @return array
	 */
	private function resolve_members( $members, $snippet_map ) {
		$out = array();
		foreach ( $members as $library_id ) {
			$library_id = (int) $library_id;
			if ( $library_id <= 0 || ! isset( $snippet_map[ $library_id ] ) ) {
				continue;
			}
			$snippet = $snippet_map[ $library_id ];
			$out[]   = array(
				'library_id'            => $library_id,
				'title'                 => isset( $snippet['title'] ) ? $snippet['title'] : '',
				'note'                  => isset( $snippet['note'] ) ? $snippet['note'] : '',
				'code_type'             => isset( $snippet['code_type'] ) ? $snippet['code_type'] : '',
				'needs_personalization' => ! empty( $snippet['pack_option_needs_personalization'] ),
			);
		}
		return $out;
	}

	/**
	 * Get the packs state option.
	 *
	 * @return array
	 */
	public function get_installed_state() {
		$state = get_option( self::STATE_OPTION, array() );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * Find a pack by slug from the get_packs() output.
	 *
	 * @param string $slug Pack slug.
	 *
	 * @return array|null
	 */
	public function find_pack( $slug ) {
		foreach ( $this->get_packs() as $pack ) {
			if ( $pack['slug'] === $slug ) {
				return $pack;
			}
		}
		return null;
	}

	/**
	 * Whether the given pack lists the given library snippet as one of its
	 * options. Used to authorize add/toggle requests against a pack's own
	 * snippet list.
	 *
	 * @param string $slug       Pack slug.
	 * @param int    $library_id Source library snippet ID.
	 *
	 * @return bool
	 */
	public function pack_includes_option( $slug, $library_id ) {
		$pack = $this->find_pack( $slug );
		if ( empty( $pack ) ) {
			return false;
		}
		foreach ( $pack['snippets'] as $option ) {
			if ( (int) $option['library_id'] === (int) $library_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether the site is connected to the WPCode library. Installing a pack
	 * imports snippets from the library, so a connection is required — same as
	 * importing any library snippet.
	 *
	 * @return bool
	 */
	public function is_library_connected() {
		return wpcode()->library_auth->has_auth();
	}

	/**
	 * Find a local snippet by its source library ID.
	 *
	 * Looks up WPCode's canonical `_wpcode_library_id` meta — stamped on every
	 * snippet imported from the library, whether by a pack or from the
	 * standard Library tab. This means conflict detection catches a snippet the
	 * user already imported manually, not just pack-installed ones.
	 *
	 * @param int $library_id Source library snippet ID.
	 *
	 * @return WP_Post|null
	 */
	public function find_local_snippet_by_library_id( $library_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'wpcode',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => self::META_LIBRARY_ID,
						'value' => (int) $library_id,
					),
				),
			)
		);
		return $query->have_posts() ? $query->posts[0] : null;
	}

	/**
	 * Batch variant of find_local_snippet_by_library_id() — resolves a whole
	 * list of library IDs in a single query so the detail view doesn't fire one
	 * query per option.
	 *
	 * @param int[] $library_ids Source library snippet IDs.
	 *
	 * @return array Map of library_id => WP_Post for the ones that are installed.
	 */
	public function find_local_snippets_by_library_ids( $library_ids ) {
		$library_ids = array_values( array_unique( array_map( 'intval', (array) $library_ids ) ) );
		if ( empty( $library_ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'wpcode',
				'post_status'    => 'any',
				'posts_per_page' => count( $library_ids ),
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => self::META_LIBRARY_ID,
						'value'   => $library_ids,
						'compare' => 'IN',
					),
				),
			)
		);

		$map = array();
		foreach ( $query->posts as $post ) {
			$lib_id = (int) get_post_meta( $post->ID, self::META_LIBRARY_ID, true );
			if ( $lib_id && ! isset( $map[ $lib_id ] ) ) {
				$map[ $lib_id ] = $post;
			}
		}
		return $map;
	}


	/**
	 * Install every option of a pack.
	 *
	 * @param string $slug Pack slug.
	 *
	 * @return array
	 */
	public function install_pack( $slug ) {
		$empty = array(
			'success'           => false,
			'created_ids'       => array(),
			'skipped_options'   => array(),
			'failed_options'    => array(),
			'activation_failed' => array(),
		);

		if ( ! current_user_can( 'wpcode_edit_snippets' ) || ! $this->is_library_connected() ) {
			return $empty;
		}

		$pack = $this->find_pack( $slug );
		if ( empty( $pack ) || empty( $pack['snippets'] ) ) {
			return $empty;
		}

		$created           = array();
		$skipped           = array();
		$failed            = array();
		$activation_failed = array();

		// Resolve all already-installed snippets in one query instead of one per
		// option (avoids an N+1 inside the install loop).
		$existing = $this->find_local_snippets_by_library_ids( wp_list_pluck( $pack['snippets'], 'library_id' ) );

		// Split the options into already-present (skip) and to-be-created.
		$to_create = array();
		foreach ( $pack['snippets'] as $snippet ) {
			$library_id = (int) $snippet['library_id'];
			if ( $library_id <= 0 ) {
				continue;
			}
			if ( isset( $existing[ $library_id ] ) ) {
				$skipped[] = $library_id;
				continue;
			}
			$to_create[] = $library_id;
		}

		// Fetch and create every missing snippet in a single request to the
		// library, rather than one HTTP call per option.
		$new_snippets = empty( $to_create ) ? array() : wpcode()->library->create_new_snippets_batch( $to_create );

		foreach ( $to_create as $library_id ) {
			if ( empty( $new_snippets[ $library_id ] ) ) {
				$failed[] = $library_id;
				continue;
			}

			$local = $new_snippets[ $library_id ];

			update_post_meta( $local->get_id(), self::META_PACK_SLUG, $slug );
			update_post_meta( $local->get_id(), self::META_OPTION_KEY, $library_id );

			// Activate through the snippet's own activate() so run_activation_checks()
			// runs the code first. If the snippet errors, activate() leaves it as a
			// draft rather than force-publishing broken code.
			$local->activate();

			$created[] = $local->get_id();
			if ( ! $local->is_active() ) {
				$activation_failed[] = $library_id;
			}
		}

		// Count how many of the pack's options are actually active now: newly
		// created ones that activated, plus already-present (skipped) ones that
		// happen to be active. Reported so the UI doesn't overstate the result.
		$active_count = count( $created ) - count( $activation_failed );
		foreach ( $skipped as $skipped_id ) {
			if ( isset( $existing[ $skipped_id ] ) && 'publish' === $existing[ $skipped_id ]->post_status ) {
				++$active_count;
			}
		}

		// Success = at least one option ended up installed (newly created OR
		// detected as already present). Failure only when nothing landed.
		$any_present = ! empty( $created ) || ! empty( $skipped );

		// Whenever any option ended up present (new OR pre-existing), make sure
		// the state has an install timestamp so the UI shows the pack as
		// installed.
		if ( $any_present ) {
			$state = $this->get_installed_state();
			if ( empty( $state[ $slug ]['installed_at'] ) ) {
				$state[ $slug ] = array( 'installed_at' => time() );
				update_option( self::STATE_OPTION, $state );
				$this->flush_packs_cache();
			}
		}

		return array(
			'success'           => $any_present,
			'created_ids'       => $created,
			'skipped_options'   => $skipped,
			'failed_options'    => $failed,
			'activation_failed' => $activation_failed,
			'active_count'      => max( 0, $active_count ),
		);
	}

	/**
	 * Install a single option of a pack.
	 *
	 * @param string $slug       Pack slug.
	 * @param int    $library_id Source library snippet ID.
	 *
	 * @return WPCode_Snippet|false
	 */
	public function add_option( $slug, $library_id ) {
		if ( ! current_user_can( 'wpcode_edit_snippets' ) || ! $this->is_library_connected() ) {
			return false;
		}

		if ( ! $this->pack_includes_option( $slug, $library_id ) ) {
			return false;
		}

		if ( $this->find_local_snippet_by_library_id( $library_id ) ) {
			return false;
		}

		$local = wpcode()->library->create_new_snippet( (int) $library_id );
		if ( ! $local ) {
			return false;
		}

		update_post_meta( $local->get_id(), self::META_PACK_SLUG, $slug );
		update_post_meta( $local->get_id(), self::META_OPTION_KEY, (int) $library_id );

		// Activate through activate() so run_activation_checks() runs — a snippet
		// whose code errors out stays a draft instead of being force-published.
		$local->activate();

		$state = $this->get_installed_state();
		if ( empty( $state[ $slug ]['installed_at'] ) ) {
			$state[ $slug ] = array( 'installed_at' => time() );
			update_option( self::STATE_OPTION, $state );
			$this->flush_packs_cache();
		}

		return $local;
	}

	/**
	 * Toggle the active state of a single pack-installed snippet.
	 *
	 * The snippet only needs to be one of the pack's options — not
	 * necessarily installed *by* this pack. A snippet shared between packs
	 * (e.g. "Disable XML-RPC") is a single underlying snippet, so it can be
	 * toggled from any pack that includes it.
	 *
	 * @param string $slug       Pack slug.
	 * @param int    $library_id Source library snippet ID.
	 * @param bool   $active     Whether to activate (true) or deactivate (false).
	 *
	 * @return bool|null The snippet's real active state after the change, or null
	 *                   if the request could not be acted on (no permission, not
	 *                   part of the pack, or snippet not installed).
	 */
	public function toggle_option( $slug, $library_id, $active ) {
		if ( ! current_user_can( 'wpcode_activate_snippets' ) ) {
			return null;
		}

		if ( ! $this->pack_includes_option( $slug, $library_id ) ) {
			return null;
		}

		$post = $this->find_local_snippet_by_library_id( $library_id );
		if ( ! $post ) {
			return null;
		}

		$snippet = new WPCode_Snippet( $post );
		if ( $active ) {
			$snippet->activate();
		} else {
			$snippet->deactivate();
		}

		// activate() can refuse (code errors, or no capability), so report the
		// real resulting state rather than the requested one.
		return $snippet->is_active();
	}

	/**
	 * Map of library IDs that other *installed* packs also include, to the
	 * slug of one such pack. Used by remove_pack() to keep a shared snippet
	 * alive (and managed) when the pack being removed is dropped.
	 *
	 * @param string $exclude_slug Pack being removed — skipped in the scan.
	 *
	 * @return array Map of library_id => owning pack slug.
	 */
	private function library_ids_claimed_by_other_packs( $exclude_slug ) {
		$claimed = array();
		$state   = $this->get_installed_state();

		foreach ( $this->get_packs() as $pack ) {
			if ( $pack['slug'] === $exclude_slug ) {
				continue;
			}
			// Only packs the user actually has installed can lay claim.
			if ( empty( $state[ $pack['slug'] ]['installed_at'] ) ) {
				continue;
			}
			foreach ( $pack['snippets'] as $option ) {
				$lib_id = (int) $option['library_id'];
				if ( $lib_id > 0 && ! isset( $claimed[ $lib_id ] ) ) {
					$claimed[ $lib_id ] = $pack['slug'];
				}
			}
		}

		return $claimed;
	}

	/**
	 * Remove a pack entirely.
	 *
	 * Snippets created by this pack are deleted — except any that another
	 * still-installed pack also includes. Those are kept and their ownership
	 * is transferred to that other pack, so removing one pack never breaks
	 * a shared snippet another pack still relies on.
	 *
	 * @param string $slug Pack slug.
	 *
	 * @return int Number of snippets deleted.
	 */
	public function remove_pack( $slug ) {
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {
			return 0;
		}

		$claimed_by_others = $this->library_ids_claimed_by_other_packs( $slug );

		$query = new WP_Query(
			array(
				'post_type'      => 'wpcode',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => self::META_PACK_SLUG,
						'value' => $slug,
					),
				),
				'fields'         => 'ids',
			)
		);

		$deleted = 0;
		foreach ( $query->posts as $post_id ) {
			$library_id = (int) get_post_meta( $post_id, self::META_LIBRARY_ID, true );

			// Shared with another installed pack: keep the snippet and hand
			// ownership to that pack so it stays manageable/removable later.
			if ( $library_id > 0 && isset( $claimed_by_others[ $library_id ] ) ) {
				update_post_meta( $post_id, self::META_PACK_SLUG, $claimed_by_others[ $library_id ] );
				continue;
			}

			if ( wp_delete_post( $post_id, true ) ) {
				++$deleted;
			}
		}

		$state = $this->get_installed_state();
		if ( isset( $state[ $slug ] ) ) {
			unset( $state[ $slug ] );
			update_option( self::STATE_OPTION, $state );
			$this->flush_packs_cache();
		}

		return $deleted;
	}
}
