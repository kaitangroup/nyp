<?php
/**
 * Preview Frame Handler for Live CSS Preview.
 *
 * This class handles the front-end preview mode when viewing a snippet in the live preview.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Preview_Frame
 */
class WPCode_Preview_Frame {

	/**
	 * The snippet ID being previewed.
	 *
	 * @var int
	 */
	private $snippet_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_load_preview' ) );
	}

	/**
	 * Check if we're in preview mode and load the necessary scripts.
	 *
	 * @return void
	 */
	public function maybe_load_preview() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		// Check if the current user can edit snippets.
		if ( ! current_user_can( 'wpcode_edit_snippets' ) ) {  // phpcs:ignore
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpcode_preview'] ) || '1' !== $_GET['wpcode_preview'] ) {
			return;
		}

		if ( ! isset( $_GET['snippet_id'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->snippet_id = absint( $_GET['snippet_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Hide the WP admin bar in preview iframe.
		add_filter( 'show_admin_bar', '__return_false' );

		// During preview, ensure CSS/SCSS for this snippet is output inline (even if normally loaded as file).
		$force_inline_callback = function ( $code, $snippet ) {
			if ( $snippet && method_exists( $snippet, 'get_id' ) && $snippet->get_id() === $this->snippet_id ) {
				$snippet->load_as_file = false;
			}

			return $code;
		};
		add_filter( 'wpcode_snippet_output_css', $force_inline_callback, 1, 2 );
		add_filter( 'wpcode_snippet_output_scss', $force_inline_callback, 1, 2 );

		// Ensure the snippet is considered by the auto-insert flow during preview without hardcoding locations.
		add_filter( 'wpcode_get_snippets_for_location', array( $this, 'maybe_force_include_snippet' ), 10, 2 );

		// Enqueue the preview iframe script and then localize data for it.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_script' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_preview_data' ), 11 );
	}

	/**
	 * Enqueue the preview iframe script so it can communicate via postMessage.
	 *
	 * @return void
	 */
	public function enqueue_preview_script() {
		$handle    = 'wpcode-preview-frame';
		$src       = WPCODE_PLUGIN_URL . 'build/css-live-preview.js';
		$deps      = array( 'jquery' );
		$version   = defined( 'WPCODE_VERSION' ) ? WPCODE_VERSION : false;
		$in_footer = true;

		wp_register_script( $handle, $src, $deps, $version, $in_footer );
		wp_enqueue_script( $handle );
	}

	/**
	 * Localize preview data for the JavaScript.
	 *
	 * @return void
	 */
	public function localize_preview_data() {
		$snippet   = wpcode_get_snippet( $this->snippet_id );
		$code_type = $snippet ? $snippet->get_code_type() : 'css';

		wp_localize_script(
			'wpcode-preview-frame',
			'wpcodePreviewFrame',
			array(
				'adminUrl'  => admin_url(),
				'snippetId' => $this->snippet_id,
				'codeType'  => $code_type,
			)
		);
	}

	/**
	 * During preview, force-include the current CSS snippet into its configured location
	 * while honoring conditional logic and avoiding duplicates.
	 *
	 * @param WPCode_Snippet[] $snippets The snippets already queued for the location.
	 * @param string           $location The location slug currently being processed.
	 *
	 * @return WPCode_Snippet[]
	 */
	public function maybe_force_include_snippet( $snippets, $location ) {
		if ( empty( $this->snippet_id ) ) {
			return $snippets;
		}

		$snippet = wpcode_get_snippet( $this->snippet_id );
		if ( ! $snippet || ! in_array( $snippet->get_code_type(), array( 'css', 'scss' ), true ) ) {
			return $snippets;
		}

		// Only add to the snippet's configured location.
		if ( $snippet->get_location() !== $location ) {
			return $snippets;
		}

		// Avoid duplicates if already present (e.g., active snippet).
		foreach ( $snippets as $s ) {
			if ( $s->get_id() === $snippet->get_id() ) {
				return $snippets;
			}
		}

		// Honor conditional logic. If not met on this page, don't include.
		if ( $snippet->conditional_rules_enabled() && ! wpcode()->conditional_logic->are_snippet_rules_met( $snippet ) ) {
			return $snippets;
		}

		$snippets[] = $snippet;

		return $snippets;
	}
}

new WPCode_Preview_Frame();
