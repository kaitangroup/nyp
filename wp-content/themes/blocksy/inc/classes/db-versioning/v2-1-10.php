<?php

namespace Blocksy\DbVersioning;

class V2110 {
	public function migrate() {
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
			! isset($settings['features']['free-shipping'])
			||
			! $settings['features']['free-shipping']
		) {
			return;
		}

		$woo_count_with_discount = get_theme_mod(
			'woo_count_with_discount',
			'__empty__'
		);

		if ($woo_count_with_discount === '__empty__') {
			return;
		}

		if ($woo_count_with_discount === 'exclude') {
			set_theme_mod('woo_count_with_discount', 'no');
		} else {
			set_theme_mod('woo_count_with_discount', 'yes');
		}
	}
}

