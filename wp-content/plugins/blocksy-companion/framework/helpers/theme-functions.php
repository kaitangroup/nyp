<?php

namespace Blocksy;

// Mechanism for safely calling functions from the Blocksy theme.
// In some cases, these functions can be not defined just yet, so we have to
// handle that gracefully.
//
// The caller of this class should be prepared to handle `null` return values.
//
// For the blocksy_get_theme_mod() function, the special handling of the null
// value is not necessary.
//
// Right now, these functions must be protected with this proxy:
//
// - blocksy_get_theme_mod()
// - blocksy_manager()
// - blocksy_get_search_post_type()
// - blocksy_has_dynamic_css_in_frontend()
// - blocksy_theme_get_dynamic_styles()
// - blocksy_woo_has_ajax_add_to_cart()
// - blocksy_has_product_specific_layer()
// - blocksy_get_special_post_id()
// - blocksy_get_taxonomy_options()
// - blocksy_get_terms()
// - blocksy_flexy_pills()
// - blocksy_get_post_options()
// - blocksy_sanitize_post_meta_options()
//
// If more functions will be called earlier than `after_setup_theme`, they
// should be added here and should be only called through this proxy object.
//
// A `null` return means the value was never processed, so callers that pass
// data through a theme helper before storing it must treat `null` as "drop
// the value", never as "store what I had". blocksy_sanitize_post_meta_options()
// is the case that matters today: it guards a write path, so falling back to
// the unsanitized input would defeat the sanitizer entirely.
class ThemeFunctions {
	public static $NON_EXISTING_FUNCTION = null;

	public function __call($name, $arguments) {
		if (function_exists($name)) {
			return call_user_func_array($name, $arguments);
		}

		ob_start();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_print_backtrace
		debug_print_backtrace();
		$backtrace = ob_get_clean();

		blocksy_companion_debug_log('ThemeFunctions->__call : missing function', [
			'function_name' => $name,
			'is_cli' => defined('WP_CLI') && WP_CLI ? 'yes' : 'no',

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'current_script' => $_SERVER['SCRIPT_FILENAME'],

			'backtrace' => $backtrace,

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'request' => $_REQUEST
		]);

		if ($name === 'blocksy_has_dynamic_css_in_frontend') {
			return false;
		}

		if ($name === 'blocksy_get_search_post_type') {
			return [];
		}

		// Functions whose default value is one of their arguments, keyed by
		// that argument's index. Other helpers are not handled like this and
		// the caller is supposed to handle the `null` return value.
		$functions_with_default = [
			'blocksy_get_theme_mod' => 1,
		];

		if (isset($functions_with_default[$name])) {
			$default_index = $functions_with_default[$name];

			if (count($arguments) > $default_index) {
				return $arguments[$default_index];
			}
		}

		// Every other function should handle the special case of the `null`.
		return self::$NON_EXISTING_FUNCTION;
	}
}
