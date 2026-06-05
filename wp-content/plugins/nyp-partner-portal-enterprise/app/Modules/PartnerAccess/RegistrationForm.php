<?php

if (!defined('ABSPATH')) {
    exit;
}

class RegistrationForm
{
    public function register()
    {
        add_shortcode(
            'nyp_partner_registration',
            [$this, 'render']
        );

        add_action(
            'wp_enqueue_scripts',
            [$this, 'enqueue_assets']
        );
    }

    public function enqueue_assets()
{
    if (!is_page('partner-registration')) {
        return;
    }

    wp_enqueue_style(
        'nyp-partner-registration',
        plugin_dir_url(dirname(dirname(dirname(__DIR__))))
            . 'assets/css/partner-registration.css',
        [],
        '1.0.0'
    );
}

    public function render()
    {
        // Already logged in
        if (is_user_logged_in()) {

            return '
                <div class="nyp-notice nyp-notice-info">
                    You are already logged in.
                </div>
            ';
        }

        ob_start();

        $this->display_messages();

        ?>

        <form method="post" class="nyp-partner-registration-form">

            <?php wp_nonce_field(
                'nyp_partner_registration',
                'nyp_partner_registration_nonce'
            ); ?>

            <input
                type="hidden"
                name="nyp_partner_registration"
                value="1"
            >

            <div class="nyp-form-row">
                <label for="company_name">
                    Company Name *
                </label>

                <input
                    type="text"
                    id="company_name"
                    name="company_name"
                    required
                >
            </div>

            <div class="nyp-form-row">
                <label for="contact_person">
                    Contact Person *
                </label>

                <input
                    type="text"
                    id="contact_person"
                    name="contact_person"
                    required
                >
            </div>

            <div class="nyp-form-row">
                <label for="email">
                    Email *
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >
            </div>

            <div class="nyp-form-row">
                <label for="phone">
                    Phone *
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    required
                >
            </div>

            <div class="nyp-form-row">
                <label for="website">
                    Website
                </label>

                <input
                    type="url"
                    id="website"
                    name="website"
                >
            </div>

            <div class="nyp-form-row">
                <label for="vat_number">
                    VAT Number
                </label>

                <input
                    type="text"
                    id="vat_number"
                    name="vat_number"
                >
            </div>

            <div class="nyp-form-row">
                <label for="message">
                    Message
                </label>

                <textarea
                    id="message"
                    name="message"
                    rows="4"
                ></textarea>
            </div>

            <div class="nyp-form-row">
                <label for="password">
                    Password *
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <div class="nyp-form-row">
                <label for="confirm_password">
                    Confirm Password *
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >
            </div>

            <button type="submit">
                Apply For Partner Access
            </button>

        </form>

        <?php

        return ob_get_clean();
    }

    private function display_messages()
{
    if (empty($_GET['registration'])) {
        return;
    }

    $registration = sanitize_text_field(
        wp_unslash($_GET['registration'])
    );

    if ($registration === 'success') {

        echo '
        <div class="nyp-notice nyp-notice-success">
            Thank you for your application.
            Your account has been created and is awaiting approval.
        </div>';

        return;
    }

    if ($registration !== 'failed') {
        return;
    }
    $error = isset($_GET['nyp_error']) ? sanitize_text_field( wp_unslash($_GET['nyp_error']) ) : '';



    $message = $this->get_error_message($error);

    echo '
    <div class="nyp-notice nyp-notice-error">
        ' . esc_html($message) . '
    </div>';
}

private function get_error_message($error)
{
    $messages = [

        'missing_fields' =>
            'Please fill in all required fields.',

        'invalid_email' =>
            'Please enter a valid email address.',

        'email_exists' =>
            'An account with this email address already exists.',

        'password_mismatch' =>
            'Passwords do not match.',

        'user_creation_failed' =>
            'Unable to create your account. Please try again.',

    ];

    return $messages[$error]
        ?? 'Registration failed. Please try again.';
}


}