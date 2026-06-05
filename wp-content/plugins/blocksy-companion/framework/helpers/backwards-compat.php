<?php

if (! defined('ABSPATH')) {
	exit;
}

// Backwards compatibility — old theme versions may still call blc_* functions.
// These wrappers ensure they continue to work after the rename to blocksy_companion_*.
// Each wrapper guards with function_exists() so it's safe when the target is
// premium-only, extension-specific, or not yet loaded.
//
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

function blocksy_compat_call($fn, $args) {
	if (function_exists($fn)) {
		return $fn(...$args);
	}
}

if (! function_exists('blc_fs') && function_exists('blocksy_companion_fs')) {
	function blc_fs() {
		return blocksy_compat_call('blocksy_companion_fs', func_get_args());
	}
}

if (! function_exists('blc_fail_php_version')) {
	function blc_fail_php_version() {
		return blocksy_compat_call('blocksy_companion_fail_php_version', func_get_args());
	}
}

if (! function_exists('blc_fail_wp_version')) {
	function blc_fail_wp_version() {
		return blocksy_compat_call('blocksy_companion_fail_wp_version', func_get_args());
	}
}

if (! function_exists('blc_call_gutenberg_function')) {
	function blc_call_gutenberg_function() {
		return blocksy_compat_call('blocksy_companion_call_gutenberg_function', func_get_args());
	}
}

if (! function_exists('blc_get_gutenberg_class')) {
	function blc_get_gutenberg_class() {
		return blocksy_compat_call('blocksy_companion_get_gutenberg_class', func_get_args());
	}
}

if (! function_exists('blc_get_version')) {
	function blc_get_version() {
		return blocksy_compat_call('blocksy_companion_get_version', func_get_args());
	}
}

if (! function_exists('blc_get_capabilities')) {
	function blc_get_capabilities() {
		return blocksy_compat_call('blocksy_companion_get_capabilities', func_get_args());
	}
}

if (! function_exists('blc_theme_functions')) {
	function blc_theme_functions() {
		return blocksy_compat_call('blocksy_companion_theme_functions', func_get_args());
	}
}

if (! function_exists('blc_can_use_premium_code')) {
	function blc_can_use_premium_code() {
		return blocksy_compat_call('blocksy_companion_can_use_premium_code', func_get_args());
	}
}

if (! function_exists('blc_site_has_feature')) {
	function blc_site_has_feature() {
		return blocksy_compat_call('blocksy_companion_site_has_feature', func_get_args());
	}
}

if (! function_exists('blc_maybe_is_ssl')) {
	function blc_maybe_is_ssl() {
		return blocksy_compat_call('blocksy_companion_maybe_is_ssl', func_get_args());
	}
}

if (! function_exists('blc_normalize_site_url')) {
	function blc_normalize_site_url() {
		return blocksy_compat_call('blocksy_companion_normalize_site_url', func_get_args());
	}
}

if (! function_exists('blc_load_xml_file')) {
	function blc_load_xml_file() {
		return blocksy_compat_call('blocksy_companion_load_xml_file', func_get_args());
	}
}

if (! function_exists('blc_stringify_url')) {
	function blc_stringify_url() {
		return blocksy_compat_call('blocksy_companion_stringify_url', func_get_args());
	}
}

if (! function_exists('blc_is_xhr')) {
	function blc_is_xhr() {
		return blocksy_compat_call('blocksy_companion_is_xhr', func_get_args());
	}
}

if (! function_exists('blc_get_option_from_db')) {
	function blc_get_option_from_db() {
		return blocksy_compat_call('blocksy_companion_get_option_from_db', func_get_args());
	}
}

if (! function_exists('blc_get_network_option_from_db')) {
	function blc_get_network_option_from_db() {
		return blocksy_compat_call('blocksy_companion_get_network_option_from_db', func_get_args());
	}
}

if (! function_exists('blc_safe_sprintf')) {
	function blc_safe_sprintf() {
		return blocksy_compat_call('blocksy_companion_safe_sprintf', func_get_args());
	}
}

if (! function_exists('blc_request_remote_url')) {
	function blc_request_remote_url() {
		return blocksy_compat_call('blocksy_companion_request_remote_url', func_get_args());
	}
}

