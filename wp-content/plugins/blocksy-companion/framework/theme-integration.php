<?php

namespace Blocksy;

if (! defined('ABSPATH')) {
	exit;
}

class ThemeIntegration {
	public function __construct() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				if (! function_exists('get_plugin_data')){
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				if (is_admin()) return;

				wp_register_script(
					'blocksy-zxcvbn',
					includes_url('/js/zxcvbn.min.js'),
					[],
					$data['Version'],
					false
				);
			},
			5
		);

		add_filter('blocksy:frontend:dynamic-js-chunks', function ($chunks) {
			$render = new \Blocksy_Header_Builder_Render();

			if (
				$render->contains_item('account')
				||
				is_customize_preview()
			) {
				$deps = [];
				$global_data = [];

				if (class_exists('woocommerce')) {
					$deps = [
						'blocksy-zxcvbn',
						'wp-hooks',
						'wp-i18n',
						'password-strength-meter',
					];

					$global_data = [
						[
							'var' => 'wc_password_strength_meter_params',
							'data' => [
								'min_password_strength' => apply_filters(
									'woocommerce_min_password_strength',
									3
								),
								'stop_checkout' => apply_filters(
									'woocommerce_enforce_password_strength_meter_on_checkout',
									false
								),
								'i18n_password_error'=> esc_attr__(
									'Please enter a stronger password.',
									'blocksy-companion'
								),
								'i18n_password_hint' => addslashes(wp_get_password_hint()),
							]
						],

						[
							'var' => 'pwsL10n',
							'data' => [
								'unknown'  => _x( 'Password strength unknown', 'password strength', 'blocksy-companion' ),
								'short'    => _x( 'Very weak', 'password strength', 'blocksy-companion' ),
								'bad'      => _x( 'Weak', 'password strength', 'blocksy-companion' ),
								'good'     => _x( 'Medium', 'password strength', 'blocksy-companion' ),
								'strong'   => _x( 'Strong', 'password strength', 'blocksy-companion' ),
								'mismatch' => _x( 'Mismatch', 'password mismatch', 'blocksy-companion' ),
							]
						]
					];
				}

				if (function_exists('dokan')) {
					$deps[] = 'dokan-form-validate';
					$deps[] = 'dokan-vendor-registration';

					$global_data[] = [
						'var' => 'DokanValidateMsg',
						'data' => apply_filters('DokanValidateMsg_args', [
							'required' => __( 'This field is required', 'blocksy-companion' ),
							'remote' => __( 'Please fix this field.', 'blocksy-companion' ),
							'email' => __( 'Please enter a valid email address.', 'blocksy-companion' ),
							'url' => __( 'Please enter a valid URL.', 'blocksy-companion' ),
							'date' => __( 'Please enter a valid date.', 'blocksy-companion' ),
							'dateISO' => __( 'Please enter a valid date (ISO).', 'blocksy-companion' ),
							'number' => __( 'Please enter a valid number.', 'blocksy-companion' ),
							'digits' => __( 'Please enter only digits.', 'blocksy-companion' ),
							'creditcard' => __( 'Please enter a valid credit card number.', 'blocksy-companion' ),
							'equalTo' => __( 'Please enter the same value again.', 'blocksy-companion' ),
							'maxlength_msg' => __( 'Please enter no more than {0} characters.', 'blocksy-companion' ),
							'minlength_msg' => __( 'Please enter at least {0} characters.', 'blocksy-companion' ),
							'rangelength_msg' => __( 'Please enter a value between {0} and {1} characters long.', 'blocksy-companion' ),
							'range_msg' => __( 'Please enter a value between {0} and {1}.', 'blocksy-companion' ),
							'max_msg' => __( 'Please enter a value less than or equal to {0}.', 'blocksy-companion' ),
							'min_msg' => __( 'Please enter a value greater than or equal to {0}.', 'blocksy-companion' ),
						])
					];

					$global_data[] = [
						'var' => 'dokanRegistrationI18n',
						'data' => [
							'defaultRole' => dokan_get_seller_registration_default_role(),
						]
					];
				}

				$chunks[] = [
					'id' => 'blocksy_account',
					'selector' => implode(', ', [
						'.ct-account-item[href*="account-modal"]',
						'.must-log-in a'
					]),
					'url' => blocksy_cdn_url(
						BLOCKSY_URL . 'static/bundle/account.js'
					),
					'deps' => $deps,
					'global_data' => $global_data,

					'trigger' => 'click',
					'version' => blocksy_companion_get_version()
				];
			}

			$chunks[] = [
				'id' => 'blocksy_sticky_header',
				'selector' => 'header [data-sticky]',
				'url' => blocksy_cdn_url(
					BLOCKSY_URL . 'static/bundle/sticky.js'
				),
				'version' => blocksy_companion_get_version()
			];

			return $chunks;
		});

		add_shortcode('blocksy_posts', function ($args, $content) {
			$args = wp_parse_args(
				$args,
				[
					'post_type' => 'post',
					'limit' => 5,

					// post_date | comment_count
					'orderby' => 'post_date',
					'order' => 'DESC',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value' => '',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key' => '',

					// yes | no
					'has_slideshow' => 'no',
					'has_slideshow_arrows' => 'yes',
					'has_slideshow_autoplay' => 'no',
					'has_slideshow_autoplay_speed' => 3,

					// yes | no
					'has_pagination' => 'yes',

					// yes | no
					'ignore_sticky_posts' => 'no',

					'term_ids' => null,
					'exclude_term_ids' => null,
					'post_ids' => null,

					// archive | slider
					'view' => 'archive',
					'slider_image_ratio' => '2/1',
					'slider_autoplay' => 'no',

					'filtering' => false,
					'filtering_use_children_tax_ids' => false,

					// 404 | skip
					'no_results' => '404',

					'class' => ''
				]
			);

			$file_path = dirname(__FILE__) . '/views/blocksy-posts.php';

			return blocksy_render_view(
				$file_path,
				[
					'args' => $args,
					'content' => $content
				]
			);
		});

		add_filter('blocksy:general:ct-scripts-localizations', function ($data) {
			if (! function_exists('get_plugin_data')){
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			$plugin_data = get_plugin_data(BLOCKSY__FILE__);

			$data['dynamic_styles_selectors'][] = [
				'selector' => '#account-modal',
				'url' => add_query_arg(
					'ver',
					$plugin_data['Version'],
					blocksy_cdn_url(
						BLOCKSY_URL . 'static/bundle/header-account-modal-lazy.min.css'
					)
				)
			];

			$data['dynamic_styles_selectors'][] = [
				'selector' => '.ct-header-account',
				'url' => add_query_arg(
					'ver',
					$plugin_data['Version'],
					blocksy_cdn_url(
						BLOCKSY_URL . 'static/bundle/header-account-dropdown-lazy.min.css'
					)
				)
			];

			return $data;
		});

		add_filter(
			'user_contactmethods',
			function ( $field ) {
				$fields['facebook'] = __( 'Facebook', 'blocksy-companion' );
				$fields['twitter'] = __( 'X (Twitter)', 'blocksy-companion' );
				$fields['linkedin'] = __( 'LinkedIn', 'blocksy-companion' );
				$fields['dribbble'] = __( 'Dribbble', 'blocksy-companion' );
				$fields['instagram'] = __( 'Instagram', 'blocksy-companion' );
				$fields['pinterest'] = __( 'Pinterest', 'blocksy-companion' );
				$fields['wordpress'] = __( 'WordPress', 'blocksy-companion' );
				$fields['github'] = __( 'GitHub', 'blocksy-companion' );
				$fields['medium'] = __( 'Medium', 'blocksy-companion' );
				$fields['youtube'] = __( 'YouTube', 'blocksy-companion' );
				$fields['vimeo'] = __( 'Vimeo', 'blocksy-companion' );
				$fields['vkontakte'] = __( 'VKontakte', 'blocksy-companion' );
				$fields['odnoklassniki'] = __( 'Odnoklassniki', 'blocksy-companion' );
				$fields['tiktok'] = __( 'TikTok', 'blocksy-companion' );
				$fields['mastodon'] = __( 'Mastodon', 'blocksy-companion' );

				$additional_fields = apply_filters(
					'blocksy:author-profile:custom-social-network',
					[]
				);

				foreach ($additional_fields as $field) {
					if (
						isset($field['id'])
						&&
						isset($field['name'])
					)  {
						$fields[$field['id']] = $field['name'];
					}
				}

				return $fields;
			}
		);

		add_filter('blocksy_changelogs_list', function ($changelogs) {
			$changelog = null;
			$access_type = get_filesystem_method();

			if ($access_type === 'direct') {
				$creds = request_filesystem_credentials(
					site_url() . '/wp-admin/',
					'', false, false,
					[]
				);

				if (WP_Filesystem($creds)) {
					global $wp_filesystem;

					$readme = $wp_filesystem->get_contents(
						BLOCKSY_PATH . '/readme.txt'
					);

					if ($readme) {
						$readme = explode('== Changelog ==', $readme);

						if (isset($readme[1])) {
							$changelogs[] = [
								'title' => __('Companion', 'blocksy-companion'),
								'changelog' => trim($readme[1])
							];
						}
					}

					if (
						blocksy_companion_can_use_premium_code()
						&&
						file_exists(
							BLOCKSY_PATH . '/framework/premium/changelog.txt'
						)
					) {
						$pro_changelog = $wp_filesystem->get_contents(
							BLOCKSY_PATH . '/framework/premium/changelog.txt'
						);

						$changelogs[] = [
							'title' => __('PRO', 'blocksy-companion'),
							'changelog' => trim($pro_changelog)
						];
					}
				}
			}

			return $changelogs;
		});

		add_action('wp_enqueue_scripts', function () {
			if (! function_exists('get_plugin_data')){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			$data = get_plugin_data(BLOCKSY__FILE__);

			if (is_admin()) return;

			if (! class_exists('Blocksy_Header_Builder_Render')) {
				return;
			}

			$render = new \Blocksy_Header_Builder_Render();

			if (
				$render->contains_item('account')
				||
				is_customize_preview()
			) {
				wp_enqueue_style(
					'blocksy-companion-header-account-styles',
					BLOCKSY_URL . 'static/bundle/header-account.min.css',
					['ct-main-styles'],
					$data['Version']
				);
			}
		}, 50);

		add_action(
			'customize_preview_init',
			function () {
				if (! function_exists('get_plugin_data')){
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$data = get_plugin_data(BLOCKSY__FILE__);

				wp_enqueue_script(
					'blocksy-companion-sync-scripts',
					BLOCKSY_URL . 'static/bundle/sync.js',
					['customize-preview', 'ct-scripts', 'wp-date', 'ct-scripts', 'ct-customizer'],
					$data['Version'],
					true
				);
			}
		);
	}
}

