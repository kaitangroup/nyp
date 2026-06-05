<?php

namespace Blocksy;

/*
add_action('blocksy:customizer:load:before', function () {
	$_REQUEST['wp_customize'] = 'on';
	_wp_customize_include();

	global $wp_customize;

	$wp_customize->wp_loaded();
});
 */

/**
 * Manages Blocksy theme extensions, tools, license activation, and starter site installation.
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
 *
 *     # List all available tools
 *     $ wp blocksy tool list
 *
 *     # Run a specific tool
 *     $ wp blocksy tool run tool-name
 *
 *     # List all available demos
 *     $ wp blocksy demo list
 *
 *     # Install a demo
 *     $ wp blocksy demo install demo-name
 *
 *     # Activate license
 *     $ wp blocksy license activate your-license-key
 *
 *     # Move all widgets to inactive area
 *     $ wp blocksy widgets drop
 */
class Cli {
	public function __construct() {
		\WP_CLI::add_command('blocksy', $this);

		new DemoCli();
		new ToolCli();
		new LicenseCli();
		new ExtensionCli();
		new WidgetsCli();
	}
}

