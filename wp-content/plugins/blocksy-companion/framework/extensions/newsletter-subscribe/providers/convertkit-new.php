<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class ConvertKitNewProvider extends Provider {
	public function fetch_lists($api_key, $api_url = '') {

		if (! $api_key) {
			return 'api_key_invalid';
		}

		$response = wp_remote_get(
			'https://api.kit.com/v4/forms/',
			[
				'headers' => [
					'X-Kit-Api-Key' => $api_key,
				],
			]
		);

		if (! is_wp_error($response)) {
			if (200 !== wp_remote_retrieve_response_code($response)) {
				return 'api_key_invalid';
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);

			if (! $body || ! isset($body['forms'])) {
				return 'api_key_invalid';
			}

			return array_map(function($list) {
				return [
					'name' => $list['name'],
					'id' => $list['id'],
				];
			}, $body['forms']);
		} else {
			return 'api_key_invalid';
		}
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		return [
			'form_url' => '#',
			'has_gdpr_fields' => false,
			'provider' => 'convertkit'
		];
	}

	public function subscribe_form($args = []) {
		$args = wp_parse_args($args, [
			'email' => '',
			'name' => '',
			'group' => '',
		]);

		$settings = $this->get_settings();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
		$curl = curl_init();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
		curl_setopt_array($curl, [
			CURLOPT_URL => 'https://api.kit.com/v4/subscribers',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => json_encode([
				'email_address' => $args['email'],
				'first_name' => $args['name'],
			]),
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/json",
				"X-Kit-Api-Key: " . $settings['api_key']
			]
		]);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
		$response = curl_exec($curl);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
		$err = curl_error($curl);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
		curl_close($curl);

		if ($err) {
			return [
				'result' => 'no',
				'error' => $err
			];
		} else {
			$response = json_decode($response, true);

			if (isset($response['errors'])) {
				return [
					'result' => 'no',
					'message' => implode(', ', $response['errors'])
				];
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
			$curl = curl_init();

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
			curl_setopt_array($curl, [
				CURLOPT_URL => 'https://api.kit.com/v4/forms/' . $args['group'] . '/subscribers',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode([
					'email_address' => $args['email'],
				]),
				CURLOPT_HTTPHEADER => [
					"content-type: application/json; charset=utf-8",
					"X-Kit-Api-Key: " . $settings['api_key']
				]
			]);

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
			$response = curl_exec($curl);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
			$err = curl_error($curl);

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
			curl_close($curl);

			if ($err) {
				return [
					'result' => 'no',
					'error' => $err
				];
			} else {
				$response = json_decode($response, true);

				if (isset($response['errors'])) {
					return [
						'result' => 'no',
						'message' => implode(', ', $response['errors'])
					];
				}

				return [
					'result' => 'yes',
					'message' => NewsletterMessages::subscribed_successfully(),
				];
			}
		}		
	}
}

