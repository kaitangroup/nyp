<?php

class Blocksy_Static_Css_Files {
	public function all_static_files() {
		$should_load_comments_css = (
			is_singular()
			&&
			!(
				function_exists('is_product')
				&&
				is_product()
			)
			&&
			(
				blocksy_has_comments()
				||
				is_customize_preview()
			)
		);

		$should_load_comments_css = apply_filters(
			'blocksy:static-files:ct-comments-styles',
			$should_load_comments_css
		);

		$should_load_sidebar = (
			blocksy_sidebar_position() !== 'none'
			||
			is_customize_preview()
			||
			(
				function_exists('is_woocommerce')
				&&
				is_woocommerce()
				&&
				(
					is_shop()
					||
					is_product_category()
					||
					is_product_tag()
					||
					is_product_taxonomy()
					||
					is_search()
				)
				&&
				blocksy_get_theme_mod('has_woo_offcanvas_filter', 'no') === 'yes'
			)
		);

		$should_load_featured_image_styles = (
			is_singular()
			&&
			blocksy_akg_or_customizer(
				'has_featured_image',
				blocksy_get_featured_image_source(),
				'no'
			) === 'yes'
		);

		$static_files = [
			'ct-main-styles' => [
				'url' => '/static/bundle/main.min.css'
			],

			'ct-admin-frontend-styles' => [
				'url' => '/static/bundle/admin-frontend.min.css',
				'enabled' => (
					current_user_can('manage_options')
					||
					current_user_can('edit_theme_options')
				)
			],

			'ct-page-title-styles' => [
				'url' => '/static/bundle/page-title.min.css',
				'enabled' => (
					is_customize_preview()
					||
					blocksy_get_page_title_source()
				)
			],

			'ct-main-rtl-styles' => [
				'url' => '/static/bundle/main-rtl.min.css',
				'enabled' => is_rtl()
			],

			'ct-sidebar-styles' => [
				'url' => '/static/bundle/sidebar.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_sidebar
			],

			'ct-comments-styles' => [
				'url' => '/static/bundle/comments.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_comments_css
			],

			'ct-author-box-styles' => [
				'url' => '/static/bundle/author-box.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => (
					is_singular()
					&&
					(
						blocksy_has_author_box()
						||
						is_customize_preview()
					)
				)
			],

			'ct-posts-nav-styles' => [
				'url' => '/static/bundle/posts-nav.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => (
					is_singular()
					&&
					(
						blocksy_has_post_nav()
						||
						is_customize_preview()
					)
				)
			],

			'ct-featured-image-styles' => [
				'url' => '/static/bundle/featured-image.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_featured_image_styles
			],

			// Integrations
			'ct-forminator-styles' => [
				'url' => '/static/bundle/forminator.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('Forminator')
			],

			'ct-getwid-styles' => [
				'url' => '/static/bundle/getwid.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('Getwid\Getwid')
			],

			'ct-elementor-styles' => [
				'url' => '/static/bundle/elementor-frontend.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => did_action('elementor/loaded')
			],

