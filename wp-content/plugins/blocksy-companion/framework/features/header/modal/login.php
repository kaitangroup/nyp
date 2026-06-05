<?php

if (! defined('ABSPATH')) {
	exit;
}

// wp_login_form([]);

$forgot_password_inline = apply_filters(
	'blocksy:account:modal:login:forgot-password-inline',
	true
);

$forgot_pass_class = 'ct-forgot-password';

if (! $forgot_password_inline) {
	$forgot_pass_class .= '-static';
}

$login_field_name = 'log';

if (function_exists('is_account_page') && is_account_page()) {
	$login_field_name = 'username';
}

?>

<form name="loginform" id="loginform" class="login" action="#" method="post">
	<?php do_action('woocommerce_login_form_start'); ?>
	<?php do_action('blocksy:account:modal:login:start'); ?>

	<p>
		<label for="user_login"><?php echo esc_html__('Username or Email Address', 'blocksy-companion') ?></label>
		<input type="text" name="<?php echo esc_attr($login_field_name); ?>" id="user_login" class="input" value="" size="20" autocomplete="username" autocapitalize="off">
	</p>

	<p>
		<label for="user_pass"><?php echo esc_html__('Password', 'blocksy-companion') ?></label>
		<span class="account-password-input">
			<?php
				blocksy_html_tag_e(
					'input',
					apply_filters('blocksy:account:modal:login:password:attr', [
						'type' => 'password',
						'name' => 'pwd',
						'id' => 'user_pass',
						'class' => 'input',
						'value' => '',
						'size' => '20',
						'autocomplete' => 'current-password',
						'spellcheck' => 'false'
					])
				);
			?>

			<span class="show-password-input"></span>
		</span>
	</p>

	<?php
		remove_action("login_form", "wp_login_attempt_focus_start");

		// login_form action hook should be right after password field, just like
		// in the wp-login.php core file.
		do_action('login_form')
	?>

	<p class="login-remember col-2">
		<span>
			<input name="rememberme" type="checkbox" id="rememberme" class="ct-checkbox" value="forever">
			<label for="rememberme"><?php echo esc_html__('Remember Me', 'blocksy-companion') ?></label>
		</span>

		<a href="#" class="<?php echo esc_attr($forgot_pass_class) ?>">
			<?php echo esc_html__('Forgot Password?', 'blocksy-companion') ?>
		</a>
	</p>

	<?php
		if (blocksy_companion_site_has_feature()) {
			if (
				class_exists('NextendSocialLogin', false)
				&&
				! class_exists('NextendSocialLoginPRO', false)
			) {
				\NextendSocialLogin::addLoginFormButtons();
			}
		}

		// remove_action("login_form", "wp_login_attempt_focus_start");

		// do_action('login_form')
	?>

	<p class="login-submit">
		<button class="ct-button ct-account-login-submit has-text-align-center" name="wp-submit">
			<?php echo esc_html__('Log In', 'blocksy-companion') ?>

			<svg class="ct-button-loader" width="16" height="16" viewBox="0 0 24 24">
				<circle cx="12" cy="12" r="10" opacity="0.2" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2.5"/>

				<path d="m12,2c5.52,0,10,4.48,10,10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2.5">
					<animateTransform
						attributeName="transform"
						attributeType="XML"
						type="rotate"
						dur="0.6s"
						from="0 12 12"
						to="360 12 12"
						repeatCount="indefinite"
					/>
				</path>
			</svg>
		</button>
	</p>

	<?php do_action('blocksy:account:modal:login:end'); ?>
	<?php do_action('woocommerce_login_form_end'); ?>
</form>

