<?php

/**
 * Generate a random ID.
 *
 * Keep function_exists() check for some time because Blocksy Companion 1.9
 * framework/helpers/blocksy-integration.php declares it again.
 */
if (! function_exists('blocksy_rand_md5')) {
	function blocksy_rand_md5() {
		return md5(time() . '-' . uniqid(wp_rand(), true) . '-' . wp_rand());
	}
}

/**
 * Extract a key from an array with defaults.
 *
 * @param string       $keys 'a/b/c' path.
 * @param array|object $array_or_object array to extract from.
 * @param null|mixed   $default_value defualt value.
 *
 * Keep function_exists() check for some time because Blocksy Companion 1.9
 * framework/helpers/blocksy-integration.php declares it again.
 */
if (! function_exists('blocksy_default_akg')) {
	function blocksy_default_akg($keys, $array_or_object, $default_value) {
		return blocksy_akg($keys, $array_or_object, $default_value);
	}
}

/**
 * Recursively find a key's value in array
 *
 * @param string       $keys 'a/b/c' path.
 * @param array|object $array_or_object array to extract from.
 * @param null|mixed   $default_value defualt value.
 *
 * @return null|mixed
 */
if (! function_exists('blocksy_akg')) {
	function blocksy_akg($keys, $array_or_object, $default_value = null) {
		if (! is_array($keys)) {
			$keys = explode('/', (string) $keys);
		}

		$array_or_object = $array_or_object;
		$key_or_property = array_shift($keys);

		if (is_null($key_or_property)) {
			return $default_value;
		}

		$is_object = is_object($array_or_object);

		if ($is_object) {
			if (! property_exists($array_or_object, $key_or_property)) {
				return $default_value;
			}
		} else {
			if (! is_array($array_or_object) || ! array_key_exists($key_or_property, $array_or_object)) {
				return $default_value;
			}
		}

		if (isset($keys[0])) { // not used count() for performance reasons.
			if ($is_object) {
				return blocksy_akg($keys, $array_or_object->{$key_or_property}, $default_value);
			} else {
				return blocksy_akg($keys, $array_or_object[$key_or_property], $default_value);
			}
		} else {
			if ($is_object) {
				return $array_or_object->{$key_or_property};
			} else {
				return $array_or_object[ $key_or_property ];
			}
		}
	}
}

function blocksy_akg_or_customizer($key, $source, $default = null) {
	$source = wp_parse_args(
		$source,
		[
			'prefix' => '',

			// customizer | array
			'strategy' => 'customizer',
		]
	);

	if ($source['strategy'] !== 'customizer' && !is_array($source['strategy'])) {
		throw new Error(
			'strategy wrong value provided. Array or customizer is required.'
		);
	}

	if (! empty($source['prefix'])) {
		$source['prefix'] .= '_';
	}

	if ($source['strategy'] === 'customizer') {
		return blocksy_get_theme_mod($source['prefix'] . $key, $default);
	}

	return blocksy_akg($source['prefix'] . $key, $source['strategy'], $default);
}

// When adding new migration, also implement same key in:
// - static/js/options/containers/MigrateValues.js
function blocksy_migrate_values($values, $args = []) {
	$args = wp_parse_args($args, [
		'migrations' => []
	]);

	$new_value = $values;

	foreach ($args['migrations'] as $migration) {
		if ($migration === 'popups_new_close_actions') {
			if (
				isset($new_value['close_button_type'])
				&&
				$new_value['close_button_type'] === 'none'
			) {
				$new_value['close_button_type'] = 'outside';
				$new_value['popup_close_button'] = 'no';
			}

			if (isset($new_value['popup_additional_close_strategy'])) {
				$new_value['popup_custom_close'] = 'yes';
				$new_value['popup_custom_close_strategy'] = $new_value['popup_additional_close_strategy'];

				if (isset($new_value['aditional_close_button_click_selector'])) {
					$new_value['popup_custom_close_button_selector'] = $new_value['aditional_close_button_click_selector'];
				}

				if (isset($new_value['popup_additional_close_submit_delay'])) {
					$new_value['popup_custom_close_action_delay'] = $new_value['popup_additional_close_submit_delay'];
				}

				unset($new_value['popup_additional_close_strategy']);
				unset($new_value['aditional_close_button_click_selector']);
				unset($new_value['popup_additional_close_submit_delay']);
			}
		}
	}

	return $new_value;
}
