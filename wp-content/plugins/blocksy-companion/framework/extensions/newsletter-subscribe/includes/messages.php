<?php

namespace Blocksy\Extensions\NewsletterSubscribe;

class NewsletterMessages {
	public static function unable_to_subscribe() {
		return __('Unable to subscribe. Please try again later.', 'blocksy-companion');
	}

	public static function confirm_subscription() {
		return __('Please confirm your subscription from the email we sent you.', 'blocksy-companion');
	}

	public static function subscribed_successfully() {
		return __('Thank you for subscribing to our newsletter!', 'blocksy-companion');
	}

	public static function already_subscribed($email = '') {
		return blocksy_safe_sprintf(
			// translators: %s is the email address
			__('%s is already a list member.', 'blocksy-companion'),
			$email
		);
	}
}
