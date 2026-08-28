<?php
/**
 * Renderer for the Packs tab inside the Snippet Manager admin page.
 *
 * Pure rendering — no menu / page registration. Instantiate and call
 * render_tab_content() to emit the markup for the Packs tab (browse + detail
 * + install modal template).
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Packs_View.
 */
class WPCode_Packs_View {

	/**
	 * The active pack slug (from ?pack= query param), if any.
	 *
	 * @var string
	 */
	protected $current_pack_slug = '';

	/**
	 * Fallback dashicon used when a pack has no `pack_icon` set on the
	 * library. Not a per-pack mapping — just a neutral default so the card
	 * never renders without an icon.
	 */
	const DEFAULT_ICON = 'dashicons-archive';

	/**
	 * Constructor — picks up the pack slug from the query string.
	 */
	public function __construct() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['pack'] ) ) {
			$this->current_pack_slug = sanitize_key( wp_unslash( $_GET['pack'] ) );
		}
		// phpcs:enable
	}

	/**
	 * Emit the content for the Packs tab — browse + detail + modal template.
	 *
	 * @return void
	 */
	public function render_tab_content() {
		if ( '' !== $this->current_pack_slug ) {
			$this->render_detail();
		} else {
			$this->render_browse();
		}
		$this->render_install_modal_template();
	}

	/**
	 * Whether the Packs tab is the active one based on the request.
	 *
	 * @return bool
	 */
	public function is_active_tab() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( '' !== $this->current_pack_slug ) {
			return true;
		}
		if ( isset( $_GET['tab'] ) && 'packs' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
			return true;
		}
		// phpcs:enable
		return false;
	}

	/**
	 * Browse view — uses the stock wpcode-items-metabox pattern so the
	 * sidebar + search + category filter are wired by the existing
	 * items-list.js (no custom JS needed for those).
	 *
	 * @return void
	 */
	protected function render_browse() {
		$packs = WPCode_Packs::get_instance()->get_packs();
		$counts  = array();
		foreach ( $packs as $r ) {
			$cat_slug = $this->group_slug( $r );
			if ( ! isset( $counts[ $cat_slug ] ) ) {
				$counts[ $cat_slug ] = array(
					'count' => 0,
					'name'  => $this->group_name( $r ),
				);
			}
			$counts[ $cat_slug ]['count']++;
		}
		$installed_count = 0;
		foreach ( $packs as $r ) {
			if ( $r['installed'] ) {
				$installed_count++;
			}
		}
		?>
		<div class="wpcode-add-snippet-description">
			<?php esc_html_e( 'Install a curated bundle of snippets that solves a single outcome. After install, manage each snippet individually or remove the whole pack.', 'insert-headers-and-footers' ); ?>
		</div>
		<div class="wpcode-items-metabox wpcode-metabox">
			<div class="wpcode-items-sidebar">
				<div class="wpcode-items-search">
					<label for="wpcode-items-search">
						<span class="screen-reader-text"><?php esc_html_e( 'Search Packs', 'insert-headers-and-footers' ); ?></span>
						<?php wpcode_icon( 'search', 16, 16 ); ?>
					</label>
					<input type="search" class="wpcode-items-search-input" placeholder="<?php esc_attr_e( 'Search Packs', 'insert-headers-and-footers' ); ?>" />
				</div>
				<ul class="wpcode-items-categories-list wpcode-items-filters">
					<li>
						<button type="button" data-category="*" class="wpcode-active">
							<?php esc_html_e( 'All Packs', 'insert-headers-and-footers' ); ?>
							<span class="wpcode-items-count"><?php echo (int) count( $packs ); ?></span>
						</button>
					</li>
					<?php if ( $installed_count > 0 ) : ?>
						<li>
							<button type="button" data-category="installed">
								<?php esc_html_e( 'Installed', 'insert-headers-and-footers' ); ?>
								<span class="wpcode-items-count"><?php echo (int) $installed_count; ?></span>
							</button>
						</li>
					<?php endif; ?>
					<?php foreach ( $counts as $cat_slug => $info ) : ?>
						<li>
							<button type="button" data-category="<?php echo esc_attr( $cat_slug ); ?>">
								<?php echo esc_html( $info['name'] ); ?>
								<span class="wpcode-items-count"><?php echo (int) $info['count']; ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="wpcode-items-list">
				<?php if ( empty( $packs ) ) : ?>
					<p style="padding: 40px 20px; text-align: center; color: var(--wpcode-text-color-paragraph);">
						<?php esc_html_e( 'No packs available yet. Connect to the library to populate packs.', 'insert-headers-and-footers' ); ?>
					</p>
				<?php else : ?>
					<ul class="wpcode-items-list-category">
						<?php foreach ( $packs as $pack ) {
							$this->render_pack_card( $pack );
						} ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single pack card using the stock wpcode-list-item pattern.
	 *
	 * @param array $pack Pack data.
	 *
	 * @return void
	 */
	protected function render_pack_card( $pack ) {
		$category_slug = $this->group_slug( $pack );
		$group_name    = $this->group_name( $pack );
		$icon          = $this->pack_icon( $pack );
		$detail_url    = add_query_arg(
			array(
				'page'   => 'wpcode-snippet-manager',
				'tab'    => 'packs',
				'pack' => $pack['slug'],
			),
			admin_url( 'admin.php' )
		);

		$categories = array( $category_slug );
		if ( $pack['installed'] ) {
			$categories[] = 'installed';
		}

		$snippet_count = count( $pack['snippets'] );
		$description   = ! empty( $pack['description'] )
			? $pack['description']
			: sprintf(
				/* translators: %d snippet count. */
				_n( '%d snippet bundled into this pack.', '%d snippets bundled into this pack.', $snippet_count, 'insert-headers-and-footers' ),
				$snippet_count
			);

		$max_visible_chips = 2;
		$visible_chips     = array_slice( $pack['snippets'], 0, $max_visible_chips );
		$remaining_chips   = $snippet_count - count( $visible_chips );
		?>
		<li class="wpcode-list-item wpcode-pack-list-item"
			data-id="<?php echo esc_attr( $pack['slug'] ); ?>"
			data-categories='<?php echo wp_json_encode( $categories ); ?>'
			data-pack-slug="<?php echo esc_attr( $pack['slug'] ); ?>"
			data-installed="<?php echo $pack['installed'] ? '1' : '0'; ?>">
			<div class="wpcode-pack-head">
				<div class="wpcode-pack-icon">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
				</div>
				<div class="wpcode-pack-head-text">
					<h3 title="<?php echo esc_attr( $pack['name'] ); ?>"><?php echo esc_html( $pack['name'] ); ?></h3>
					<span class="cat-badge"><?php echo esc_html( $group_name ); ?></span>
				</div>
			</div>
			<p class="wpcode-pack-desc"><?php echo esc_html( $description ); ?></p>
			<div class="wpcode-pack-foot">
				<div class="wpcode-pack-snippets">
					<?php foreach ( $visible_chips as $snippet ) : ?>
						<span class="wpcode-pack-snippet-chip" title="<?php echo esc_attr( $snippet['title'] ); ?>"><?php echo esc_html( $snippet['title'] ); ?></span>
					<?php endforeach; ?>
					<?php if ( $remaining_chips > 0 ) : ?>
						<span class="wpcode-pack-snippet-chip wpcode-pack-snippet-chip-more">
							<?php
							printf(
								/* translators: %d more snippets count. */
								esc_html__( '+%d more', 'insert-headers-and-footers' ),
								(int) $remaining_chips
							);
							?>
						</span>
					<?php endif; ?>
				</div>
				<div class="wpcode-pack-actions">
					<?php if ( $pack['installed'] ) : ?>
						<a href="<?php echo esc_url( $detail_url ); ?>" class="wpcode-button wpcode-button-secondary"><?php esc_html_e( 'Manage', 'insert-headers-and-footers' ); ?></a>
					<?php elseif ( 0 === $snippet_count ) : ?>
						<button type="button" class="wpcode-button wpcode-button-secondary" disabled><?php esc_html_e( 'Coming soon', 'insert-headers-and-footers' ); ?></button>
					<?php elseif ( ! WPCode_Packs::get_instance()->is_library_connected() ) : ?>
						<button type="button" class="wpcode-button wpcode-start-auth"><?php esc_html_e( 'Connect to Library', 'insert-headers-and-footers' ); ?></button>
					<?php else : ?>
						<button type="button" class="wpcode-button js-wpcode-open-install"><?php esc_html_e( 'Install', 'insert-headers-and-footers' ); ?></button>
					<?php endif; ?>
				</div>
			</div>
		</li>
		<?php
	}

	/**
	 * Render the detail view.
	 *
	 * @return void
	 */
	protected function render_detail() {
		$pack = WPCode_Packs::get_instance()->find_pack( $this->current_pack_slug );
		if ( null === $pack ) {
			echo '<p>' . esc_html__( 'Pack not found.', 'insert-headers-and-footers' ) . '</p>';
			return;
		}

		$group_name = $this->group_name( $pack );
		$icon       = $this->pack_icon( $pack );

		$packs_helper = WPCode_Packs::get_instance();

		// Resolve every option's local snippet in a single query instead of one
		// query per row.
		$library_ids = wp_list_pluck( $pack['snippets'], 'library_id' );
		$installed   = $packs_helper->find_local_snippets_by_library_ids( $library_ids );

		// Map every library snippet to the packs that include it, so we can tell
		// the user when an option is shared across packs. The same underlying
		// snippet is shared — so the note appears on *every* pack that lists it,
		// not just the ones installed after the first.
		$shared_pack_names = array();
		foreach ( $packs_helper->get_packs() as $other_pack ) {
			if ( $other_pack['slug'] === $pack['slug'] ) {
				continue;
			}
			foreach ( $other_pack['snippets'] as $other_option ) {
				$shared_pack_names[ (int) $other_option['library_id'] ][ $other_pack['slug'] ] = $other_pack['name'];
			}
		}

		$rows = array();
		foreach ( $pack['snippets'] as $option ) {
			$lib_id       = (int) $option['library_id'];
			$local        = isset( $installed[ $lib_id ] ) ? $installed[ $lib_id ] : null;
			$is_installed = (bool) $local;
			// WPCode encodes active state in post_status (publish = active, draft = inactive).
			$active   = $is_installed && 'publish' === $local->post_status;
			$edit_url = $is_installed ? admin_url( 'admin.php?page=wpcode-snippet-manager&snippet_id=' . $local->ID ) : '';

			// Other packs that also include this snippet (deduped by slug).
			$shared_with = isset( $shared_pack_names[ $lib_id ] ) ? array_values( $shared_pack_names[ $lib_id ] ) : array();

			$rows[] = array(
				'option'       => $option,
				'is_installed' => $is_installed,
				'active'       => $active,
				'edit_url'     => $edit_url,
				'shared_with'  => $shared_with,
			);
		}
		$active_count = 0;
		foreach ( $rows as $row ) {
			if ( $row['active'] ) {
				$active_count++;
			}
		}

		$back_url = add_query_arg(
			array(
				'page' => 'wpcode-snippet-manager',
				'tab'  => 'packs',
			),
			admin_url( 'admin.php' )
		);
		?>
		<div class="wpcode-pack-detail"
			data-pack-slug="<?php echo esc_attr( $pack['slug'] ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpcode_packs' ) ); ?>">
			<a href="<?php echo esc_url( $back_url ); ?>" class="wpcode-pack-detail-back">
				&larr; <?php esc_html_e( 'All packs', 'insert-headers-and-footers' ); ?>
			</a>
			<div class="wpcode-detail-head">
				<div class="wpcode-pack-icon wpcode-pack-icon-lg">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
				</div>
				<div class="info">
					<h2><?php echo esc_html( $pack['name'] ); ?></h2>
					<div class="meta-row">
						<span class="cat-badge"><?php echo esc_html( $group_name ); ?></span>
						<span class="dot"></span>
						<span>
							<?php
							printf(
								/* translators: %d snippet count. */
								esc_html( _n( '%d snippet', '%d snippets', count( $pack['snippets'] ), 'insert-headers-and-footers' ) ),
								count( $pack['snippets'] )
							);
							?>
						</span>
						<?php if ( $pack['installed_at'] > 0 ) : ?>
							<span class="dot"></span>
							<span>
								<?php
								printf(
									/* translators: %s install date. */
									esc_html__( 'Installed %s', 'insert-headers-and-footers' ),
									esc_html( date_i18n( get_option( 'date_format' ), $pack['installed_at'] ) )
								);
								?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $pack['description'] ) ) : ?>
				<p class="wpcode-detail-desc"><?php echo esc_html( $pack['description'] ); ?></p>
			<?php endif; ?>

			<div class="wpcode-snippets-card">
				<div class="head">
					<h3><?php esc_html_e( 'Snippets in this pack', 'insert-headers-and-footers' ); ?></h3>
					<div class="meta">
						<?php
						printf(
							/* translators: %1$d active, %2$d total. */
							esc_html__( '%1$d of %2$d active', 'insert-headers-and-footers' ),
							(int) $active_count,
							count( $pack['snippets'] )
						);
						?>
					</div>
				</div>
				<?php foreach ( $rows as $row ) :
					$option = $row['option'];
					?>
					<?php
					// A shared snippet is a single underlying snippet that several
					// packs include — it's still toggleable from here, and toggling
					// it affects every pack that lists it. Note that wherever it's
					// shared so the user understands the link.
					$shared_with = $row['shared_with'];
					?>
					<div class="wpcode-snippet-row <?php echo $row['active'] ? '' : 'is-inactive'; ?>"
						data-library-id="<?php echo (int) $option['library_id']; ?>">
						<div class="toggle-wrap">
							<?php if ( $row['is_installed'] ) : ?>
								<div class="wpcode-toggle <?php echo $row['active'] ? 'is-on' : ''; ?> js-wpcode-toggle-option"
									role="switch"
									aria-checked="<?php echo $row['active'] ? 'true' : 'false'; ?>"
									tabindex="0"></div>
							<?php else : ?>
								<button type="button" class="wpcode-button wpcode-button-secondary js-wpcode-add-option"><?php esc_html_e( 'Add', 'insert-headers-and-footers' ); ?></button>
							<?php endif; ?>
						</div>
						<div class="snippet-info">
							<h5><?php echo esc_html( $option['title'] ); ?></h5>
							<?php if ( ! empty( $option['needs_personalization'] ) ) : ?>
								<span class="wpcode-snippet-personalize-note">
									<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									<?php esc_html_e( 'Edit this snippet to personalize its values.', 'insert-headers-and-footers' ); ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $shared_with ) ) : ?>
								<span class="wpcode-snippet-shared-note">
									<?php
									printf(
										/* translators: %s: comma-separated list of other packs that also include this snippet. */
										esc_html__( 'Shared with %s', 'insert-headers-and-footers' ),
										esc_html( implode( ', ', $shared_with ) )
									);
									?>
								</span>
							<?php endif; ?>
						</div>
						<div class="actions">
							<?php if ( $row['is_installed'] ) : ?>
								<a href="<?php echo esc_url( $row['edit_url'] ); ?>" class="wpcode-button wpcode-button-secondary"><?php esc_html_e( 'Edit snippet', 'insert-headers-and-footers' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $pack['installed'] ) : ?>
				<div class="wpcode-detail-actions">
					<button type="button" class="wpcode-button wpcode-button-secondary wpcode-button-danger js-wpcode-remove-pack"><?php esc_html_e( 'Remove pack', 'insert-headers-and-footers' ); ?></button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output the hidden install-modal template + JSON payload.
	 *
	 * @return void
	 */
	protected function render_install_modal_template() {
		$packs = WPCode_Packs::get_instance()->get_packs();
		$payload = array();
		foreach ( $packs as $pack ) {
			$payload[ $pack['slug'] ] = array(
				'name'     => $pack['name'],
				'icon'     => $this->pack_icon( $pack ),
				'category' => $this->group_name( $pack ),
				'snippets' => wp_list_pluck( $pack['snippets'], 'title' ),
			);
		}
		?>
		<script type="application/json" id="wpcode-packs-payload"><?php
			echo wp_json_encode(
				array(
					'packs' => $payload,
					'nonce'   => wp_create_nonce( 'wpcode_packs' ),
					'i18n'    => array(
						'install_title'     => __( 'Install %s?', 'insert-headers-and-footers' ),
						'installing_title'  => __( 'Installing %s…', 'insert-headers-and-footers' ),
						'installed_title'   => __( '%s installed', 'insert-headers-and-footers' ),
						'snippets_subtitle' => __( '%1$d snippets · %2$s · Free in Lite', 'insert-headers-and-footers' ),
						'confirm_lead'      => __( 'These snippets will be added to your library and activated. You can toggle or remove any of them after installation.', 'insert-headers-and-footers' ),
						'creating_snippets' => __( 'Creating snippets in your library…', 'insert-headers-and-footers' ),
						'success_heading'   => __( 'All %d snippets activated', 'insert-headers-and-footers' ),
						'success_body'      => __( "They're live now. Edit any of them in your library, or open the pack dashboard to toggle individual snippets.", 'insert-headers-and-footers' ),
						/* translators: %1$d: number activated, %2$d: total in the pack. */
						'success_heading_partial' => __( '%1$d of %2$d snippets activated', 'insert-headers-and-footers' ),
						'success_body_partial'    => __( 'Some snippets need attention — open the pack dashboard to review the ones that could not be activated.', 'insert-headers-and-footers' ),
						'cancel'            => __( 'Cancel', 'insert-headers-and-footers' ),
						'install_button'    => __( 'Install pack', 'insert-headers-and-footers' ),
						'installing_button' => __( 'Installing…', 'insert-headers-and-footers' ),
						'back_to_packs'   => __( 'Back to packs', 'insert-headers-and-footers' ),
						'open_dashboard'    => __( 'Open pack dashboard', 'insert-headers-and-footers' ),
						'confirm_remove'    => __( 'Remove %1$s? This will delete all %2$d snippets it created.', 'insert-headers-and-footers' ),
						'remove_title'      => __( 'Remove pack?', 'insert-headers-and-footers' ),
						'remove_confirm_button' => __( 'Remove pack', 'insert-headers-and-footers' ),
						'activation_refused' => __( "This snippet couldn't be activated — it may contain an error. Edit the snippet to review it.", 'insert-headers-and-footers' ),
					),
				)
			);
		?></script>
		<template id="wpcode-packs-install-modal">
			<div class="wpcode-rmodal-backdrop">
				<div class="wpcode-rmodal">
					<div class="wpcode-rmodal-head">
						<h3 class="js-wpcode-rmodal-title"></h3>
						<div class="sub js-wpcode-rmodal-sub"><span></span></div>
					</div>
					<div class="wpcode-rmodal-body">
						<p class="js-wpcode-rmodal-lead"></p>
						<ul class="wpcode-checklist"></ul>
						<div class="wpcode-progress-track" hidden>
							<div class="wpcode-progress-fill"></div>
						</div>
						<div class="wpcode-progress-label" hidden>
							<span class="js-wpcode-progress-text"></span>
							<span class="js-wpcode-progress-percent"></span>
						</div>
						<div class="wpcode-success-state" hidden>
							<svg xmlns="http://www.w3.org/2000/svg" class="wpcode-icon wpcode-icon-checkmark path circle" width="64" height="64" viewBox="0 0 130.2 130.2" aria-hidden="true">
								<circle fill="none" stroke="#73AF55" stroke-width="6" cx="65.1" cy="65.1" r="62.1"></circle>
								<polyline fill="none" stroke="#73AF55" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "></polyline>
							</svg>
							<h4 class="js-wpcode-success-heading"></h4>
							<p class="js-wpcode-success-body"></p>
						</div>
					</div>
					<div class="wpcode-rmodal-foot">
						<button type="button" class="wpcode-button wpcode-button-secondary js-wpcode-rmodal-cancel"></button>
						<button type="button" class="wpcode-button js-wpcode-rmodal-primary"><span></span></button>
					</div>
				</div>
			</div>
		</template>
		<?php
	}

	/**
	 * The pack's group label, as authored on the library (e.g. "Performance").
	 * Used as the badge and as the sidebar grouping label.
	 *
	 * @param array $pack Pack data.
	 *
	 * @return string
	 */
	protected function group_name( $pack ) {
		$group = isset( $pack['group'] ) ? trim( (string) $pack['group'] ) : '';
		return '' !== $group ? $group : __( 'Other', 'insert-headers-and-footers' );
	}

	/**
	 * A slug form of the group label, used for the sidebar filter buttons and
	 * the card's data-categories attribute.
	 *
	 * @param array $pack Pack data.
	 *
	 * @return string
	 */
	protected function group_slug( $pack ) {
		return sanitize_title( $this->group_name( $pack ) );
	}

	/**
	 * The dashicon to render for a pack. Driven entirely by the library's
	 * pack_icon field; a leading "dashicons-" is added if the editor left it
	 * off, and a neutral default is used when no icon is set.
	 *
	 * @param array $pack Pack data.
	 *
	 * @return string
	 */
	protected function pack_icon( $pack ) {
		$icon = isset( $pack['icon'] ) ? trim( (string) $pack['icon'] ) : '';
		if ( '' === $icon ) {
			return self::DEFAULT_ICON;
		}
		if ( 0 !== strpos( $icon, 'dashicons-' ) ) {
			$icon = 'dashicons-' . $icon;
		}
		return $icon;
	}
}
