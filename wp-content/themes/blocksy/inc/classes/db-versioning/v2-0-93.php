<?php

namespace Blocksy\DbVersioning;

class V2093 {
	public function migrate() {
		$this->migrate_color_mode_switch_and_wp_rocket();
	}

	public function migrate_color_mode_switch_and_wp_rocket() {
		if (
			! class_exists('\Blocksy\Plugin')
			||
			! in_array('color-mode-switch', get_option('blocksy_active_extensions', []))
			||
			! function_exists('rocket_clean_domain')
		) {
			return;
		}

		add_filter('rocket_cache_dynamic_cookies', function($cookies) {
			$cookies[] = 'blocksy_current_theme';
			return $cookies;
		});

		// Update the WP Rocket rules on the .htaccess file.
		flush_rocket_htaccess();

		// Regenerate the config file.
		rocket_generate_config_file();

		// Clear WP Rocket cache.
		rocket_clean_domain();
	}
}

