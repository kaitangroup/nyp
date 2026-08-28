<?php
/**
 * Execute CSS snippets and return their output.
 *
 * @package wpcode
 */

/**
 * WPCode_Snippet_Execute_CSS class.
 */
class WPCode_Snippet_Execute_CSS extends WPCode_Snippet_Execute_Type {

	/**
	 * The snippet type, JavaScript for this one.
	 *
	 * @var string
	 */
	public $type = 'css';

	/**
	 * Grab snippet code and return its output.
	 *
	 * @return string
	 */
	protected function prepare_snippet_output() {
		$code = $this->get_snippet_code();

		$snippet_id = $this->snippet->get_id();
		$style_id   = 'wpcode-snippet-css-' . $snippet_id;

		// Detect live preview context for this snippet.
		$is_live_preview = false;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( is_user_logged_in() && current_user_can( 'wpcode_edit_snippets' ) && isset( $_GET['wpcode_preview'], $_GET['snippet_id'] ) && '1' === $_GET['wpcode_preview'] && absint( $_GET['snippet_id'] ) === $snippet_id ) {
			$is_live_preview = true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// For normal output, only render when code is not empty.
		// For live preview of this snippet, always output a style tag so the editor can target it,
		// inserting a harmless comment placeholder when the CSS is empty.
		if ( '' === trim( $code ) ) {
			if ( ! $is_live_preview ) {
				return '';
			}
			$code = '/* inserted by WPCode live preview */';
		}

		// In live preview add a deterministic id for targeting; otherwise keep original behavior (no id).
		if ( $is_live_preview ) {
			return '<style id="' . esc_attr( $style_id ) . '" class="wpcode-css-snippet">' . $code . '</style>';
		}

		return '<style class="wpcode-css-snippet">' . $code . '</style>';
	}
}