if (! function_exists('blc_get_jed_locale_data')) {
	function blc_get_jed_locale_data() {
		return blocksy_compat_call('blocksy_companion_get_jed_locale_data', func_get_args());
	}
}

if (! function_exists('blc_get_variables_from_file')) {
	function blc_get_variables_from_file() {
		return blocksy_compat_call('blocksy_companion_get_variables_from_file', func_get_args());
	}
}

if (! function_exists('blc_get_json_translation_files')) {
	function blc_get_json_translation_files() {
		return blocksy_compat_call('blocksy_companion_get_json_translation_files', func_get_args());
	}
}

if (! function_exists('blc_debug_log')) {
	function blc_debug_log() {
		return blocksy_compat_call('blocksy_companion_debug_log', func_get_args());
	}
}

if (! function_exists('blc_parse_attributes_string')) {
	function blc_parse_attributes_string() {
		return blocksy_compat_call('blocksy_companion_parse_attributes_string', func_get_args());
	}
}

if (! function_exists('blc_exts_get_preliminary_config')) {
	function blc_exts_get_preliminary_config() {
		return blocksy_compat_call('blocksy_companion_exts_get_preliminary_config', func_get_args());
	}
}

if (! function_exists('blc_get_ext')) {
	function blc_get_ext() {
		return blocksy_compat_call('blocksy_companion_get_ext', func_get_args());
	}
}

if (! function_exists('blc_get_product_ids_on_sale')) {
	function blc_get_product_ids_on_sale() {
		return blocksy_compat_call('blocksy_companion_get_product_ids_on_sale', func_get_args());
	}
}

if (! function_exists('blc_get_trending_posts')) {
	function blc_get_trending_posts() {
		return blocksy_compat_call('blocksy_companion_get_trending_posts', func_get_args());
	}
}

if (! function_exists('blc_get_trending_posts_value')) {
	function blc_get_trending_posts_value() {
		return blocksy_compat_call('blocksy_companion_get_trending_posts_value', func_get_args());
	}
}

if (! function_exists('blc_get_trending_block')) {
	function blc_get_trending_block() {
		return blocksy_compat_call('blocksy_companion_get_trending_block', func_get_args());
	}
}

if (! function_exists('blc_ext_newsletter_subscribe_form')) {
	function blc_ext_newsletter_subscribe_form() {
		return blocksy_compat_call('blocksy_companion_ext_newsletter_subscribe_form', func_get_args());
	}
}

if (! function_exists('blc_ext_newsletter_subscribe_output_form')) {
	function blc_ext_newsletter_subscribe_output_form() {
		return blocksy_compat_call('blocksy_companion_ext_newsletter_subscribe_output_form', func_get_args());
	}
}

if (! function_exists('blc_get_icon')) {
	function blc_get_icon() {
		return blocksy_compat_call('blocksy_companion_get_icon', func_get_args());
	}
}

if (! function_exists('blc_render_content_block')) {
	function blc_render_content_block() {
		return blocksy_compat_call('blocksy_companion_render_content_block', func_get_args());
	}
}

if (! function_exists('blc_get_content_block_that_matches')) {
	function blc_get_content_block_that_matches() {
		return blocksy_compat_call('blocksy_companion_get_content_block_that_matches', func_get_args());
	}
}

if (! function_exists('blc_get_content_blocks')) {
	function blc_get_content_blocks() {
		return blocksy_compat_call('blocksy_companion_get_content_blocks', func_get_args()) ?? [];
	}
}

if (! function_exists('blc_get_woo_offcanvas_trigger')) {
	function blc_get_woo_offcanvas_trigger() {
		return blocksy_compat_call('blocksy_companion_get_woo_offcanvas_trigger', func_get_args());
	}
}

if (! function_exists('blc_order_details_customer_before')) {
	function blc_order_details_customer_before() {
		return blocksy_compat_call('blocksy_companion_order_details_customer_before', func_get_args());
	}
}

if (! function_exists('blc_order_details_customer_after')) {
	function blc_order_details_customer_after() {
		return blocksy_compat_call('blocksy_companion_order_details_customer_after', func_get_args());
	}
}

if (! function_exists('blc_cpt_extra_filtering_output')) {
	function blc_cpt_extra_filtering_output() {
		return blocksy_compat_call('blocksy_companion_cpt_extra_filtering_output', func_get_args());
	}
}
