<?php

namespace Blocksy;

/**
 * Manages Blocksy theme extensions.
 *
 * ## EXAMPLES
 *
 *     # List all available extensions
 *     $ wp blocksy extension list
 *
 *     # Activate an extension
 *     $ wp blocksy extension activate custom-fonts
 *
 *     # Deactivate an extension
 *     $ wp blocksy extension deactivate custom-fonts
 */
class ExtensionCli {
	public function __construct() {
		\WP_CLI::add_command('blocksy extension', $this);
	}

	/**
	 * List all available extensions.
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
	 *     wp blocksy extension list
	 *     wp blocksy extension list --format=json
	 *
	 * @subcommand list
	 */
	public function extension_list($args, $assoc_args) {
		$extensions = Plugin::instance()->extensions->get_extensions([
			'require_config' => true
		]);

		$items = [];

		foreach ($extensions as $id => $extension) {
			$config = $extension['config'];
			$is_active = Plugin::instance()->extensions->get($id) !== null;

			$items[] = [
				'id' => $id,
				'name' => $config['name'] ?? $id,
				'description' => $config['description'] ?? '',
				'status' => $is_active ? 'active' : 'inactive',
			];
		}

		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

		if ($format === 'ids') {
			echo esc_html(implode(' ', array_column($items, 'id')));
			return;
		}

		\WP_CLI\Utils\format_items($format, $items, ['id', 'name', 'description', 'status']);
	}

	/**
	 * Activate an extension.
	 *
	 * ## OPTIONS
	 *
	 * <extension>
	 * : The extension ID to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy extension activate custom-fonts
	 *
	 * @subcommand activate
	 */
	public function extension_activate($args) {
		$extension_id = $args[0];

		$extensions = Plugin::instance()->extensions->get_extensions([
			'require_config' => true
		]);

		if (!isset($extensions[$extension_id])) {
			\WP_CLI::error("Extension '{$extension_id}' not found.");
			return;
		}

		if (Plugin::instance()->extensions->get($extension_id) !== null) {
			\WP_CLI::warning("Extension '{$extension_id}' is already active.");
			return;
		}

		Plugin::instance()->extensions->activate_extension($extension_id);
		\WP_CLI::success("Extension '{$extension_id}' activated successfully.");
	}

	/**
	 * Deactivate an extension.
	 *
	 * ## OPTIONS
	 *
	 * <extension>
	 * : The extension ID to deactivate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy extension deactivate custom-fonts
	 *
	 * @subcommand deactivate
	 */
	public function extension_deactivate($args) {
		$extension_id = $args[0];

		$extensions = Plugin::instance()->extensions->get_extensions([
			'require_config' => true
		]);

		if (!isset($extensions[$extension_id])) {
			\WP_CLI::error("Extension '{$extension_id}' not found.");
			return;
		}

		if (Plugin::instance()->extensions->get($extension_id) === null) {
			\WP_CLI::warning("Extension '{$extension_id}' is not active.");
			return;
		}

		Plugin::instance()->extensions->deactivate_extension($extension_id);
		\WP_CLI::success("Extension '{$extension_id}' deactivated successfully.");
	}
}
