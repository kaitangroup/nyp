<?php

namespace Blocksy;

class WooCommerceCart {
	public function __construct() {
		// Remove cross-sells from the cart page. We will call this function in cart/cart.php
		// template that we override in templates.
		remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

		add_action('elementor/widget/before_render_content', function($widget) {
			if (! class_exists('ElementorPro\Modules\Woocommerce\Widgets\Cart')) {
				return;
			}

			if ($widget instanceof \ElementorPro\Modules\Woocommerce\Widgets\Cart) {
				global $ct_skip_cart;
				$ct_skip_cart = true;
			}
		}, 10 , 1);

		add_filter('wc_get_template', function ($template, $template_name, $args, $template_path, $default_path) {
			if ($template_name !== 'cart/cart.php') {
				return $template;
			}

			global $ct_skip_cart;

			if ($ct_skip_cart) {
				$default_path = \WC()->plugin_path() . '/templates/';
				return $default_path . $template_name;
			}

			return $template;
		}, 10, 5);

		// Case #1 - WC()->cart->needs_shipping() && WC()->cart->show_shipping()
		$this->handle_cart_shipping_template();

		// Case #2 - WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' )
		$this->handle_cart_totals_template();
	}

	private function handle_cart_shipping_template() {
		add_action(
			'woocommerce_before_template_part',
			function ($template_name, $template_path, $located, $args) {
				if ($template_name !== 'cart/cart-shipping.php') {
					return;
				}

				ob_start();
			},
			1,
			4
		);

		add_action(
			'woocommerce_after_template_part',
			function ($template_name, $template_path, $located, $args) {
				if ($template_name !== 'cart/cart-shipping.php') {
					return;
				}

				$result = ob_get_clean();

				echo $this->replace_shipping_row($result);
			},
			1,
			4
		);
	}

	private function handle_cart_totals_template() {
		add_action(
			'woocommerce_before_template_part',
			function ($template_name, $template_path, $located, $args) {
				if ($template_name !== 'cart/cart-totals.php') {
					return;
				}

				ob_start();
			},
			1,
			4
		);

		add_action(
			'woocommerce_after_template_part',
			function ($template_name, $template_path, $located, $args) {
				if ($template_name !== 'cart/cart-totals.php') {
					return;
				}

				$result = ob_get_clean();

				if (
					\WC()->cart->needs_shipping()
					&&
					'yes' === get_option('woocommerce_enable_shipping_calc')
					&&
					! \WC()->cart->show_shipping()
				) {
					$result = preg_replace_callback(
						'/<tr class="shipping">(.+?)<\/tr>/s',
						function ($matches) {
							return '<tr class="shipping">' . $this->replace_shipping_row($matches[1]) . '</tr>';
						},
						$result
					);
				}

				echo $result;
			},
			1,
			4
		);
	}

	private function replace_shipping_row($html) {
		// extract heading from th
		$heading = '';

		if (preg_match('/<th>(.+?)<\/th>/s', $html, $matches)) {
			$heading = $matches[1];
		}

		// drop the th column
		$html = preg_replace(
			'/<th>.+?<\/th>/s',
			'',
			$html
		);

		// add heading, colspan and remove data-title
		return preg_replace(
			'/<td data-title="[^"]*">/s',
			'<td colspan="2">' . blocksy_html_tag(
				'div',
				[
					'class' => 'ct-shipping-heading'
				],
				$heading
			),
			$html
		);
	}
}
