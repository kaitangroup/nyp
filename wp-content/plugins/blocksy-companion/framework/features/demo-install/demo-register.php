<?php

namespace Blocksy;

class DemoInstallRegisterDemo {
	public function register() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_demo_name = isset($_REQUEST['demo_name']) ? sanitize_text_field(wp_unslash($_REQUEST['demo_name'])) : '';

		if ($raw_demo_name === '') {
			wp_send_json_error([
				'message' => __("No demo name provided.", 'blocksy-companion')
			]);
		}

		$demo_name = explode(':', $raw_demo_name);

		if (! isset($demo_name[1])) {
			$demo_name[1] = '';
		}

		$demo = $demo_name[0];
		$builder = $demo_name[1];

		$this->set_current_demo($demo . ':' . $builder);

		wp_send_json_success();
	}

	public function deregister() {
		update_option('blocksy_ext_demos_current_demo', null);

		do_action('blocksy:dynamic-css:refresh-caches');
		do_action('blocksy:cache-manager:purge-all');

		wp_send_json_success();
	}

	public function set_current_demo($demo) {
		update_option('blocksy_ext_demos_current_demo', [
			'demo' => $demo
		]);
	}
}
