<?php

class Blocksy_Manager {
	public static $instance = null;

	public $db = null;
	public $db_versioning = null;

	public $builder = null;

	public $header_builder = null;
	public $footer_builder = null;

	public $post_types = null;

	public $screen = null;

	public $dynamic_css = null;
	public $dynamic_styles_descriptor = null;
	public $woocommerce = null;
	public $colors = null;
	public $entity_id_picker = null;

	public $archive = null;

	private $hooks = null;

	private $current_template = null;

	private $scripts_enqueued = null;


	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_current_template() {
		if (!$this->current_template) {
			// return apply_filters('template_include', '__DEFAULT__');
		}

		return $this->current_template;
	}

	private function __construct() {
		$this->early_init();
	}

	private function early_init() {
		$this->register_autoloader();

		$this->db = new \Blocksy\Database();
		$this->db_versioning = new \Blocksy\DbVersioning();

		$this->builder = new Blocksy_Customizer_Builder();

		$this->header_builder = new Blocksy_Header_Builder();
		$this->footer_builder = new Blocksy_Footer_Builder();

		$this->post_types = new \Blocksy\CustomPostTypes();
		$this->screen = new Blocksy_Screen_Manager();
		$this->colors = new \Blocksy\Colors();

		$this->archive = new \Blocksy\ArchiveLogic();

		$breadcrumbs = new \Blocksy\BreadcrumbsBuilder();
		$breadcrumbs->mount_shortcode();

		new \Blocksy\SearchModifications();
		new \Blocksy\ThemePatterns();

		$this->entity_id_picker = new \Blocksy\EntityIdPicker();

		$this->boot_woocommerce_integration();

		$this->dynamic_css = new \Blocksy\ThemeDynamicCss();

		$i18n_manager = new Blocksy_Translations_Manager();
		$i18n_manager->init();

		new \Blocksy\BlocksFallback();

		add_action('customize_save_after', function () {
			$i18n_manager = new Blocksy_Translations_Manager();
			$i18n_manager->register_wpml_translation_keys();
		});

		add_action('customize_save', function ($obj) {
			if (! $obj) {
				return;
			}

			$header_placements = $obj->get_setting('header_placements');

			if ($header_placements) {
				$current_value = $header_placements->post_value();

				if ($current_value) {
					unset($current_value['__forced_static_header__']);
					unset($current_value['__should_refresh_item__']);
					unset($current_value['__should_refresh__']);

					foreach ($current_value as $key => $value) {
						if (floatval($key)) {
							unset($current_value[$key]);
						}
					}

					$header_placements->manager->set_post_value(
						'header_placements',
						$current_value
					);
				}
			}

			$footer_placements = $obj->get_setting('footer_placements');

			if ($footer_placements) {
				$current_value = $footer_placements->post_value();

				if ($current_value) {
					unset($current_value['__forced_static_footer__']);
					unset($current_value['__should_refresh__']);
					unset($current_value['__should_refresh_item__']);

					foreach ($current_value as $key => $value) {
						if (floatval($key)) {
							unset($current_value[$key]);
						}
					}

					$footer_placements->manager->set_post_value(
						'footer_placements',
						$current_value
					);
				}
			}
		});

		// Wipe any caches that might have been computed before the WP action.
		// These are usually custom the_content triggers that are ran on demand
		// by various SEO plugins -- usually in wp_head action.
		add_action(
			'wp',
			function () {
				$this->screen->wipe_caches();
			},
			PHP_INT_MAX
		);

		add_filter('block_parser_class', function () {
			return 'Blocksy_WP_Block_Parser';
		});

		add_filter('template_include', function ($template) {
			$this->current_template = $template;
			return $template;
		}, 900000000);

		// WP 6.9 feature that loads core block assets separately.
		// Fixed in WP 6.9.1-RC1, so we only need this workaround for earlier versions.
		global $wp_version;

		if (version_compare($wp_version, '6.9.1-RC1', '<')) {
			add_filter('should_load_separate_core_block_assets', '__return_false');
		}

		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 10);

