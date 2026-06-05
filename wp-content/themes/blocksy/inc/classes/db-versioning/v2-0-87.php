<?php

namespace Blocksy\DbVersioning;

class V2087 {
	public function migrate() {
		$this->enable_extensions();
	}

	public function enable_extensions() {
		if (
			! function_exists('wc_get_attribute_taxonomies')
			||
			! class_exists('\Blocksy\Plugin')
			||
			! in_array(
				'woocommerce-extra',
				get_option('blocksy_active_extensions', [])
			)
		) {
			return;
		}

		if (! class_exists('\Blocksy\Extensions\WoocommerceExtra\Storage')) {
			return;
		}

		$storage = new \Blocksy\Extensions\WoocommerceExtra\Storage();
		$settings = $storage->get_settings();

		if (
			! isset($settings['features']['added-to-cart-popup'])
			||
			! $settings['features']['added-to-cart-popup']
		) {
			return;
		}

		if (
			get_theme_mod(
				'cart_popup_suggested_products',
				'__empty__'
			) !== '__empty__'
			||
			get_theme_mod('added_to_cart_popup_suggested_products', 'yes') === 'no'
		) {
			return;
		}
		
		$settings = update_option(
			'blocksy_ext_woocommerce_extra_settings',
			array_merge(
				$settings,
				[
					'features' => array_merge(
						$settings['features'],
						[
							'suggested-products' => true
						]
					)
				]
			)
		);

		remove_theme_mod('added_to_cart_popup_suggested_products');

		set_theme_mod('checkout_suggested_products', 'no');
		set_theme_mod('mini_cart_suggested_products', 'no');

		$migrate_options = [
			'suggested_products_visibility',
			'suggested_products_title_font',
			'suggested_products_title_color',
			'suggested_products_price_font',
			'suggested_products_price_color',
		];

		$theme_mods = [
			'suggested_products_source' => 'added_to_cart_popup_products_source',
			'suggested_products_columns' => 'added_to_cart_popup_products_columns',
			'suggested_products_number_of_items' => 'added_to_cart_popup_products_number_of_items',
			'suggested_products_type' => 'added_to_cart_popup_products_type',
			'suggested_products_autoplay_speed' => 'added_to_cart_popup_products_autoplay_speed',
		];

		$all_options = array_merge($theme_mods, array_combine($migrate_options, $migrate_options));

		foreach ($all_options as $key => $mod) {
			$value = get_theme_mod($mod, '__empty__');

			if ($value !== '__empty__') {
				remove_theme_mod($mod);

				$new_key = "cart_popup_$key";
				set_theme_mod($new_key, $value);
			}
		}

		blocksy_manager()->db->wipe_cache();
	}
}
