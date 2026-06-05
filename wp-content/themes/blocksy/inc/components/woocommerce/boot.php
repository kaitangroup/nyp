<?php

namespace Blocksy;

class WooCommerceBoot {
	public function __construct() {
		add_action('after_setup_theme', function () {
			add_theme_support(
				'woocommerce',
				[
					'gallery_thumbnail_image_width' => blocksy_get_theme_mod(
						'gallery_thumbnail_image_width',
						100
					)
				]
			);

			if (
				blocksy_get_theme_mod('has_product_single_lightbox', 'no') === 'yes'
				||
				is_customize_preview()
			) {
				add_theme_support('wc-product-gallery-lightbox');
			}

			if (
				blocksy_get_theme_mod('has_product_single_zoom', 'yes') === 'yes'
				||
				is_customize_preview()
			) {
				add_theme_support('wc-product-gallery-zoom');
			}
		});

		if (! wp_doing_ajax()) {
			add_filter('template_include', function ($template) {
				if (
					! blocksy_manager()->screen->uses_woo_default_template()
					||
					! blocksy_woocommerce_has_flexy_view()
				) {
					add_theme_support('wc-product-gallery-slider');
				}

				return $template;
			}, 900000009);
		} else {
			add_action('init', function () {
				if (
					! blocksy_manager()->screen->uses_woo_default_template()
					||
					! blocksy_woocommerce_has_flexy_view()
				) {
					add_theme_support('wc-product-gallery-slider');
				}
			});
		}

		add_filter('woocommerce_enqueue_styles', '__return_empty_array');

		add_action('wp_enqueue_scripts', function () {
			if (
				blocksy_manager()->screen->uses_woo_default_template()
				&&
				blocksy_woocommerce_has_flexy_view()
			) {
				wp_deregister_script('yith_wapo_color_label_frontend');
			}
		}, 100);

		add_action('wp_enqueue_scripts', function () {
			$render = new \Blocksy_Header_Builder_Render();

			if ($render->contains_item('cart') || is_customize_preview()) {
				wp_enqueue_script('wc-cart-fragments');
			}

			if (! function_exists('is_shop')) return;

			$theme = blocksy_get_wp_parent_theme();

			wp_enqueue_style(
				'ct-woocommerce-styles',
				get_template_directory_uri() . '/static/bundle/woocommerce.min.css',
				['ct-main-styles'],
				$theme->get('Version')
			);

			// wp_dequeue_style( 'wc-block-style' );
		});

		add_action(
			'blocksy:widgets_init',
			function ($sidebar_title_tag) {
				register_sidebar(
					[
						'name' => esc_html__('WooCommerce Sidebar', 'blocksy'),
						'id' => 'sidebar-woocommerce',
						'description' => esc_html__('Add widgets here.', 'blocksy'),
						'before_widget' => '<div class="ct-widget %2$s" id="%1$s">',
						'after_widget' => '</div>',
						'before_title' => '<' . $sidebar_title_tag . ' class="widget-title">',
						'after_title' => '</' . $sidebar_title_tag . '>',
					]
				);
			}
		);

		add_filter(
			'woocommerce_get_block_types',
			[$this, 'allow_product_collection_block_in_widgets'],
			100
		);

		add_action('enqueue_block_editor_assets', function () {
			$screen = get_current_screen();

			$relevant_screens = ['customize', 'widgets'];

			if (
				! $screen
				||
				! in_array($screen->id, $relevant_screens)
			) {
				return;
			}

			$wp_scripts = wp_scripts();

			$wp_scripts->remove('wp-edit-post');

			foreach ($wp_scripts->registered as $script) {
				foreach ($script->deps as $dep) {
					if ($dep === 'wp-edit-post') {
						$wp_scripts->remove($script->handle);
						break;
					}
				}
			}
		}, PHP_INT_MAX);
	}

	// Load WooCommerce Product Collection block inside Widgets page
	// Remove when we will release our Advanced Products block
	public function allow_product_collection_block_in_widgets($block_types) {
		global $pagenow;

		if (in_array($pagenow, ['widgets.php', 'customize.php'], true)) {
			if (class_exists('Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Controller')) {
				$block_types[] = 'ProductCollection\Controller';
				$block_types[] = 'ProductCollection\NoResults';
			} else {
				$block_types[] = 'ProductCollection';
			}

			// Enqueued to allow registering atomic Woo blocks, like
			// woocommerce/product-image, woocommerce/product-price, etc.
			//
			// These atomic blocks are referenced in the inner blocks template
			// of the ProductCollection block and cause errors in block editor
			// if not present.
			$block_types[] = 'AllProducts';

			// Enqueued to fix issue with ProductTemplate block in the inner block
			// template of the ProductCollection block.
			$block_types[] = 'ProductTemplate';
		}

		return $block_types;
	}
}
