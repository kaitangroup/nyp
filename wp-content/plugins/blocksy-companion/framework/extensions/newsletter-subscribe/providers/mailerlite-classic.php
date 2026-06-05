<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class MailerliteClassicProvider extends Provider {
	private function request($method, $path, $api_key, $body = null) {
		$args = [
			'timeout' => 30,
			'method' => $method,
			'headers' => [
				'X-MailerLite-ApiKey' => $api_key,
				'accept' => 'application/json',
				'content-type' => 'application/json',
			],
		];

		if (! is_null($body)) {
			$args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request(
			'https://api.mailerlite.com/api/v2/' . ltrim($path, '/'),
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

		$cache_key = 'blocksy_ml_classic_doi_' . md5($api_key);
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

	public function fetch_lists($api_key, $api_url = '') {
		if (! $api_key) {
			return 'api_key_invalid';
		}

		$response = $this->request('GET', 'groups', $api_key);

		if ($response['error'] || 200 !== $response['code']) {
			return 'api_key_invalid';
		}

		if (! is_array($response['body']) || empty($response['body'])) {
			return 'api_key_invalid';
		}

		$double_optin = $this->fetch_double_optin_status($api_key);

		return array_map(function($list) use ($double_optin) {
			return [
				'name' => $list['name'],
				'id' => $list['id'],
				'double_optin' => $double_optin,
			];
		}, $response['body']);
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		$settings = $this->get_settings();
		$has_double_optin = false;

		if (isset($settings['api_key']) && $settings['api_key']) {
			$has_double_optin = $this->fetch_double_optin_status($settings['api_key']);
		}

		return [
			'form_url' => '#',
			'has_gdpr_fields' => false,
			'double_optin' => $has_double_optin,
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

		$response = $this->request(
			'POST',
			'groups/' . $args['group'] . '/subscribers',
			$settings['api_key'],
			[
				'email' => $args['email'],
				'name' => $args['name']
			]
		);

		if ($response['error'] || ! in_array($response['code'], [200, 201], true)) {
			return [
				'result' => 'no',
				'message' => isset($response['body']['error']['message']) ? $response['body']['error']['message'] : NewsletterMessages::unable_to_subscribe(),
				'error' => $response['error'],
				'res' => $response['body'],
			];
		}

		$status = $response['body']['type'] ?? '';

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
