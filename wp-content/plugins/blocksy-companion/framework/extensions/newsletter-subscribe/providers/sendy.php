<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class SendyProvider extends Provider {
	private function request($path, $api_url, $body = []) {
		$response = wp_remote_post(
			trailingslashit($api_url) . ltrim($path, '/'),
			[
				'timeout' => 30,
				'body' => $body,
			]
		);

		if (is_wp_error($response)) {
			return [
				'error' => $response->get_error_message(),
				'code' => 0,
				'body' => null,
			];
		}

		$raw_body = wp_remote_retrieve_body($response);
		$decoded_body = json_decode($raw_body, true);

		return [
			'error' => null,
			'code' => wp_remote_retrieve_response_code($response),
			'body' => is_array($decoded_body) ? $decoded_body : $raw_body,
		];
	}

	public function fetch_lists($api_key, $api_url) {
		if (! $api_url) {
			return 'api_url_invalid';
		}

		if (! $api_key) {
			return 'api_key_invalid';
		}

		$brands_response = $this->request(
			'/api/brands/get-brands.php',
			$api_url,
			[
				'api_key' => $api_key,
			]
		);

		if (
			$brands_response['error']
			||
			200 !== $brands_response['code']
			||
			! is_array($brands_response['body'])
		) {
			return 'api_key_invalid';
		}

		$brands = array_values(array_filter($brands_response['body'], function ($brand) {
			return isset($brand['id'], $brand['name']);
		}));

		if (empty($brands)) {
			return 'api_key_invalid';
		}

		$has_multiple_brands = count($brands) > 1;
		$lists = [];

		foreach ($brands as $brand) {
			$lists_response = $this->request(
				'/api/lists/get-lists.php',
				$api_url,
				[
					'api_key' => $api_key,
					'brand_id' => $brand['id'],
				]
			);

			if (
				$lists_response['error']
				||
				200 !== $lists_response['code']
				||
				! is_array($lists_response['body'])
			) {
				continue;
			}

			foreach ($lists_response['body'] as $list) {
				if (! isset($list['id'], $list['name'])) {
					continue;
				}

				$lists[] = [
					'name' => $has_multiple_brands ? $brand['name'] . ' / ' . $list['name'] : $list['name'],
					'id' => $list['id'],
				];
			}
		}

		if (empty($lists)) {
			return 'api_key_invalid';
		}

		return $lists;
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		return [
			'form_url' => '#',
			'has_gdpr_fields' => false,
			'provider' => 'sendy'
		];
	}

	public function subscribe_form($args = []) {
		$args = wp_parse_args($args, [
			'email' => '',
			'name' => '',
			'group' => ''
		]);

		$settings = $this->get_settings();

		$response = $this->request(
			'/subscribe',
			$settings['api_url'],
			[
				'api_key' => $settings['api_key'],
				'name' => $args['name'],
				'email' => $args['email'],
				'list' => $args['group'],
				'boolean' => 'true',
			]
		);

		if ($response['error'] || 200 !== $response['code']) {
			return [
				'result' => 'no',
				'message' => NewsletterMessages::unable_to_subscribe(),
				'error' => $response['error'],
			];
		}

		$body = is_string($response['body']) ? trim($response['body']) : '';

		if ($body !== '1' && strtolower($body) !== 'true') {
			return [
				'result' => 'no',
				'message' => $body ? $body : NewsletterMessages::unable_to_subscribe(),
			];
		}

		return [
			'result' => 'yes',
			'message' => NewsletterMessages::subscribed_successfully(),
		];
	}
}
