<?php

namespace Blocksy;

if (! defined('ABSPATH')) {
	exit;
}

class ConditionsManagerAPI {
	public function __construct() {
		add_action('wp_ajax_blc_retrieve_conditions_data', function () {
			$capability = blocksy_companion_get_capabilities()->get_wp_capability_by('conditions');

			if (! current_user_can($capability)) {
				wp_send_json_error();
			}

			$filter = 'all';

			$allowed_filters = [
				'archive',
				'singular',
				'product_tabs',
				'product_waitlist',
				'maintenance-mode',
				'content_block_hook'
			];

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$maybe_filter = isset($_REQUEST['filter']) ? sanitize_text_field(wp_unslash($_REQUEST['filter'])) : '';

			if (in_array($maybe_filter, $allowed_filters)) {
				$filter = $maybe_filter;
			}

			$languages = [];

			if (function_exists('blocksy_get_current_language')) {
				$languages = blocksy_get_all_i18n_languages();
			}

			$conditions_manager = new ConditionsManager();

			wp_send_json_success([
				'languages' => $languages,
				'conditions' => $conditions_manager->get_all_rules([
					'filter' => $filter
				]),
			]);
		});
	}
}

