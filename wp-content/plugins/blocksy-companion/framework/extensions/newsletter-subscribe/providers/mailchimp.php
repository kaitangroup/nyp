<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class MailchimpProvider extends Provider {
	public function __construct() {
	}

	private function get_subscriber_hash($email) {
		return md5(strtolower(trim($email)));
	}

	private function is_compliance_status($status) {
		return in_array($status, ['unsubscribed', 'cleaned'], true);
	}

	private function is_member_exists_error($response) {
		if (! is_array($response)) {
			return false;
		}

		if (isset($response['title']) && $response['title'] === 'Member Exists') {
			return true;
		}

		if (
			isset($response['detail'])
			&&
			is_string($response['detail'])
			&&
			strpos(strtolower($response['detail']), 'already a list member') !== false
		) {
			return true;
		}

		return false;
	}

	private function is_compliance_error($response) {
		if (! is_array($response)) {
			return false;
		}

		$keywords = ['compliance', 'forgotten', 'resubscribe'];

		foreach (['title', 'detail'] as $key) {
			if (! isset($response[$key]) || ! is_string($response[$key])) {
				continue;
			}

			$value = strtolower($response[$key]);

			foreach ($keywords as $keyword) {
				if (strpos($value, $keyword) !== false) {
					return true;
				}
			}
		}

		return false;
	}

	private function map_error_response_to_message($response, $email = '') {
		if ($this->is_member_exists_error($response)) {
			return blocksy_safe_sprintf(
				// translators: %s is the email address
				__('%s is already a list member.', 'blocksy-companion'),
				$email
			);
		}

		if ($this->is_compliance_error($response)) {
			if (isset($response['detail']) && is_string($response['detail'])) {
				return $response['detail'];
			}

			return NewsletterMessages::confirm_subscription();
		}

		return NewsletterMessages::unable_to_subscribe();
	}

	private function request($api_key, $region, $method, $path, $body = null) {
		$maybe_args = [
			'timeout' => 30,
			'method' => $method,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
				'Content-Type' => 'application/json; charset=utf-8',
			],
		];

		if (! is_null($body)) {
			$maybe_args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request(
			'https://' . $region . '.api.mailchimp.com/3.0/' . ltrim($path, '/'),
			$maybe_args
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

	public function fetch_lists($api_key, $api_url = '') {
		if (! $api_key) {
			return 'api_key_invalid';
		}

		if (strpos($api_key, '-') === false) {
			return 'api_key_invalid';
		}

		$region = explode('-', $api_key);

		if (strpos($region[1], '.') !== false) {
			return 'api_key_invalid';
		}

		$response = wp_remote_get(
			'https://' . $region[1] . '.api.mailchimp.com/3.0/lists?count=1000',
			[
				'timeout' => 2,
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode(
						'asd:' . $api_key
					)
				]
			]
		);

		if (! is_wp_error($response)) {
			if (200 !== wp_remote_retrieve_response_code($response)) {
				return 'api_key_invalid';
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);

			if (! $body) {
				return 'api_key_invalid';
			}

			if (! isset($body['lists'])) {
				return 'api_key_invalid';
			}

			return array_map(function($list) {
				return [
					'name' => $list['name'],
					'id' => $list['id'],
					'subscribe_url_long' => $list['subscribe_url_long'],

					'subscribe_url_long_json' => str_replace(
						'subscribe',
						'subscribe/post-json',
						$list['subscribe_url_long'] . '&c=callback'
					),

					'has_gdpr_fields' => $list['marketing_permissions'],
					'double_optin' => $list['double_optin']
				];
			}, $body['lists']);
		} else {
			return 'api_key_invalid';
		}
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		$settings = $this->get_settings();

		if (! isset($settings['api_key'])) {
			return false;
		}

		if (! $settings['api_key']) {
			return false;
		}

		$lists = $this->fetch_lists($settings['api_key']);

		if (! is_array($lists)) {
			return false;
		}

		if (empty($lists)) {
			return false;
		}

		if ($maybe_custom_list) {
			$settings['list_id'] = $maybe_custom_list;
		}

		if (! $settings['list_id']) {
			return [
				'form_url' => $lists[0]['subscribe_url_long'],
				'has_gdpr_fields' => $lists[0]['has_gdpr_fields'],
				'double_optin' => $lists[0]['double_optin'],
				'provider' => 'mailchimp'
			];
		}

		foreach ($lists as $single_list) {
			if ($single_list['id'] === $settings['list_id']) {
				return [
					'form_url' => $single_list['subscribe_url_long'],
					'has_gdpr_fields' => $single_list['has_gdpr_fields'],
					'double_optin' => $single_list['double_optin'],
					'provider' => 'mailchimp'
				];
			}
		}

		return [
			'form_url' => $lists[0]['subscribe_url_long'],
			'has_gdpr_fields' => $lists[0]['has_gdpr_fields'],
			'double_optin' => $lists[0]['double_optin'],
			'provider' => 'mailchimp'
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

		$api_key = $settings['api_key'];
		$target_status = $args['double_optin'] ? 'pending' : 'subscribed';

		if (strpos($api_key, '-') === false) {
			return 'api_key_invalid';
		}

		$region = explode('-', $api_key);
		$region_name = $region[1];

		if (strpos($region_name, '.') !== false) {
			return 'api_key_invalid';
		}

		$name_parts = $this->maybe_split_name($args['name']);
		$fname = $name_parts['first_name'];
		$lname = $name_parts['last_name'];
		$subscriber_hash = $this->get_subscriber_hash($args['email']);
		$list_path = 'lists/' . $args['group'] . '/members';

		$member_check = $this->request(
			$api_key,
			$region_name,
			'GET',
			$list_path . '/' . $subscriber_hash
		);

		if ($member_check['error']) {
			return [
				'result' => 'no',
				'message' => NewsletterMessages::unable_to_subscribe(),
				'error' => $member_check['error'],
			];
		}

		if (! in_array($member_check['code'], [200, 404], true)) {
			return [
				'result' => 'no',
				'message' => $this->map_error_response_to_message($member_check['body'], $args['email']),
				'res' => $member_check['body'],
			];
		}

		$has_existing_member = $member_check['code'] === 200;
		$existing_status = $has_existing_member ? $member_check['body']['status'] ?? '' : '';

		if ($this->is_compliance_status($existing_status)) {
			$target_status = 'pending';
		}

		if ($existing_status === 'archived') {
			$target_status = 'subscribed';
			$has_existing_member = false;
		}

		$request_body = [
			'email_address' => $args['email'],
			'status' => $target_status,
			'status_if_new' => $target_status,
			'merge_fields' => array_merge(
				[
					'FNAME' => $fname
				],
				(! empty($lname) ? ['LNAME' => $lname] : [])
			)
		];

		if ($has_existing_member && ! $this->is_compliance_status($existing_status) && $existing_status !== 'archived') {
			return [
				'result' => 'no',
				'message' => NewsletterMessages::already_subscribed($args['email']),
				'res' => $member_check['body'],
			];
		}

		$subscribe_response = $this->request(
			$api_key,
			$region_name,
			$has_existing_member ? 'PUT' : 'POST',
			$has_existing_member ? $list_path . '/' . $subscriber_hash : $list_path,
			$request_body
		);

		// Safety fallback for compliance errors that may still come from POST/PUT.
		if (
			$subscribe_response['code'] === 400
			&&
			$target_status !== 'pending'
			&&
			$this->is_compliance_error($subscribe_response['body'])
		) {
			$subscribe_response = $this->request(
				$api_key,
				$region_name,
				'PUT',
				$list_path . '/' . $subscriber_hash,
				array_merge($request_body, [
					'status' => 'pending',
					'status_if_new' => 'pending',
				])
			);
		}

		if (in_array($subscribe_response['code'], [200, 201], true)) {
			return [
				'result' => 'yes',
				'message' => NewsletterMessages::subscribed_successfully(),
				'res' => $subscribe_response['body'],
			];
		} else {
			return [
				'result' => 'no',
				'message' => $this->map_error_response_to_message($subscribe_response['body'], $args['email']),
				'res' => $subscribe_response['body'],
			];
		}
	}

}
