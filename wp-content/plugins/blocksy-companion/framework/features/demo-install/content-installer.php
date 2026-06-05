<?php

namespace Blocksy;

if (! defined('ABSPATH')) {
	exit;
}

class DemoInstallContentInstaller {
	protected $demo_name = null;
	protected $is_ajax_request = true;

	private $content_started_at = 0;

	public function __construct($args = []) {
		$args = wp_parse_args($args, [
			'demo_name' => null,
			'is_ajax_request' => true,
		]);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_demo_name = isset($_REQUEST['demo_name']) ? sanitize_text_field(wp_unslash($_REQUEST['demo_name'])) : '';

		if (! $args['demo_name'] && $request_demo_name !== '') {
			$args['demo_name'] = $request_demo_name;
		}

		$this->demo_name = $args['demo_name'];
		$this->is_ajax_request = $args['is_ajax_request'];
	}

	public function import() {
		// For AJAX requests, require SSE Accept header
		if ($this->is_ajax_request) {
			$accept_header = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';

			if (strpos($accept_header, 'text/event-stream') === false) {
				wp_send_json_error([
					'message' => __("Invalid request. SSE Accept header required.", 'blocksy-companion')
				]);
			}

			$this->setup_sse_headers();
		}

		add_filter(
			'option_uploads_use_yearmonth_folders',
			'__return_true',
			100
		);

		if (class_exists('\Astra_Sites')) {
			$astra_sites_instance = \Astra_Sites::get_instance();

			remove_filter(
				'wp_import_post_data_processed',
				[$astra_sites_instance, 'wp_slash_after_xml_import'],
				99, 2
			);

			if (
				class_exists('\Elementor\Plugin')
				&&
				defined('ELEMENTOR_VERSION')
				&&
				version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')
			) {
				add_filter(
					'wp_import_post_meta',
					['Elementor\Compatibility', 'on_wp_import_post_meta']
				);

				$compatibility_classes = [
					'\AstraSites\Elementor\Astra_Sites_Compatibility_Elementor',
					'\AI_Builder\Inc\Compatibility\Elementor\AI_Builder_Compatibility_Elementor',
				];

				foreach ($compatibility_classes as $class) {
					if (class_exists($class) && method_exists($class, 'get_instance')) {
						$instance = $class::get_instance();

						remove_filter(
							'wp_import_post_meta',
							[$instance, 'on_wp_import_post_meta']
						);
					}
				}
			}
		}

		if (
			! current_user_can('edit_theme_options')
			&&
			$this->is_ajax_request
		) {
			$this->send_sse_error(__("Sorry, you don't have permission to install content.", 'blocksy-companion'));
		}

		if (class_exists('\Elementor\Compatibility')) {
			remove_filter('wp_import_post_meta', [
				'\Elementor\Compatibility',
				'on_wp_import_post_meta'
			]);
		}

		add_action(
			'blocksy_wp_import_insert_term',
			function($term_id) {
				$this->track_term_insert($term_id);
			},
			10,
			1
		);

		add_action(
			'wp_import_insert_term',
			function($t) {
				$term_id = $t['term_id'];
				$this->track_term_insert($term_id);
			},
			10,
			1
		);

		add_filter(
			'wp_import_post_meta',
			function($meta, $post_id, $post) {
				$this->track_post_insert($post_id);

				$wp_importer = get_plugins('/wordpress-importer');

				if (! empty($wp_importer)) {
					$wp_importer_version = $wp_importer['wordpress-importer.php']['Version'];

					if (! empty($wp_importer_version)) {
						if (version_compare($wp_importer_version, '0.7', '>=')) {
							foreach ($meta as &$m) {
								if ('_elementor_data' === $m['key']) {
									$m['value'] = wp_slash($m['value']);
									break;
								}
							}
						}
					}
				} else {
					foreach ($meta as &$m) {
						if ('_elementor_data' === $m['key']) {
							$m['value'] = wp_slash($m['value']);
							break;
						}
					}
				}

				return $meta;
			},
			10,
			3
		);

		add_filter(
			'wp_import_term_meta',
			function($meta, $term_id, $post) {
				$this->track_term_insert($term_id);

				return $meta;
			},
			10,
			3
		);

		if (! $this->demo_name) {
			if ($this->is_ajax_request) {
				$this->send_sse_error(__("No demo name provided.", 'blocksy-companion'));
			} else {
				return new \WP_Error(
					'blocksy_demo_install_content_no_demo_name',
					__("No demo name provided.", 'blocksy-companion')
				);
			}
		}

		$demo_name = explode(':', $this->demo_name);

		if (! isset($demo_name[1])) {
			$demo_name[1] = '';
		}

		$demo = $demo_name[0];
		$builder = $demo_name[1];

		$demo_to_install = Plugin::instance()->demo->get_currently_installing_demo();

		if (
			empty($demo_to_install)
			||
			! isset($demo_to_install['demo'])
			||
			! isset($demo_to_install['demo']['content'])
		) {
			if ($this->is_ajax_request) {
				$this->send_sse_error(__("No demo data found.", 'blocksy-companion'));
			} else {
				return new \WP_Error(
					'blocksy_demo_install_content_no_demo_data',
					__("No demo data found.", 'blocksy-companion')
				);
			}
		}

		$demo_to_install = $demo_to_install['demo'];

		$wp_import = new \Blocksy_WP_Import();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- required by WP Importer API
		$GLOBALS['wp_import'] = $wp_import;

		$import_data = $wp_import->parse($demo_to_install['content']);

		$wp_import->get_authors_from_import($import_data);

		unset($import_data);

		$author_data = [];

		foreach ($wp_import->authors as $wxr_author) {
			$author = new \stdClass();

			// Always in the WXR
			$author->user_login = $wxr_author['author_login'];

			// Should be in the WXR; no guarantees
			if (isset($wxr_author['author_email'])) {
				$author->user_email = $wxr_author['author_email'];
			}

			if (isset($wxr_author['author_display_name'])) {
				$author->display_name = $wxr_author['author_display_name'];
			}

			if (isset($wxr_author['author_first_name'])) {
				$author->first_name = $wxr_author['author_first_name'];
			}

			if (isset($wxr_author['author_last_name'])) {
				$author->last_name = $wxr_author['author_last_name'];
			}

			$author_data[] = $author;
		}

		// Build the author mapping
		$author_mapping = $this->process_author_mapping( 'skip', $author_data );

		$author_in = wp_list_pluck($author_mapping, 'old_user_login');
		$author_out = wp_list_pluck($author_mapping, 'new_user_login');
		unset($author_mapping, $author_data);

		// $user_select needs to be an array of user IDs
		$user_select = [];
		$invalid_user_select = [];

		foreach ($author_out as $author_login) {
			$user = get_user_by('login', $author_login);

			if ($user) {
				$user_select[] = $user->ID;
			} else {
				$invalid_user_select[] = $author_login;
			}
		}

		if (! empty($invalid_user_select)) {
			# return new WP_Error( 'invalid-author-mapping', sprintf( 'These user_logins are invalid: %s', implode( ',', $invalid_user_select ) ) );
		}

		// Enable default GD library.
		add_filter('wp_image_editors', function ($editors) {
			$gd_editor = 'WP_Image_Editor_GD';
			$editors = array_diff($editors, array($gd_editor));
			array_unshift($editors, $gd_editor);
			return $editors;
		});

		unset($author_out);

		// Clear object cache and suspend cache invalidation during import
		// This prevents stale cached data from causing issues with concurrent requests
		wp_cache_flush();
		wp_suspend_cache_invalidation(true);

		$wp_import->fetch_attachments = true;

		$_GET['import'] = 'wordpress';
		$_GET['step'] = 2;

		$_POST['imported_authors'] = $author_in;
		$_POST['user_map'] = $user_select;
		$_POST['fetch_attachments'] = $wp_import->fetch_attachments;

		$is_first_import_request = true;

		if ($this->is_ajax_request) {
			$body = json_decode(file_get_contents('php://input'), true);

			if (isset($body['requestsPayload']['importer_data'])) {
				$importer_data = $body['requestsPayload']['importer_data'];

				$is_first_import_request = false;

				$wp_import->processed_authors = $importer_data['processed_authors'];
				$wp_import->author_mapping = $importer_data['author_mapping'];
				$wp_import->processed_terms = $importer_data['processed_terms'];
				$wp_import->processed_posts = $importer_data['processed_posts'];
				$wp_import->post_orphans = $importer_data['post_orphans'];
				$wp_import->processed_menu_items = $importer_data['processed_menu_items'];
				$wp_import->menu_item_orphans = $importer_data['menu_item_orphans'];
				$wp_import->missing_menu_items = $importer_data['missing_menu_items'];
				$wp_import->url_remap = $importer_data['url_remap'];
				$wp_import->featured_images = $importer_data['featured_images'];
			}

			$this->content_started_at = microtime(true);

			// Default to 10 minutes if not provided. We want first request to
			// optimistically complete within server limits.
			$content_import_timeout = 600;

			if (
				isset($body['duration'])
				&&
				intval($body['duration']) !== 0
			) {
				$content_import_timeout = intval($body['duration']);
			}

			add_filter(
				'wp_import_post_data_raw',
				function($post) use ($wp_import, $content_import_timeout) {
					$time = microtime(true) - $this->content_started_at;

					$status_message = blocksy_safe_sprintf(
						// translators: %1$s and %2$s are HTML tags for a link.
						__('Importing %1$s: %2$s', 'blocksy-companion'),
						$post['post_type'],
						$post['post_title']
					);

					$this->send_sse_status($status_message);

					if ($time > $content_import_timeout) {
						$import_output = ob_get_clean();
						wp_suspend_cache_invalidation(false);

						$this->send_sse_complete([
							'status' => 'content_import_timeout_reached',
							'import_output' => $import_output,
							'importer_data' => [
								'processed_authors' => $wp_import->processed_authors,
								'author_mapping' => $wp_import->author_mapping,
								'processed_terms' => $wp_import->processed_terms,
								'processed_posts' => $wp_import->processed_posts,
								'post_orphans' => $wp_import->post_orphans,
								'processed_menu_items' => $wp_import->processed_menu_items,
								'menu_item_orphans' => $wp_import->menu_item_orphans,
								'missing_menu_items' => $wp_import->missing_menu_items,
								'url_remap' => $wp_import->url_remap,
								'featured_images' => $wp_import->featured_images,
							],
							'total_processed' => count($wp_import->processed_posts),
							'total_posts' => count($wp_import->posts),
						]);
					}

					return $post;
				}
			);
		}

		ob_start();

		if ($is_first_import_request) {
			$wp_import->import($demo_to_install['content']);
		} else {
			$wp_import->import_partial($demo_to_install['content']);
		}

		$import_output = ob_get_clean();

		if (class_exists('Blocksy_Customizer_Builder')) {
			$header_builder = new \Blocksy_Customizer_Builder();
			$header_builder->patch_header_value_for($wp_import->processed_terms);
		}

		// Re-enable cache invalidation after import
		wp_suspend_cache_invalidation(false);

		$this->clean_plugins_cache();
		$this->assign_pages_ids($demo, $builder);

		if ($this->is_ajax_request) {
			$this->send_sse_complete([
				'processed_posts' => $wp_import->processed_posts,
				'processed_terms' => $wp_import->processed_terms,
				'import_output' => $import_output,
			]);
		}
	}

