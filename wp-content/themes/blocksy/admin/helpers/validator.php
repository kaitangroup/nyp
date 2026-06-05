<?php
/**
 * Sanitization helpers for admin inputs.
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package   Blocksy
 */

if (! function_exists('blocksy_is_value_suspicious')) {
	/**
	 * Check if a string value contains suspicious patterns.
	 *
	 * @param string $value The value to check.
	 * @return bool True if suspicious, false otherwise.
	 */
	function blocksy_is_value_suspicious($value) {
		if (! is_string($value)) {
			return false;
		}

		$value = trim($value);

		// Null bytes can be used to bypass security checks
		if (strpos($value, "\0") !== false) {
			return true;
		}

		// Characters that could enable XSS or CSS injection
		$dangerous = ['<', '>'];

		foreach ($dangerous as $char) {
			if (strpos($value, $char) !== false) {
				return true;
			}
		}

		// Block serialized PHP object strings to prevent Object Injection
		if (is_serialized($value)) {
			return true;
		}

		return false;
	}
}

if (! function_exists('blocksy_sanitize_value_recursive')) {
	/**
	 * Recursively sanitize all string values in an array.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed Sanitized value.
	 */
	function blocksy_sanitize_value_recursive($value) {
		if (is_string($value)) {
			if (blocksy_is_value_suspicious($value)) {
				return '';
			}
			return $value;
		}

		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = blocksy_sanitize_value_recursive($item);
			}
		}

		return $value;
	}
}

if (! function_exists('blocksy_sanitize_post_meta_options')) {
	/**
	 * Sanitize post meta options by recursively checking all string values.
	 *
	 * Any string containing suspicious characters (< >) will be replaced
	 * with an empty string to prevent XSS attacks.
	 *
	 * Keys listed in the 'blocksy:post-meta:unfiltered-keys' filter are
	 * skipped when the current user has the 'unfiltered_html' capability.
	 *
	 * @param mixed $value The meta options to sanitize.
	 * @return mixed Sanitized meta options.
	 */
	function blocksy_sanitize_post_meta_options($value) {
		$unfiltered_keys = [];

		if (current_user_can('unfiltered_html')) {
			$unfiltered_keys = apply_filters(
				'blocksy:post-meta:unfiltered-keys',
				[]
			);
		}

		if (is_array($value) && ! empty($unfiltered_keys)) {
			$preserved = [];

			foreach ($unfiltered_keys as $key) {
				if (array_key_exists($key, $value)) {
					$preserved[$key] = $value[$key];
				}
			}

			$value = blocksy_sanitize_value_recursive($value);

			foreach ($preserved as $key => $val) {
				$value[$key] = $val;
			}

			return $value;
		}

		return blocksy_sanitize_value_recursive($value);
	}
}
