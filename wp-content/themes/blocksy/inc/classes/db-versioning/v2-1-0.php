<?php

namespace Blocksy\DbVersioning;

class V210 {
	public function migrate() {
		$this->migrate_background_to_color([
			'background_id' => 'compare_modal_background',
			'default_color' => 'var(--theme-palette-color-8)'
		]);

		$this->migrate_background_to_color([
			'background_id' => 'compare_modal_backdrop',
			'default_color' => 'rgba(18, 21, 25, 0.8)'
		]);

		$this->migrate_background_to_color([
			'background_id' => 'product_compare_bar_background',
			'default_color' => \Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT')
		]);
	}

	private function migrate_background_to_color($args = []) {
		$args = wp_parse_args($args, [
			'background_id' => null,
			'color_id' => null,
			'default_color' => '#000000'
		]);

		// Same ID by default.
		if (! $args['color_id']) {
			$args['color_id'] = $args['background_id'];
		}

		$background = get_theme_mod($args['background_id'], '__empty__');

		if (
			$background === '__empty__'
			||
			! isset($background['backgroundColor'])
		) {
			return;
		}

		blocksy_print($background);

		$next_color = $args['default_color'];

		if (
			! empty($background['backgroundColor'])
			&&
			! empty($background['backgroundColor']['default'])
			&&
			! empty($background['backgroundColor']['default']['color'])
		) {
			$next_color = $background['backgroundColor']['default']['color'];
		}

		set_theme_mod($args['color_id'], [
			'default' => ['color' => $next_color]
		]);
	}
}
