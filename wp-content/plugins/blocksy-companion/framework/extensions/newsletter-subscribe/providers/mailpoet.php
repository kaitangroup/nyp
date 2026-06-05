<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class MailPoetProvider extends Provider {
	public function __construct() {
	}

	public function fetch_lists($api_key, $api_url = '') {

		if (! class_exists(\MailPoet\API\API::class)) {
			return 'api_key_invalid';
		}

		$mailpoet_api = \MailPoet\API\API::MP('v1');

		return array_map(function($list) {
			return [
				'name' => $list['name'],
				'id' => $list['id'],
			];
		}, $mailpoet_api->getLists());
	}

	public function get_form_url_and_gdpr_for($maybe_custom_list = null) {
		return [
			'form_url' => '#',
			'has_gdpr_fields' => false,
			'provider' => 'mailpoet'
		];
	}

	public function subscribe_form($args = []) {
		$args = wp_parse_args($args, [
			'email' => '',
			'name' => '',
			'group' => ''
		]);

		$settings = $this->get_settings();

		if (! class_exists(\MailPoet\API\API::class)) {
			return [
				'result' => 'no',
				'error' => 'MailPoet API not found'
			];
		}

		$mailpoet_api = \MailPoet\API\API::MP('v1');

		$name_parts = $this->maybe_split_name($args['name']);
		$fname = $name_parts['first_name'];
		$lname = $name_parts['last_name'];

		$list_ids = [$args['group']];

		$subscriber = [
			'email' => $args['email'],
			'first_name' => $fname,
		];

		if (! empty($lname)) {
			$subscriber['last_name'] = $lname;
		}

		try {
			$get_subscriber = $mailpoet_api->getSubscriber($subscriber['email']);
		} catch (\Exception $e) {
			$get_subscriber = false;
		}

		try {
			if (! $get_subscriber) {
				$mailpoet_api->addSubscriber($subscriber, $list_ids);
			} else {
				$mailpoet_api->subscribeToLists($subscriber['email'], $list_ids);
			}
		} catch (\Exception $e) {
			return [
				'result' => 'no',
				'message' => $e->getMessage()
			];
		}

		return [
			'result' => 'yes',
			'message' => NewsletterMessages::subscribed_successfully()
		];
	}
}

