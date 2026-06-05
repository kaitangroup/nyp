<?php

if (! function_exists('blocksy_validate_color')) {
	function blocksy_validate_color($color) {
		/**
		 * Allow hex colors
		 */
		if (sanitize_hex_color($color)) {
			return $color;
		}

		/**
		 * Allow rgb* colors
		 */
		if (strpos($color, 'rgb') !== false) {
			return $color;
		}

		/**
		 * Allow var(--global) values
		 */
		if (strlen($color) > 2 && substr($color, 0, 6) === "var(--") {
			return $color;
		}

		// Allow CT_CSS_SKIP_RULE values
		if (strpos($color, 'CT_CSS_SKIP_RULE') !== false) {
			return $color;
		}

		return null;
	}
}

if (! function_exists('blocksy_validate_single_slider')) {
	function blocksy_validate_single_slider($value) {
		if (! intval($value) && intval($value) !== 0) {
			return null;
		}

		return true;
	}
}

function blocksy_sanitize_builder_value($option, $input) {
	if (! isset($input['sections'])) {
		return $input;
	}

	foreach ($input['sections'] as $key => $section) {
		if (! isset($section['items'])) {
			continue;
		}

		$html_fields = [
			'text' => ['header_text'],
			'copyright_text' => ['copyright_text']
		];

		foreach ($section['items'] as $item_key => $item) {
			if (! isset($item['id'])) {
				continue;
			}

			if (
				! isset($html_fields[$item['id']])
				||
				! isset($item['values'])
			) {
				continue;
			}

			foreach ($html_fields[$item['id']] as $html_field) {
				if (! isset($item['values'][$html_field])) {
					continue;
				}

				$item['values'][$html_field] = blocksy_sanitize_user_html(
					$item['values'][$html_field]
				);
			}

			$input['sections'][$key]['items'][$item_key] = $item;
		}
	}

	return $input;
}

if (! function_exists('blocksy_validate_for')) {
	function blocksy_validate_for($option, $input) {
		if (
			$option['type'] === 'ct-header-builder'
			||
			$option['type'] === 'ct-footer-builder'
		) {
			return blocksy_sanitize_builder_value($option, $input);
		}

		if (
			$option['type'] === 'ct-switch'
			||
			$option['type'] === 'ct-panel'
		) {
			$allowed_values = ['yes', 'no'];

			if (isset($option['behavior']) && $option['behavior'] === 'bool') {
				$allowed_values = [true, false];
			}

			if (in_array($input, $allowed_values, true)) {
				return $input;
			}

			return $option['value'];
		}

		if ($option['type'] === 'text' || $option['type'] === 'textarea') {
			return wp_kses_post($input);
		}

		if ($option['type'] === 'ct-color-picker') {
			if (! is_array($input)) {
				return $option['value'];
			}

			foreach ($input as $single_color) {
				if (! isset($single_color['color'])) {
					return $option['value'];
				}

				if (! blocksy_validate_color($single_color['color'])) {
					return $option['value'];
				}
			}
		}

		if (
			$option['type'] === 'ct-select'
			||
			$option['type'] === 'ct-image-picker'
			||
			$option['type'] === 'ct-radio'
		) {
			if (! in_array(
				$input,
				array_reduce(
					blocksy_ordered_keys($option['choices']),
					function ($current, $item) {
						return array_merge($current, [$item['key']]);
					},
					[]
				),
				true
			)) {
				return $option['value'];
			}
		}

		if (
			$option['type'] === 'ct-checkboxes'
			||
			$option['type'] === 'ct-visibility'
		) {
			foreach ($input as $key => $value) {
				if (
					! is_bool($value)
					||
					! in_array(
						$key,
						array_reduce(
							blocksy_ordered_keys($option['choices']),
							function ($current, $item) {
								return array_merge($current, [$item['key']]);
							},
							[]
						),
						true
					)
				) {
					return $option['value'];
				}
			}
		}

		if ($option['type'] === 'ct-number') {
			if (isset($option['responsive']) && $option['responsive']) {
				$values_to_validate = ['desktop', 'tablet', 'mobile'];

				foreach (blocksy_expand_responsive_value($input) as $single_key => $single_value) {
					if (! in_array($single_key, $values_to_validate)) {
						continue;
					}

					if (! is_numeric($single_value)) {
						return $option['value'];
					}
				}

				return $input;
			}

			if (! is_numeric($input)) {
				return $option['value'];
			}

			return max(
				intval($option['min']),
				min(intval($option['max']), intval($input))
			);
		}

		if ($option['type'] === 'ct-slider') {
			if (isset($option['responsive']) && $option['responsive']) {
				$values_to_validate = ['desktop', 'tablet', 'mobile'];

				foreach (blocksy_expand_responsive_value($input) as $single_key => $single_value) {
					if (! in_array($single_key, $values_to_validate)) {
						continue;
					}

					if (! blocksy_validate_single_slider($single_value)) {
						return $option['value'];
					}
				}

				return $input;
			}

			if (
				is_array($input)
				||
				! blocksy_validate_single_slider($input)
			) {
				return $option['value'];
			}
		}

		if ($option['type'] === 'ct-image-uploader') {
			if (
				!is_array($input)
				||
				! isset($input['attachment_id'])
				||
				! wp_attachment_is_image($input['attachment_id'])
			) {
				return $option['value'];
			}
		}

		return $input;
	}
}

if (! function_exists('blocksy_include_sanitizer')) {
	function blocksy_include_sanitizer($option) {
		if (! isset($option['setting'])) {
			return $option;
		}

		if (isset($option['setting']['sanitize_callback'])) {
			return $option;
		}

		$option['setting']['sanitize_callback'] = function ($input, $setting) use ($option) {
			return blocksy_validate_for($option, $input);
		};

		return $option;
	}
}
