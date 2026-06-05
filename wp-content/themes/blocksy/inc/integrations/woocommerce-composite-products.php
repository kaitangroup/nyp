<?php

if (! function_exists('wc_cp_add_to_cart_after_summary')) {
    return;
}

remove_action('woocommerce_after_single_product_summary', 'wc_cp_add_to_cart_after_summary', -1000);
add_action('woocommerce_after_single_product_summary', 'wc_cp_add_to_cart_after_summary', 2);

add_filter(
	'woocommerce_composite_form_wrapper_classes', 
	function($classes, $product) {
		$classes[] = 'is-width-constrained';
		return $classes;
	},
	10, 2
);