<?php

if (! defined('ABSPATH')) {
	exit;
}

function blocksy_ext_cookies_consent_output() {
	$content = blocksy_companion_theme_functions()->blocksy_get_theme_mod(
		'cookie_consent_content',
		__('We use cookies to ensure that we give you the best experience on our website.', 'blocksy-companion')
	);

	$accept_button_text = blocksy_companion_theme_functions()->blocksy_get_theme_mod('cookie_consent_button_text', __('Accept', 'blocksy-companion'));
	$decline_button_text = blocksy_companion_theme_functions()->blocksy_get_theme_mod('cookie_consent_decline_button_text', __('Decline', 'blocksy-companion'));

	$period = blocksy_companion_theme_functions()->blocksy_get_theme_mod('cookie_consent_period', 'forever');
	$type = blocksy_companion_theme_functions()->blocksy_get_theme_mod('cookie_consent_type', 'type-1');

	$class = 'container';

	if ( $type === 'type-2' ) {
		$class = 'ct-container';
	}

	ob_start();

	?>

	<div class="cookie-notification ct-fade-in-start" data-period="<?php echo esc_attr($period) ?>" data-type="<?php echo esc_attr($type) ?>">

		<div class="<?php echo esc_attr($class) ?>">
			<?php if (!empty($content)) { ?>
				<div class="ct-cookies-content"><?php echo wp_kses_post($content) ?></div>
			<?php } ?>

			<div class="ct-button-group">
				<button type="button" class="ct-button ct-cookies-accept-button"><?php echo esc_html($accept_button_text) ?></button>

				<button type="button" class="ct-button ct-cookies-decline-button"><?php echo esc_html($decline_button_text) ?></button>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

function blocksy_ext_cookies_checkbox($prefix = '') {
	ob_start();

	if (! empty($prefix)) {
		$prefix = '_' . $prefix;
	}

	$message = blocksy_companion_theme_functions()->blocksy_get_theme_mod(
		'forms_cookie_consent_content',
		blocksy_companion_safe_sprintf(
			// translators: %1$s and %2$s are HTML tags for a link.
			__('I accept the %1$sPrivacy Policy%2$s', 'blocksy-companion'),
			'<a href="' . get_privacy_policy_url() . '">',
			'</a>'
		)
	);

	?>

	<p class="gdpr-confirm-policy">
		<input name="ct_has_gdprconfirm" type="hidden" value="yes">
		<?php
			blocksy_html_tag_e(
				'input',
				[
					'id' => 'gdprconfirm' . $prefix,
					'class' => 'ct-checkbox',
					'name' => 'gdprconfirm',
					'type' => 'checkbox',
					'required' => true
				]
			);
		?><label for="gdprconfirm<?php echo esc_attr($prefix) ?>"><?php echo wp_kses_post($message) ?></label>
	</p>

	<?php

	return ob_get_clean();
}
