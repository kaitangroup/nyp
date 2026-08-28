<?php
/**
 * WordPress Abilities API Integration for WPCode (Read-Only)
 *
 * @package WPCode
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPCode_Abilities_API class.
 *
 * Registers read-only abilities for WordPress 6.9+ Abilities API
 * to allow AI and automation tools to query WPCode data safely.
 */
class WPCode_Abilities_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register WPCode categories for the Abilities API.
	 *
	 * @return void
	 */
	public function register_categories() {
		// WPCode Diagnostics category.
		wp_register_ability_category(
			'wpcode-diagnostics',
			array(
				'label'       => __( 'WPCode Diagnostics', 'insert-headers-and-footers' ),
				'description' => __( 'Abilities for checking snippet health', 'insert-headers-and-footers' ),
			)
		);

		// WPCode Library category.
		wp_register_ability_category(
			'wpcode-library',
			array(
				'label'       => __( 'WPCode Library', 'insert-headers-and-footers' ),
				'description' => __( 'Abilities for searching pre-built templates', 'insert-headers-and-footers' ),
			)
		);

		// WPCode Insights category.
		wp_register_ability_category(
			'wpcode-insights',
			array(
				'label'       => __( 'WPCode Insights', 'insert-headers-and-footers' ),
				'description' => __( 'Abilities for reading current snippet configurations', 'insert-headers-and-footers' ),
			)
		);
	}

	/**
	 * Register all read-only abilities.
	 *
	 * @return void
	 */
	public function register_abilities() {
		// 1. List snippets.
		$this->register_list_snippets_ability();

		// 2. Detect snippet errors.
		$this->register_detect_snippet_errors_ability();

		// 3. Get error logs.
		$this->register_get_error_logs_ability();

		// 4. Search library.
		$this->register_search_library_ability();

		// 5. Get settings.
		$this->register_get_settings_ability();
	}

	/**
	 * Register ability to list snippets.
	 *
	 * @return void
	 */
	private function register_list_snippets_ability() {
		wp_register_ability(
			'wpcode/list-snippets',
			array(
				'label'               => __( 'List Code Snippets', 'insert-headers-and-footers' ),
				'description'         => __( 'Provides a summary of all snippets and their active/inactive status', 'insert-headers-and-footers' ),
				'category'            => 'wpcode-diagnostics',
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'status'    => array(
							'type'        => 'string',
							'enum'        => array( 'all', 'active', 'inactive' ),
							'description' => __( 'Filter by snippet status', 'insert-headers-and-footers' ),
							'default'     => 'all',
						),
						'code_type' => array(
							'type'        => 'string',
							'enum'        => array( 'php', 'js', 'css', 'html', 'text', 'universal', 'blocks', 'scss' ),
							'description' => __( 'Filter by code type', 'insert-headers-and-footers' ),
						),
						'tag'       => array(
							'type'        => 'string',
							'description' => __( 'Filter by tag', 'insert-headers-and-footers' ),
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array( 'type' => 'integer' ),
							'title'       => array( 'type' => 'string' ),
							'code_type'   => array( 'type' => 'string' ),
							'location'    => array( 'type' => 'string' ),
							'active'      => array( 'type' => 'boolean' ),
							'tags'        => array( 'type' => 'array' ),
							'has_error'   => array( 'type' => 'boolean' ),
							'last_update' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_snippets' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * Register ability to detect snippet errors.
	 *
	 * @return void
	 */
	private function register_detect_snippet_errors_ability() {
		wp_register_ability(
			'wpcode/detect-snippet-errors',
			array(
				'label'               => __( 'Detect Snippet Errors', 'insert-headers-and-footers' ),
				'description'         => __( 'Checks if WPCode\'s error handler has flagged any active snippets', 'insert-headers-and-footers' ),
				'category'            => 'wpcode-diagnostics',
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'snippet_id' => array(
							'type'        => 'integer',
							'description' => __( 'Snippet ID to check', 'insert-headers-and-footers' ),
						),
					),
					'required'   => array( 'snippet_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'has_error'     => array( 'type' => 'boolean' ),
						'error_message' => array( 'type' => 'string' ),
						'error_line'    => array( 'type' => 'integer' ),
						'error_time'    => array( 'type' => 'integer' ),
						'error_url'     => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_detect_snippet_errors' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * Register ability to get error logs.
	 *
	 * @return void
	 */
	private function register_get_error_logs_ability() {
		wp_register_ability(
			'wpcode/get-error-logs',
			array(
				'label'               => __( 'Get Error Logs', 'insert-headers-and-footers' ),
				'description'         => __( 'Provides the stack trace of recent snippet-related failures', 'insert-headers-and-footers' ),
				'category'            => 'wpcode-diagnostics',
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'snippets_affected' => array( 'type' => 'integer' ),
						'log_files'         => array( 'type' => 'array' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_error_logs' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);
	}

	/**
	 * Register ability to search library.
	 *
	 * @return void
	 */
	private function register_search_library_ability() {
		wp_register_ability(
			'wpcode/search-library',
			array(
				'label'               => __( 'Search WPCode Library', 'insert-headers-and-footers' ),
				'description'         => __( 'Queries the WPCode Cloud Library for snippets matching a keyword', 'insert-headers-and-footers' ),
				'category'            => 'wpcode-library',
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'keyword' => array(
							'type'        => 'string',
							'description' => __( 'Search keyword', 'insert-headers-and-footers' ),
						),
					),
					'required'   => array( 'keyword' ),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array( 'type' => 'string' ),
							'title'       => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'category'    => array( 'type' => 'string' ),
							'tags'        => array( 'type' => 'array' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_search_library' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);
	}

	/**
	 * Register ability to get settings.
	 *
	 * @return void
	 */
	private function register_get_settings_ability() {
		wp_register_ability(
			'wpcode/get-settings',
			array(
				'label'               => __( 'Get WPCode Settings', 'insert-headers-and-footers' ),
				'description'         => __( 'Reads global settings (e.g., "Safe Mode" status)', 'insert-headers-and-footers' ),
				'category'            => 'wpcode-insights',
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'version'          => array( 'type' => 'string' ),
						'error_logging'    => array( 'type' => 'boolean' ),
						'safe_mode_active' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_settings' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);
	}

	// ========================================
	// PERMISSION CALLBACKS
	// ========================================

	/**
	 * Check if user has permission to read WPCode data.
	 *
	 * @return bool
	 */
	public function check_read_permission() {
		return current_user_can( 'wpcode_edit_snippets' );
	}

	/**
	 * Check if user has admin permission.
	 *
	 * @return bool
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	// ========================================
	// EXECUTION METHODS
	// ========================================

	/**
	 * Execute list snippets.
	 *
	 * @param array $input Input parameters.
	 *
	 * @return array
	 */
	public function execute_list_snippets( $input ) {
		$args = array(
			'post_type'      => 'wpcode',
			'posts_per_page' => - 1,
			'post_status'    => array( 'publish', 'draft' ),
		);

		// Apply status filter.
		if ( ! empty( $input['status'] ) && 'all' !== $input['status'] ) {
			$args['post_status'] = 'active' === $input['status'] ? 'publish' : 'draft';
		}

		// Apply code type filter.
		if ( ! empty( $input['code_type'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'wpcode_type',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $input['code_type'] ),
				),
			);
		}

		// Apply tag filter.
		if ( ! empty( $input['tag'] ) ) {
			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = array();
			}
			$args['tax_query'][] = array(
				'taxonomy' => 'wpcode_tags',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $input['tag'] ),
			);
		}

		$snippets = get_posts( $args );
		$result   = array();

		foreach ( $snippets as $post ) {
			$snippet    = new WPCode_Snippet( $post->ID );
			$last_error = $snippet->get_last_error();

			$result[] = array(
				'id'          => $snippet->get_id(),
				'title'       => $snippet->get_title(),
				'code_type'   => $snippet->get_code_type(),
				'location'    => $snippet->get_location(),
				'active'      => $snippet->is_active(),
				'tags'        => $snippet->get_tags(),
				'has_error'   => ! empty( $last_error ),
				'last_update' => get_the_modified_date( 'c', $post ),
				// IMPORTANT: Do NOT include 'code' field for security.
			);
		}

		return $result;
	}

	/**
	 * Execute detect snippet errors.
	 *
	 * @param array $input Input parameters.
	 *
	 * @return array|WP_Error
	 */
	public function execute_detect_snippet_errors( $input ) {
		$snippet_id = absint( $input['snippet_id'] );
		$snippet    = new WPCode_Snippet( $snippet_id );

		if ( ! $snippet->get_id() ) {
			return new WP_Error(
				'snippet_not_found',
				__( 'Snippet not found', 'insert-headers-and-footers' ),
				array( 'status' => 404 )
			);
		}

		$last_error = $snippet->get_last_error();

		return array(
			'has_error'     => ! empty( $last_error ),
			'error_message' => ! empty( $last_error['message'] ) ? $last_error['message'] : '',
			'error_line'    => ! empty( $last_error['error_line'] ) ? (int) $last_error['error_line'] : 0,
			'error_time'    => ! empty( $last_error['time'] ) ? (int) $last_error['time'] : 0,
			'error_url'     => ! empty( $last_error['url'] ) ? esc_url( $last_error['url'] ) : '',
		);
	}

	/**
	 * Execute get error logs.
	 *
	 * @param array|null $input Input parameters (optional, not used).
	 *
	 * @return array
	 */
	public function execute_get_error_logs( $input = null ) {
		$logs = wpcode()->logger->get_logs();

		return array(
			'snippets_affected' => wpcode()->error->get_error_count(),
			'log_files'         => $logs,
		);
	}

	/**
	 * Execute search library.
	 *
	 * @param array $input Input parameters.
	 *
	 * @return array|WP_Error
	 */
	public function execute_search_library( $input ) {
		$keyword = sanitize_text_field( $input['keyword'] );

		// Lazy load library classes if not already available.
		if ( ! isset( wpcode()->library ) || ! is_object( wpcode()->library ) ) {
			if ( ! class_exists( 'WPCode_File_Cache' ) ) {
				require_once WPCODE_PLUGIN_PATH . 'includes/class-wpcode-file-cache.php';
			}
			if ( ! class_exists( 'WPCode_Library' ) ) {
				require_once WPCODE_PLUGIN_PATH . 'includes/class-wpcode-library.php';
			}
			if ( ! class_exists( 'WPCode_Library_Auth' ) ) {
				require_once WPCODE_PLUGIN_PATH . 'includes/class-wpcode-library-auth.php';
			}
			wpcode()->file_cache   = new WPCode_File_Cache();
			wpcode()->library      = new WPCode_Library();
			wpcode()->library_auth = new WPCode_Library_Auth();
		}

		// Use the library's search method.
		if ( method_exists( wpcode()->library, 'search_snippets' ) ) {
			$results = wpcode()->library->search_snippets( $keyword );
		} else {
			$data         = wpcode()->library->get_data();
			$all_snippets = $data['snippets'];
			$results      = array();

			foreach ( $all_snippets as $snippet ) {
				if (
					stripos( $snippet['title'], $keyword ) !== false ||
					( isset( $snippet['note'] ) && stripos( $snippet['note'], $keyword ) !== false )
				) {
					$results[] = $snippet;
				}
			}
		}

		// Format results for output.
		$formatted_results = array();
		foreach ( $results as $snippet ) {
			$formatted_results[] = array(
				'id'          => isset( $snippet['id'] ) ? (string) $snippet['id'] : '',
				'title'       => isset( $snippet['title'] ) ? $snippet['title'] : '',
				'description' => isset( $snippet['note'] ) ? $snippet['note'] : '',
				'category'    => isset( $snippet['categories'][0] ) ? $snippet['categories'][0] : '',
				'tags'        => isset( $snippet['tags'] ) ? $snippet['tags'] : array(),
			);
		}

		return $formatted_results;
	}

	/**
	 * Execute get settings.
	 *
	 * @param array|null $input Input parameters (optional, not used).
	 *
	 * @return array
	 */
	public function execute_get_settings( $input = null ) {
		return array(
			'version'          => WPCODE_VERSION,
			'error_logging'    => wpcode()->logger->is_enabled(),
			'safe_mode_active' => $this->is_safe_mode_active(),
		);
	}

	/**
	 * Check if WPCode Safe Mode is currently active.
	 *
	 * Safe Mode in WPCode is a per-request feature activated via the 'wpcode-safe-mode'
	 * query parameter. When active, all snippet execution is disabled for that request.
	 * It's primarily used for debugging when a snippet causes issues.
	 *
	 * @return bool
	 */
	private function is_safe_mode_active() {
		// Safe Mode is determined by the presence of the query param and user capability.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpcode-safe-mode'] ) ) {
			return false;
		}

		// Only authenticated users with proper capability can use safe mode.
		return current_user_can( 'wpcode_activate_snippets' );
	}
}

new WPCode_Abilities_API();
