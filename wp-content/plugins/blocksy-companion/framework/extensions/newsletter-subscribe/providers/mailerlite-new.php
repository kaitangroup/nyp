<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class MailerliteNewProvider extends Provider {
	private function request($method, $path, $api_key, $body = null) {
		$args = [
			'timeout' => 30,
			'method' => $method,
			'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:87.0) Gecko/20100101 Firefox/87.0',
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'accept' => 'application/json',
				'content-type' => 'application/json',
			],
		];

		if (! is_null($body)) {
			$args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request(
			'https://connect.mailerlite.com/api/' . ltrim($path, '/'),
			$args
		);

		if (is_wp_error($response)) {
			return [
				'error' => $response->get_error_message(),
				'code' => 0,
				'body' => [],
			];
		}

		$decoded_body = json_decode(wp_remote_retrieve_body($response), true);

		if (! is_array($decoded_body)) {
			$decoded_body = [];
		}

		return [
			'error' => null,
			'code' => wp_remote_retrieve_response_code($response),
			'body' => $decoded_body,
		];
	}

	private function fetch_double_optin_status($api_key) {
		if (! $api_key) {
			return false;
		}

		$cache_key = 'blocksy_ml_new_doi_' . md5($api_key);
		$cached = get_transient($cache_key);

		if ($cached !== false) {
			return $cached === '1';
		}

		$response = $this->request('GET', 'settings/double_optin', $api_key);
		$is_enabled = false;

		if (! $response['error'] && 200 === $response['code']) {
			$body = $response['body'];

			if (isset($body['enabled'])) {
				$is_enabled = (bool) $body['enabled'];
			} elseif (isset($body['double_optin'])) {
				$is_enabled = (bool) $body['double_optin'];
			} elseif (isset($body['data']['enabled'])) {
				$is_enabled = (bool) $body['data']['enabled'];
			} elseif (isset($body['data']['double_optin'])) {
				$is_enabled = (bool) $body['data']['double_optin'];
			}
		}

		set_transient($cache_key, $is_enabled ? '1' : '0', 5 * MINUTE_IN_SECONDS);

		return $is_enabled;
	}

	private function is_double_optin_enabled($settings) {
		$detected = false;

		if (isset($settings['api_key']) && $settings['api_key']) {
			$detected = $this->fetch_double_optin_status($settings['api_key']);
		}

		return $detected;
	}

	public function fetch_lists($api_key, $api_url = '') {
		if (! $api_key) {
			return 'api_key_invalid';
		}

		$response = $this->request('GET', 'groups', $api_key);

		if ($response['error'] || 200 !== $response['code']) {
			return 'api_key_invalid';
		}

		if (! isset($response['body']['data']) || ! is_array($response['body']['data'])) {
			return 'api_key_invalid';
		}

		$double_optin = $this->is_double_optin_enabled([
			'api_key' => $api_key,
		]);

		return array_map(function($list) use ($double_optin) {
			return [
				'name' => $list['name'],
				'id' => $list['id'],
				'double_optin' => $double_optin,
			];
		}, $response['body']['data']);
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		$settings = $this->get_settings();

		return [
			'form_url' => '#',
			'has_gdpr_fields' => false,
			'double_optin' => $this->is_double_optin_enabled($settings),
			'provider' => 'mailerlite'
		];
	}

	public function subscribe_form($args = []) {
		$args = wp_parse_args($args, [
			'email' => '',
			'name' => '',
			'group' => '',
			'double_optin' => false,
		]);

		$settings = $this->get_settings();

		$payload = [
			'email' => $args['email'],
			'fields' => [
				'name' => $args['name']
			],
			'groups' => [
				$args['group']
			]
		];

		if ($args['double_optin'] || $this->is_double_optin_enabled($settings)) {
			$payload['status'] = 'unconfirmed';
		}

		$response = $this->request(
			'POST',
			'subscribers',
			$settings['api_key'],
			$payload
		);

		if ($response['error'] || ! in_array($response['code'], [200, 201], true)) {
			return [
				'result' => 'no',
				'message' => isset($response['body']['message']) ? $response['body']['message'] : NewsletterMessages::unable_to_subscribe(),
				'error' => $response['error'],
				'res' => $response['body'],
			];
		}

		$status = $response['body']['data']['status'] ?? '';

		if ($status === 'unconfirmed') {
			return [
				'result' => 'yes',
				'message' => NewsletterMessages::confirm_subscription(),
				'res' => $response['body'],
			];
		}

		return [
			'result' => 'yes',
			'message' => NewsletterMessages::subscribed_successfully(),
			'res' => $response['body'],
		];
	}
}
