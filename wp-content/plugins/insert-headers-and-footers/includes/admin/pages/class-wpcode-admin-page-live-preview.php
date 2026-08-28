<?php
/**
 * Live Preview Admin Page.
 *
 * This page handles the split-screen CSS live preview interface.
 *
 * @package WPCode
 */

/**
 * Class WPCode_Admin_Page_Live_Preview
 */
class WPCode_Admin_Page_Live_Preview extends WPCode_Admin_Page {

	/**
	 * The page slug to be used when adding the submenu.
	 *
	 * @var string
	 */
	public $page_slug = 'wpcode-live-preview';

	/**
	 * Hide this page from the menu.
	 *
	 * @var bool
	 */
	public $hide_menu = true;

	/**
	 * The snippet ID being previewed.
	 *
	 * @var int
	 */
	private $snippet_id;

	/**
	 * The snippet instance.
	 *
	 * @var WPCode_Snippet
	 */
	private $snippet;

	/**
	 * The CSS code to preview.
	 *
	 * @var string
	 */
	private $preview_css;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->page_title = __( 'Live CSS Preview', 'insert-headers-and-footers' );
		parent::__construct();
	}

	/**
	 * Page-specific hooks.
	 *
	 * @return void
	 */
	public function page_hooks() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['snippet_id'] ) ) {
			wp_die( esc_html__( 'No snippet ID provided.', 'insert-headers-and-footers' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpcode-live-preview' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'insert-headers-and-footers' ) );
		}

		$this->snippet_id = absint( $_GET['snippet_id'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $this->snippet_id ? ! current_user_can( 'edit_post', $this->snippet_id ) : ! current_user_can( 'wpcode_edit_snippets' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'insert-headers-and-footers' ), '', array( 'response' => 403 ) );
		}

		$this->snippet = wpcode_get_snippet( $this->snippet_id );

		if ( ! $this->snippet ) {
			wp_die( esc_html__( 'Invalid snippet ID.', 'insert-headers-and-footers' ) );
		}

		// Get the CSS to preview.
		$this->preview_css = $this->get_snippet_css();

		// Store CSS in transient for the preview frame.
		$this->save_preview_css();

		add_filter( 'wpcode_admin_js_data', array( $this, 'add_preview_data' ) );
	}

	/**
	 * Output the page content.
	 *
	 * @return void
	 */
	public function output_content() {
		$preview_url = add_query_arg(
			array(
				'wpcode_preview' => '1',
				'snippet_id'     => $this->snippet_id,
			),
			home_url( '/' )
		);
		?>
		<div class="wpcode-live-preview-container">
			<div class="wpcode-live-preview-editor">
				<div class="wpcode-live-preview-editor-header">
					<h3><?php echo 'scss' === $this->snippet->get_code_type() ? esc_html__( 'SCSS Editor', 'insert-headers-and-footers' ) : esc_html__( 'CSS Editor', 'insert-headers-and-footers' ); ?></h3>
					<div class="wpcode-live-preview-device-toggles">
						<button type="button" class="wpcode-device-toggle wpcode-device-desktop wpcode-active" data-device="desktop" title="<?php esc_attr_e( 'Desktop View', 'insert-headers-and-footers' ); ?>">
							<?php wpcode_icon( 'desktop', 20, 20, '0 0 48 48' ); ?>
						</button>
						<button type="button" class="wpcode-device-toggle wpcode-device-tablet" data-device="tablet" title="<?php esc_attr_e( 'Tablet View', 'insert-headers-and-footers' ); ?>">
							<?php wpcode_icon( 'tablet', 20, 20, '2 1 20 20' ); ?>
						</button>
						<button type="button" class="wpcode-device-toggle wpcode-device-mobile" data-device="mobile" title="<?php esc_attr_e( 'Mobile View', 'insert-headers-and-footers' ); ?>">
							<?php wpcode_icon( 'mobile', 20, 20, '0 0 48 48' ); ?>
						</button>
					</div>
				</div>
				<div class="wpcode-live-preview-editor-area">
					<textarea id="wpcode-live-preview-css-editor" name="wpcode_live_preview_css"><?php echo esc_textarea( $this->preview_css ); ?></textarea>
				</div>
			</div>
			<div class="wpcode-live-preview-resizer" title="<?php esc_attr_e( 'Drag to resize the editor', 'insert-headers-and-footers' ); ?>"></div>
			<div class="wpcode-live-preview-iframe-wrapper wpcode-device-desktop">
				<div class="wpcode-live-preview-loading">
					<?php esc_html_e( 'Loading preview...', 'insert-headers-and-footers' ); ?>
				</div>
				<iframe id="wpcode-live-preview-frame" src="<?php echo esc_url( $preview_url ); ?>" title="<?php esc_attr_e( 'Live Preview', 'insert-headers-and-footers' ); ?>"></iframe>
			</div>
		</div>
		<div class="wpcode-live-preview-actions">
			<img id="wpcode-live-preview-logo" class="wpcode-live-preview-logo" src="<?php echo esc_url( WPCODE_PLUGIN_URL . 'admin/images/wpcode-logo.png' ); ?>" alt="<?php esc_attr_e( 'WPCode', 'insert-headers-and-footers' ); ?>" />
			<button type="button" class="wpcode-button wpcode-button-secondary" id="wpcode-live-preview-discard">
				<?php esc_html_e( 'Discard & Exit', 'insert-headers-and-footers' ); ?>
			</button>
			<button type="button" class="wpcode-button" id="wpcode-live-preview-save">
				<?php esc_html_e( 'Save & Exit', 'insert-headers-and-footers' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Output the header for this page.
	 *
	 * @return void
	 */
	public function output_header() {
		// Intentionally empty - no header for the live preview page.
	}

	/**
	 * Don't output the standard footer for this page.
	 *
	 * @return void
	 */
	public function output_footer() {
		// Intentionally empty - we don't want the standard footer overlays.
	}

	/**
	 * Enqueue page-specific scripts.
	 *
	 * @return void
	 */
	public function page_scripts() {
		// Determine the code type (CSS or SCSS).
		$code_type = $this->snippet ? $this->snippet->get_code_type() : 'css';

		// The code editor.
		$code_editor = new WPCode_Code_Editor( $code_type );
		$code_editor->register_editor( 'wpcode-live-preview-css-editor' );
		$code_editor->init_editor();
	}

	/**
	 * Add preview-specific data to the JavaScript.
	 *
	 * @param array $data The existing data.
	 *
	 * @return array
	 */
	public function add_preview_data( $data ) {
		$data['livePreview'] = array(
			'snippetId'   => $this->snippet_id,
			'editorId'    => 'wpcode-live-preview-css-editor',
			'iframeId'    => 'wpcode-live-preview-frame',
			'snippetUrl'  => add_query_arg(
				array(
					'page'       => 'wpcode-snippet-manager',
					'snippet_id' => $this->snippet_id,
				),
				admin_url( 'admin.php' )
			),
			'hasChanges'  => false,
			'exitConfirm' => __( 'You have unsaved changes. Are you sure you want to exit?', 'insert-headers-and-footers' ),
			'codeType'    => $this->snippet ? $this->snippet->get_code_type() : 'css',
		);

		return $data;
	}

	/**
	 * Get the CSS/SCSS code to preview.
	 *
	 * @return string
	 */
	private function get_snippet_css() {
		$user_id   = get_current_user_id();
		$code_type = $this->snippet->get_code_type();

		// For SCSS snippets, check for unsaved SCSS source first.
		if ( 'scss' === $code_type ) {
			$source_transient_key = "wpcode_preview_scss_source_{$user_id}_{$this->snippet_id}";
			$transient_source     = get_transient( $source_transient_key );

			if ( false !== $transient_source ) {
				return $transient_source;
			}
		} else {
			// For CSS snippets, check if there's unsaved CSS in the transient.
			$transient_key = "wpcode_preview_css_{$user_id}_{$this->snippet_id}";
			$transient_css = get_transient( $transient_key );

			if ( false !== $transient_css ) {
				return $transient_css;
			}
		}

		// Otherwise, get the code from the snippet.
		return $this->snippet->get_code();
	}

	/**
	 * Save the preview CSS to a transient.
	 *
	 * @return void
	 */
	private function save_preview_css() {
		$user_id   = get_current_user_id();
		$code_type = $this->snippet->get_code_type();

		// For SCSS, store the source code in the source transient.
		// The compiled CSS will be generated client-side and stored separately.
		if ( 'scss' === $code_type ) {
			$source_transient_key = "wpcode_preview_scss_source_{$user_id}_{$this->snippet_id}";
			set_transient( $source_transient_key, $this->preview_css, HOUR_IN_SECONDS );
		} else {
			// For CSS, store directly.
			$transient_key = "wpcode_preview_css_{$user_id}_{$this->snippet_id}";
			set_transient( $transient_key, $this->preview_css, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Add a body class specific to the live preview page.
	 *
	 * @param string $body_class The existing body classes.
	 *
	 * @return string
	 */
	public function page_specific_body_class( $body_class ) {
		$body_class  = parent::page_specific_body_class( $body_class );
		$body_class .= ' wpcode-live-preview-page';

		return $body_class;
	}
}

