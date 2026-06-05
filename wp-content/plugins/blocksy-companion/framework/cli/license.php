<?php

namespace Blocksy;

/**
 * Manages Blocksy theme license.
 *
 * ## EXAMPLES
 *
 *     # Activate license
 *     $ wp blocksy license activate your-license-key
 */
class LicenseCli {
	public function __construct() {
		\WP_CLI::add_command('blocksy license', $this);
	}

	/**
	 * Activate a license key.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The license key to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp blocksy license activate your-license-key
	 *
	 * @subcommand activate
	 */
	public function license_activate($args, $assoc_args) {
		$fs = blocksy_companion_fs();

		if (empty($fs)) {
			\WP_CLI::error('Freemius instance not found.');
			return;
		}

		if (false === $fs->has_api_connectivity()) {
			\WP_CLI::error('No API connectivity.');
			return;
		}

		if ($fs->is_registered()) {
			\WP_CLI::warning('The user is already registered with Freemius.');
		}

		$key = $args[0];

		try {
			$next_page = $fs->activate_migrated_license($key);
		} catch (Exception $e) {
			\WP_CLI::error('Error: ' . $e->getMessage());
			return;
		}

		if ($fs->can_use_premium_code()) {
			\WP_CLI::success('License key activated successfully.');
		} else {
			\WP_CLI::error('License key activation failed.');
		}
	}
}