	public function track_post_insert($post_id) {
		update_post_meta($post_id, 'blocksy_demos_imported_post', true);
	}

	private function setup_sse_headers() {
		// Disable output buffering for SSE
		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');
		header('X-Accel-Buffering: no');

		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- required for long-running SSE import
		set_time_limit(0);

		// Send initial connection event
		$this->send_sse_event('connected', ['message' => 'SSE connection established']);
	}

	private function send_sse_event($event, $data) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SSE protocol output, not HTML.
		echo "event: {$event}\n";
		echo "data: " . wp_json_encode($data) . "\n\n";

		if (ob_get_level()) {
			ob_flush();
		}
		flush();
	}

	private function send_sse_status($message) {
		$this->send_sse_event('status', ['message' => $message]);
	}

	private function send_sse_complete($data) {
		$this->send_sse_event('complete', $data);
		exit;
	}

	private function send_sse_error($message) {
		$this->send_sse_event('error', ['message' => $message]);
		exit;
	}

	public function track_term_insert($term_id) {
		update_term_meta($term_id, 'blocksy_demos_imported_term', true);
	}

	public function clean_plugins_cache() {
		if (class_exists('\Elementor\Plugin')) {
			\Elementor\Plugin::$instance->posts_css_manager->clear_cache();
		}

		if (is_callable('FLBuilderModel::delete_asset_cache_for_all_posts')) {
			\FLBuilderModel::delete_asset_cache_for_all_posts();
		}
	}

	public function assign_pages_ids($demo, $builder) {
		$demo_content = Plugin::instance()->demo->fetch_single_demo([
			'demo' => $demo,
			'builder' => $builder,
			'field' => 'content'
		]);

		if (! isset($demo_content['pages_ids_options'])) {
			if ($this->is_ajax_request) {
				$this->send_sse_error(__("No pages to assign.", 'blocksy-companion'));
			} else {
				return new \WP_Error(
					'blocksy_demo_install_content_no_pages',
					__("No pages to assign.", 'blocksy-companion')
				);
			}
		}

		foreach ($demo_content['pages_ids_options'] as $option_id => $page_title) {
			// If no page title provided, skip.
			//
			// Otherwise, we might accidentally set options like 'woocommerce_shop_page_id'
			// to the first matching page found, even if the demo didn't intend to set it.
			// This would lead to unexpected behavior.
			// And Woo will drop that page in their migration tool if Woo is not active.
			if (empty($page_title)) {
				continue;
			}

			if (strpos($option_id, 'woocommerce') !== false) {
				if (! class_exists('WooCommerce')) {
					continue;
				}
			}

			$pages = get_posts([
				'post_type' => 'page',
				'title' => $page_title,
				'post_status' => 'publish',
				'numberposts' => 1
			]);

			$page = !empty($pages) ? $pages[0] : null;

			if (isset($page) && $page->ID) {
				update_option($option_id, $page->ID);
			}
		}
	}

	private function process_author_mapping($authors_arg, $author_data) {
		switch ($authors_arg) {
		// Create authors if they don't yet exist; maybe match on email or user_login
		case 'create':
			return $this->create_authors_for_mapping($author_data);
		// Skip any sort of author mapping
		case 'skip':
			return array();
		default:
			return new WP_Error( 'invalid-argument', "'authors' argument is invalid." );
		}
	}

	private function create_authors_for_mapping($author_data) {
		$author_mapping = [];

		foreach ($author_data as $author) {
			if (isset($author->user_email)) {
				$user = get_user_by('email', $author->user_email);

				if ($user instanceof WP_User) {
					$author_mapping[] = [
						'old_user_login' => $author->user_login,
						'new_user_login' => $user->user_login,
					];

					continue;
				}
			}

			$user = get_user_by('login', $author->user_login);

			if ($user instanceof WP_User) {
				$author_mapping[] = [
					'old_user_login' => $author->user_login,
					'new_user_login' => $user->user_login,
				];
				continue;
			}

			$user = array(
				'user_login' => '',
				'user_email' => '',
				'user_pass' => wp_generate_password(),
			);

			$user = array_merge($user, (array) $author);
			$user_id = wp_insert_user($user);

			if (is_wp_error($user_id)) {
				return $user_id;
			}

			$user = get_user_by( 'id', $user_id );
			$author_mapping[] = [
				'old_user_login' => $author->user_login,
				'new_user_login' => $user->user_login,
			];
		}

		return $author_mapping;
	}
}
