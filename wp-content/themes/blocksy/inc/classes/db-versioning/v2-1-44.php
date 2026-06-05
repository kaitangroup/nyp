<?php

namespace Blocksy\DbVersioning;

class V2144 {
	public function migrate() {
		$header_placements = get_theme_mod('header_placements', []);

		if (empty($header_placements) || ! isset($header_placements['sections'])) {
			return;
		}

		$made_changes = false;

		foreach ($header_placements['sections'] as $section_index => &$single_section) {
			if (! isset($single_section['items'])) {
				continue;
			}

			foreach ($single_section['items'] as $item_index => &$item_values) {
				if ($item_values['id'] !== 'search') {
					continue;
				}

				if (! isset($item_values['values']['search_thumb_radius'])) {
					continue;
				}

				$value = $item_values['values']['search_thumb_radius'];

				if (is_array($value) && isset($value['values'])) {
					// Flat ct-spacing format
					$item_values['values']['search_thumb_radius'] = intval($value['values'][0]['value']);
					$made_changes = true;
				}

				if (
					is_array($value)
					&&
					isset($value['desktop'])
					&&
					is_array($value['desktop'])
					&&
					isset($value['desktop']['values'])
				) {
					// Responsive wrapper around ct-spacing values
					$converted = [
						'desktop' => 10,
						'tablet' => 10,
						'mobile' => 10,
					];

					foreach (['desktop', 'tablet', 'mobile'] as $device) {
						if (isset($value[$device]['values'][0]['value'])) {
							$converted[$device] = intval($value[$device]['values'][0]['value']);
						}
					}

					$item_values['values']['search_thumb_radius'] = $converted;
					$made_changes = true;
				}
			}

			unset($item_values);
		}

		unset($single_section);

		if ($made_changes) {
			set_theme_mod('header_placements', $header_placements);
		}
	}
}
