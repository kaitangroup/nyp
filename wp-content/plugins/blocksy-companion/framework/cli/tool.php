<?php

namespace Blocksy;

/**
 * Manages Blocksy theme tools.
 *
 * ## EXAMPLES
 *
 *     # List all available tools
 *     $ wp blocksy tool list
 *
 *     # Run a maintenance tool
 *     $ wp blocksy tool run regenerate_dynamic_css
 */
class ToolCli {
	public function __construct() {
		\WP_CLI::add_command('blocksy tool', $this);
	}

	/**
	 * List all available tools.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy tool list
	 *     wp blocksy tool list --format=json
	 *
	 * @subcommand list
	 */
	public function tool_list($args, $assoc_args) {
		$tools = $this->get_tools();
		$items = [];

		foreach ($tools as $id => $tool) {
			$items[] = [
				'id' => $id,
				'name' => $tool['name'],
			];
		}

		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

		if ($format === 'ids') {
			echo esc_html(implode(' ', array_column($items, 'id')));
			return;
		}

		\WP_CLI\Utils\format_items($format, $items, ['id', 'name']);
	}

	/**
	 * Run a maintenance tool.
	 *
	 * ## OPTIONS
	 *
	 * <tool>
	 * : The tool to run. Use 'wp blocksy tool list' to see available tools.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy tool run regenerate_dynamic_css
	 *     wp blocksy tool run regenerate_taxonomies_lookup
	 *
	 * @subcommand run
	 */
	public function tool_run($args, $assoc_args) {
		$tool = $args[0];
		$tools = $this->get_tools();

		if (! isset($tools[$tool])) {
			\WP_CLI::error("Unknown tool: {$tool}");
			return;
		}

		$tools[$tool]['callback']();
	}

	/**
	 * Get all registered tools.
	 *
	 * @return array
	 */
	private function get_tools() {
		return apply_filters('blocksy_cli_tools', [
			'regenerate_dynamic_css' => [
				'name' => 'Regenerate Dynamic CSS',
				'callback' => function() {
					do_action('blocksy:dynamic-css:refresh-caches');
					\WP_CLI::success('Dynamic CSS cache has been regenerated.');
				}
			]
		]);
	}
}