			'ct-elementor-woocommerce-styles' => [
				'url' => '/static/bundle/elementor-woocommerce-frontend.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => (
					did_action('elementor/loaded')
					&&
					function_exists('is_woocommerce')
				)
			],

			'ct-tutor-styles' => [
				'url' => '/static/bundle/tutor.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => function_exists('tutor_course_enrolled_lead_info')
			],

			'ct-tribe-events-styles' => [
				'url' => '/static/bundle/tribe-events.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('Tribe__Events__Main')
			],

			'ct-brizy-styles' => [
				'url' => '/static/bundle/brizy.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => function_exists('brizy_load')
			],

			'ct-jet-woo-builder-styles' => [
				'url' => '/static/bundle/jet-woo-builder.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('Jet_Woo_Builder')
			],

			'ct-beaver-styles' => [
				'url' => '/static/bundle/beaver.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('FLBuilderLoader')
			],

			'ct-divi-styles' => [
				'url' => '/static/bundle/divi.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('ET_Builder_Plugin')
			],

			'ct-cf-7-styles' => [
				'url' => '/static/bundle/cf-7.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => defined('WPCF7_VERSION')
			],

			'ct-stackable-styles' => [
				'url' => '/static/bundle/stackable.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => defined('STACKABLE_VERSION')
			],

			'ct-qubely-styles' => [
				'url' => '/static/bundle/qubely.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => defined('QUBELY_VERSION')
			],

			'ct-bbpress-styles' => [
				'url' => '/static/bundle/bbpress.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => function_exists('is_bbpress')
			],

			'ct-buddypress-styles' => [
				'url' => '/static/bundle/buddypress.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => function_exists('is_buddypress')
			],

			'ct-wpforms-styles' => [
				'url' => '/static/bundle/wpforms.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => defined('WPFORMS_VERSION')
			],

			'ct-dokan-styles' => [
				'url' => '/static/bundle/dokan.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('WeDevs_Dokan')
			],

			'ct-page-scroll-to-id-styles' => [
				'url' => '/static/bundle/page-scroll-to-id.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => class_exists('malihuPageScroll2id')
			],

			'ct-eventkoi-styles' => [
				'url' => '/static/bundle/eventkoi.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => (
					class_exists('\EventKoi\Init')
					&&
					(
						is_singular('event')
						||
						is_tax('event_cal')
					)
				)
			],
		];

		$static_files = array_merge(
			$static_files,
			$this->dynamic_static_files()
		);

		return apply_filters(
			'blocksy:static-files:all',
			$static_files
		);
	}

	public function dynamic_static_files($args = []) {
		$args = wp_parse_args($args, [
			'is_rendering_content' => is_singular()
		]);

		global $post;

		$prefix = blocksy_manager()->screen->get_prefix();

		$woo_extra_settings = get_option('blocksy_ext_woocommerce_extra_settings', [
			'features' => []
		]);

		$render = new \Blocksy_Header_Builder_Render();

		$is_elementor_preview = false;

		if (
			class_exists('Elementor\Plugin')
			&&
			(
				\Elementor\Plugin::$instance->preview->is_preview_mode()
				||
				\Elementor\Plugin::$instance->editor->is_edit_mode()
			)
		) {
			$is_elementor_preview = true;
		}

		$should_load_share_box = (
			is_singular()
			&&
			(
				blocksy_has_share_box()
				||
				is_customize_preview()
				||
				is_page(
					blocksy_get_theme_mod('woocommerce_wish_list_page', '__EMPTY__')
				)
				||
				(
					function_exists('blocksy_has_product_specific_layer')
					&&
					blocksy_has_product_specific_layer('product_sharebox')
				)
				||
				(
					function_exists('is_account_page')
					&&
					is_account_page()
				)
				||
				(
					$post
					&&
					has_shortcode($post->post_content, 'product_page')
				)
			)
		);

		$should_load_related_posts_styles = (
			is_singular()
			&&
			blocksy_get_theme_mod($prefix . '_has_related_posts', 'no') === 'yes'
		);

		$should_load_flexy_styles = false;

		if ($is_elementor_preview) {
			$should_load_flexy_styles = true;
		}

		return [
			'ct-woocommerce-cart-checkout-blocks' => [
				'url' => '/static/bundle/woocommerce-cart-checkout-blocks.min.css',
				'enabled' => (
					has_block('woocommerce/cart')
					||
					has_block('woocommerce/checkout')
				)
			],

			'ct-share-box-styles' => [
				'url' => '/static/bundle/share-box.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_share_box
			],

			'ct-related-posts-styles' => [
				'url' => '/static/bundle/related-posts.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_related_posts_styles
			],

			'ct-pagination-styles' => [
				'url' => '/static/bundle/pagination.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => false
			],

			'ct-entries-styles' => [
				'url' => '/static/bundle/entries.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => false
			],

			'ct-flexy-styles' => [
				'url' => '/static/bundle/flexy.min.css',
				'deps' => ['ct-main-styles'],
				'enabled' => $should_load_flexy_styles
			],
		];
	}

	public function enqueue_static_files($theme) {
		foreach ($this->all_static_files() as $id => $internal_file) {
			$file = wp_parse_args($internal_file, [
				'enabled' => true,
				'deps' => [],
				'url' => ''
			]);

			$file['url'] = get_template_directory_uri() . $file['url'];

			if (! $file['enabled']) {
				wp_register_style(
					$id,
					$file['url'],
					$file['deps'],
					$theme->get('Version')
				);

				continue;
			}

			wp_enqueue_style(
				$id,
				$file['url'],
				$file['deps'],
				$theme->get('Version')
			);
		}

		$this->check_for_patterns();
	}

	public function recheck_dynamic_styles($args = []) {
		foreach ($this->dynamic_static_files($args) as $id => $file) {
			$file = wp_parse_args($file, [
				'enabled' => true
			]);

			if ($file['enabled']) {
				wp_enqueue_style($id);
			}
		}
	}

	public function check_for_patterns() {
		add_filter('pre_render_block', function ($block_content, $block) {
			if ($block['blockName'] === 'core/block') {

				if (
					isset($block['attrs']['ref'])
					&&
					get_post($block['attrs']['ref'])
				) {

					global $post;
					$original_post = $post;

					$post = get_post($block['attrs']['ref']);
					setup_postdata($post);

					$this->recheck_dynamic_styles([
						'is_rendering_content' => true
					]);

					$post = $original_post;
					setup_postdata($post);
				}
			}

			return $block_content;
		}, 10, 2);
	}
}
