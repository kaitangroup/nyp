<?php

namespace Blocksy;

/**
 * Manages Blocksy theme widgets.
 *
 * ## EXAMPLES
 *
 *     # Move all widgets to inactive area
 *     $ wp blocksy widgets drop
 */
class WidgetsCli {
	public function __construct() {
		\WP_CLI::add_command('blocksy widgets', $this);
	}

	/**
	 * Move all widgets to the inactive widgets area.
	 *
	 * ## OPTIONS
	 *
	 * <none>
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy widgets drop
	 *
	 * @when after_wp_load
	 *
	 * @subcommand drop
	 */
	public function drop_widgets($args, $assoc_args) {
		$sidebars_widgets = get_option('sidebars_widgets', []);

		if (! isset($sidebars_widgets['wp_inactive_widgets'])) {
			$sidebars_widgets['wp_inactive_widgets'] = [];
		}

		foreach ($sidebars_widgets as $sidebar_id => $widgets) {
			if (! $widgets) continue;
			if ($sidebar_id === 'wp_inactive_widgets') {
				continue;
			}

			if ($sidebar_id === 'array_version') {
				continue;
			}

			foreach ($widgets as $widget_id) {
				$sidebars_widgets['wp_inactive_widgets'][] = $widget_id;
			}

			$sidebars_widgets[$sidebar_id] = [];
		}

		update_option('sidebars_widgets', $sidebars_widgets);
		unset($sidebars_widgets['array_version']);

		set_theme_mod('sidebars_widgets', [
			'time' => time(),
			'data' => $sidebars_widgets
		]);
	}
}
