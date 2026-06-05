<?php

if (! defined('ABSPATH')) {
	exit;
}

require_once dirname(__FILE__) . '/helpers.php';

class BlocksyExtensionCookiesConsent {
	public static function should_display_notification() {
		return ! isset($_COOKIE['blocksy_cookies_consent_accepted']);
	}

	public static function has_consent() {
		return (
			isset($_COOKIE['blocksy_cookies_consent_accepted'])
			&&
			$_COOKIE['blocksy_cookies_consent_accepted'] === 'true'
		);
	}

	public function __construct() {
		add_filter('blocksy-async-scripts-handles', function ($d) {
			$d[] = 'blocksy-ext-cookies-consent-scripts';
			return $d;
		});

		add_filter(
			'blocksy_extensions_customizer_options',
			[$this, 'add_options_panel']
		);

		add_action(
			'customize_preview_init',
			function () {
				if (! function_exists('get_plugin_data')){
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_enqueue_script(
					'blocksy-cookies-consent-customizer-sync',
					BLOCKSY_URL . 'framework/extensions/cookies-consent/static/bundle/sync.js',
					[ 'ct-scripts', 'customize-preview' ],
					$data['Version'],
					true
				);
			}
		);

		add_action('wp_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (is_admin()) {
				return;
			}

			wp_enqueue_script(
				'blocksy-ext-cookies-consent-scripts',
				BLOCKSY_URL . 'framework/extensions/cookies-consent/static/bundle/main.js',
				['ct-scripts'],
				$data['Version'],
				true
			);
		}, 50);

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			$data['dynamic_styles']['cookie_notification'] = blocksy_cdn_url(
				BLOCKSY_URL . 'framework/extensions/cookies-consent/static/bundle/main.min.css'
			);

			return $data;
		});

		add_action(
			'blocksy:global-dynamic-css:enqueue',
			'BlocksyExtensionCookiesConsent::add_global_styles',
			10, 3
		);

		add_action(
			'pre_comment_on_post',
			function ($post_id) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data = wp_unslash($_POST);

				if (! isset($data['comment_post_ID'])) {
					return;
				}

				if (
					! isset($data['ct_has_gdprconfirm'])
					||
					$data['ct_has_gdprconfirm'] !== 'yes'
				) {
					return;
				}

				if (
					! isset($data['gdprconfirm'])
					||
					$data['gdprconfirm'] !== 'on'
				) {
					wp_die(
						'<p>' . esc_html__('Please accept the Privacy Policy in order to comment.', 'blocksy-companion') . '</p>',
						esc_html__('Comment Submission Failure', 'blocksy-companion'),
						array(
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'response' => $data,
							'back_link' => true,
						)
					);
				}
			}
		);

		add_action('wp', function() {
			add_filter('woocommerce_product_review_comment_form_args', [$this, 'change_comment_form']);
		}, 999);

		add_filter(
			'blocksy:footer:offcanvas-drawer',
			function ($els, $payload) {
				if ($payload['location'] !== 'start') {
					return $els;
				}

				$els[] = '<template id="ct-cookies-consent-template">'
					. blocksy_ext_cookies_consent_output()
					. '</template>';

				return $els;
			},
			10,
			2
		);

		add_action('wp_ajax_blocksy_companion_load_cookies_consent_scripts', [
			$this,
			'load_cookies_consent_scripts',
		]);

		add_action(
			'wp_ajax_nopriv_blocksy_companion_load_cookies_consent_scripts',
			[$this, 'load_cookies_consent_scripts']
		);
	}

	public function load_cookies_consent_scripts() {
		$scripts = apply_filters('blocksy:cookies-consent:scripts-to-load', [], PHP_INT_MAX);

		wp_send_json_success([
			'scripts' => $scripts,
		]);
	}

	public function change_comment_form($comment_form) {
		$comment_form['comment_field'] .= blocksy_ext_cookies_checkbox('reviews');

		return $comment_form;
	}

	static public function add_global_styles($args) {
		blocksy_theme_get_dynamic_styles(array_merge([
			'path' => dirname(__FILE__) . '/global.php',
			'chunk' => 'global',
		], $args));
	}

	static public function onDeactivation() {
		remove_action(
			'blocksy:global-dynamic-css:enqueue',
			'BlocksyExtensionCookiesConsent::add_global_styles',
			10, 3
		);
	}

	public function add_options_panel($options) {
		$options['cookie_consent_ext'] = blocksy_get_options(
			dirname(__FILE__) . '/customizer.php',
			[],
			false
		);

		return $options;
	}
}