		add_action(
			'wp_head',
			function () {
				if (! blocksy_has_dynamic_css_in_frontend()) {
					return;
				}

				$this->dynamic_css->load_frontend_css([
					'descriptor' => $this->dynamic_styles_descriptor
				]);
			},
			10
		);
	}

	public function register_autoloader() {
		require get_template_directory() . '/inc/classes/autoload.php';
		\Blocksy\ThemeAutoloader::run();
	}

	public function enqueue_scripts() {
		if ($this->scripts_enqueued) {
			return;
		}

		$this->scripts_enqueued = true;

		$theme = blocksy_get_wp_parent_theme();

		$m = new \Blocksy\FontsManager();

		$this->dynamic_styles_descriptor = $this
			->dynamic_css
			->get_dynamic_styles_descriptor();

		$m->load_dynamic_google_fonts($this->dynamic_styles_descriptor['google_fonts']);

		$static_files = new Blocksy_Static_Css_Files();
		$static_files->enqueue_static_files($theme);

		wp_register_script(
			'ct-events',
			get_template_directory_uri() . '/static/bundle/events.js',
			[],
			$theme->get('Version'),
			true
		);

		wp_enqueue_script(
			'ct-scripts',
			get_template_directory_uri() . '/static/bundle/main.js',
			[],
			$theme->get('Version'),
			true
		);

		$data = apply_filters('blocksy:general:ct-scripts-localizations', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'public_url' => blocksy_cdn_url(
				get_template_directory_uri() . '/static/bundle/'
			),
			'rest_url' => get_rest_url(),
			'search_url' => get_search_link('QUERY_STRING'),
			'show_more_text' => __('Show more', 'blocksy'),
			'more_text' => __('More', 'blocksy'),
			'search_live_results' => __('Search results', 'blocksy'),
			'search_live_no_results' => __('No results', 'blocksy'),
			'search_live_results_closed' => __('Search results closed.', 'blocksy'),

			'search_live_no_result' => __('No results', 'blocksy'),
			'search_live_one_result' => _n(
				'You got %s result. Please press Tab to select it.',
				'You got %s results. Please press Tab to select one.',
				1,
				'blocksy'
			),
			'search_live_many_results' => _n(
				'You got %s result. Please press Tab to select it.',
				'You got %s results. Please press Tab to select one.',
				5,
				'blocksy'
			),

			'search_live_stock_status_texts' => [
				'instock' => __('In stock', 'blocksy'),
				'outofstock' => __('Out of stock', 'blocksy'),
			],

			'clipboard_copied' => __('Copied!', 'blocksy'),
			'clipboard_failed' => __('Failed to Copy', 'blocksy'),

			'expand_submenu' => __('Expand dropdown menu', 'blocksy'),
			'collapse_submenu' => __('Collapse dropdown menu', 'blocksy'),

			'dynamic_js_chunks' => blocksy_manager()->get_dynamic_js_chunks(),

			'dynamic_styles' => [
				'lazy_load' => add_query_arg(
					'ver',
					$theme->get('Version'),
					blocksy_cdn_url(
						get_template_directory_uri() . '/static/bundle/non-critical-styles.min.css'
					)
				),

				'search_lazy' => add_query_arg(
					'ver',
					$theme->get('Version'),
					blocksy_cdn_url(
						get_template_directory_uri() . '/static/bundle/non-critical-search-styles.min.css'
					)
				),

				'back_to_top' => add_query_arg(
					'ver',
					$theme->get('Version'),
					blocksy_cdn_url(
						get_template_directory_uri() . '/static/bundle/back-to-top.min.css'
					)
				)
			],

			'dynamic_styles_selectors' => [
				[
					'selector' => '.ct-header-cart, #woo-cart-panel',
					'url' => add_query_arg(
						'ver',
						$theme->get('Version'),
						blocksy_cdn_url(
							get_template_directory_uri() . '/static/bundle/cart-header-element-lazy.min.css'
						)
					)
				],

				[
					'selector' => '.flexy',
					'url' => add_query_arg(
						'ver',
						$theme->get('Version'),
						blocksy_cdn_url(
							get_template_directory_uri() . '/static/bundle/flexy.min.css'
						)
					),
				],

				[
					'selector' => '.ct-pagination',
					'url' => add_query_arg(
						'ver',
						$theme->get('Version'),
						blocksy_cdn_url(
							get_template_directory_uri() . '/static/bundle/pagination.min.css'
						)
					),
				]
			]
		]);

		foreach ($data['dynamic_styles_selectors'] as $dynamic_style_index => $dynamic_style) {
			if (strpos($dynamic_style['url'], 'ver=') !== false) {
				continue;
			}

			$data['dynamic_styles_selectors'][$dynamic_style_index]['url'] = add_query_arg(
				'ver',
				$theme->get('Version'),
				$dynamic_style['url']
			);
		}

		$maybe_current_language = blocksy_get_current_language('slug');

		if ($maybe_current_language !== '__NOT_KNOWN__') {
			$data['lang'] = $maybe_current_language;
		}

		if (is_customize_preview()) {
			$data['customizer_sync'] = blocksy_customizer_sync_data();
		}

		wp_localize_script(
			'ct-scripts',
			'ct_localizations',
			$data
		);

		if (defined('WP_DEBUG') && WP_DEBUG) {
			wp_localize_script(
				'ct-scripts',
				'WP_DEBUG',
				['debug' => true]
			);
		}

		if (is_singular() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

		do_action('blocksy:frontend:scripts-enqueued');
	}

	public function get_dynamic_js_chunks() {
		$all_chunks = apply_filters(
			'blocksy:frontend:dynamic-js-chunks',
			[]
		);

		global $wp_scripts;

		$theme = blocksy_get_wp_parent_theme();
		$version = $theme->get('Version');

		foreach ($all_chunks as $index => $chunk) {
			if (isset($chunk['version'])) {
				$version = $chunk['version'];
			}

			$all_chunks[$index]['url'] = add_query_arg(
				'ver',
				$version,
				$chunk['url']
			);

			if (! isset($chunk['deps'])) {
				continue;
			}

			$deps_data = [];

			foreach ($chunk['deps'] as $dep_id) {
				if (! isset($wp_scripts->registered[$dep_id])) {
					continue;
				}

				$src = $wp_scripts->registered[$dep_id]->src;

				// Follow approach from do_item() in /wp-includes/class-wp-scripts.php
				if (
					! preg_match('|^(https?:)?//|', $src)
					&&
					! (
						$wp_scripts->content_url
						&&
						str_starts_with($src, $wp_scripts->content_url)
					)
				) {
					$src = $wp_scripts->base_url . $src;
				}

				$deps_data[$dep_id] = $src;
			}

			$all_chunks[$index]['deps_data'] = $deps_data;
		}

		return $all_chunks;
	}

	public function get_prefix_title_actions($args = []) {
		$args = wp_parse_args($args, [
			'prefix' => '',
			'areas' => []
		]);

		return apply_filters(
			'blocksy:options:prefix-global-actions',
			[],
			$args
		);
	}

	public function get_conditions_overrides() {
		$shop_cards_type = blocksy_get_theme_mod('shop_cards_type', 'type-1');

		if ($shop_cards_type !== 'type-1' && $shop_cards_type !== 'type-2') {
			$shop_cards_type = 'type-1';
		}

		return apply_filters(
			'blocksy:options:conditions:overrides',
			[
				'product_view_type' => 'default-gallery',
				'shop_cards_type' => $shop_cards_type,
			]
		);
	}

	public function get_hooks() {
		if (! $this->hooks) {
			$this->hooks = new \Blocksy\WpHooksManager();
		}

		return $this->hooks;
	}

	public function boot_woocommerce_integration() {
		if (class_exists('WooCommerce')) {
			$this->woocommerce = new \Blocksy\WooCommerce();
		}

		new \Blocksy\WooDefaultPages();
	}
}

function blocksy_manager() {
	return Blocksy_Manager::instance();
}
