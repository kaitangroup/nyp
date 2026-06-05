<?php

add_action(
	'uael_before_product_loop_start',
	function ($args) {
		wc_set_loop_prop('name', 'ultimate_addons');
	}
);

add_filter(
	'woocommerce_product_loop_start',
	function ($content) {
		$attr = '';

		if (wc_get_loop_prop('name', 'default') !== 'ultimate_addons') {
			$hover_attr = '';
			$hover_value = 'none';

			$render_layout_config = blocksy_get_theme_mod(
				'woo_card_layout',
				[
					[
						'id' => 'product_image',
						'enabled' => true,
					],
				]
			);

			foreach ($render_layout_config as $layout) {
				if ($layout['id'] === 'product_image') {
					$hover_value = blocksy_akg(
						'product_image_hover',
						$layout,
						'none'
					);
				}
			}

			$other_attr = [
				'data-products' => blocksy_get_theme_mod('shop_cards_type', 'type-1')
			];

			// $alignment = '';

			// if ($shop_structure === 'type-1') {
			// 	$alignment = ' data-alignment="' . blocksy_get_theme_mod('shop_cards_alignment_1', 'left') . '"';
			// }

			if ($hover_value !== 'none') {
				$other_attr['data-hover'] = $hover_value;
			}

			if (function_exists('blocksy_quick_view_attr')) {
				$other_attr = array_merge(
					blocksy_quick_view_attr(),
					$other_attr
				);
			}

			if (is_archive() && is_customize_preview()) {
				$other_attr['data-shortcut'] = 'border:outside';
				$other_attr['data-shortcut-location'] = blocksy_first_level_deep_link('woo_categories');
			}

			$attr = blocksy_attr_to_html($other_attr);
		}

		return preg_replace(
			'/<ul class="products (.*)"/',
			'<ul class="products $1" ' . $attr,
			$content
		);
	}
);
