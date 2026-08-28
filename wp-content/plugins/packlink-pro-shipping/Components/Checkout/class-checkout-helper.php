<?php
/**
 * Class Checkout_Helper
 *
 * @package Packlink\WooCommerce\Components\Checkout
 */

namespace Packlink\WooCommerce\Components\Checkout;

/**
 * Provides checkout page detection with fallback mechanisms.
 */
class Checkout_Helper {

	/**
	 * Determines if the current page is the WooCommerce checkout page.
	 *
	 * Falls back to page ID and content checks when is_checkout()
	 * returns false due to premature session initialization by
	 * third-party plugins (e.g., Power Coupons for WooCommerce).
	 *
	 * @return bool True if on checkout page.
	 */
	public static function is_packlink_checkout() {
		if ( is_checkout() ) {
			return true;
		}

		global $post;

		if ( ! $post ) {
			return false;
		}

		// Fallback 1: Check page ID against WooCommerce checkout setting.
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( $checkout_page_id > 0 && (int) $post->ID === $checkout_page_id ) {
			return true;
		}

		// Fallback 2: Check for WooCommerce checkout shortcode.
		if ( has_shortcode( $post->post_content, 'woocommerce_checkout' ) ) {
			return true;
		}

		// Fallback 3: Check for WooCommerce checkout block.
		if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $post ) ) {
			return true;
		}

		return false;
	}
}
