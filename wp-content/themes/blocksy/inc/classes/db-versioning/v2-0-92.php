<?php

namespace Blocksy\DbVersioning;

class V2092 {
	public function migrate() {
		$this->copy_blog_pagination_settings_to_search();
	}

	public function copy_blog_pagination_settings_to_search() {
		$reveal_options = blocksy_get_options('general/cards-reveal-effect', [
			'prefix' => 'blog',
		]);

		$pagination_options = blocksy_get_options('general/pagination', [
			'prefix' => 'blog',
		]);

		$options_to_copy = array_merge(
			$this->get_options_ids($reveal_options),
			$this->get_options_ids($pagination_options)
		);

		$archive_prefixes = blocksy_manager()->screen->get_archive_prefixes([
			'has_search' => true,
			'has_woocommerce' => true,
			'has_blog' => false
		]);

		foreach ($archive_prefixes as $archive_prefix) {
			$options_to_copy = $this->get_options_ids($pagination_options);

			if ($archive_prefix === 'search') {
				$options_to_copy = array_merge(
					$this->get_options_ids($reveal_options),
					$this->get_options_ids($pagination_options)
				);
			}

			foreach ($options_to_copy as $option_id) {
				if (strpos($option_id, 'blog_') !== 0) {
					continue;
				}

				$option_value = get_theme_mod($option_id, '__default__');

				// blog value was not set
				if ($option_value === '__default__') {
					continue;
				}

				$new_option_id = implode('_', [
					$archive_prefix,
					ltrim($option_id, 'blog_')
				]);

				$new_option_value = get_theme_mod($new_option_id, '__default__');

				// local value was already set
				if ($new_option_value !== '__default__') {
					continue;
				}

				set_theme_mod($new_option_id, $option_value);
			}
		}
	}

	private function get_options_ids($options) {
		$collected_options = [];

		blocksy_collect_options(
			$collected_options,
			$options,
			[
				'limit_option_types' => false,
				'limit_level' => 1,
				'include_container_types' => true,
				'info_wrapper' => true,
			]
		);

		$options_to_copy = [];

		foreach ($collected_options as &$option) {
			if ('container' === $option['group']) {
				$options_to_copy = array_merge(
					$options_to_copy,
					$this->get_options_ids($option['option']['options'])
				);
			}

			if ('option' === $option['group']) {
				if (isset($option['option']['inner-options'])) {
					$options_to_copy = array_merge(
						$options_to_copy,
						$this->get_options_ids($option['option']['inner-options'])
					);
				}

				$options_to_copy[] = $option['id'];
			}
		}

		return $options_to_copy;
	}
}

