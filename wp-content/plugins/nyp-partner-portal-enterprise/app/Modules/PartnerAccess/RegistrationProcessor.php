<?php

namespace NYP\Modules\PartnerAccess;
use WP_User;

class RegistrationProcessor
{
    public function register()
    {
        add_action(
            'init',
            [$this, 'process_registration']
        );
    }

    public function process_registration()
    {
        if (
            !isset($_POST['nyp_partner_registration']) ||
            empty($_POST['nyp_partner_registration'])
        ) {
            return;
        }

        /*
         * Verify nonce
         */
        if (
            !isset($_POST['nyp_partner_registration_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['nyp_partner_registration_nonce'])
                ),
                'nyp_partner_registration'
            )
        ) {
            wp_die('Security check failed.');
        }

        $company_name = sanitize_text_field(
            $_POST['company_name'] ?? ''
        );

        $contact_person = sanitize_text_field(
            $_POST['contact_person'] ?? ''
        );

        $email = sanitize_email(
            $_POST['email'] ?? ''
        );

        $phone = sanitize_text_field(
            $_POST['phone'] ?? ''
        );

        $website = esc_url_raw(
            $_POST['website'] ?? ''
        );

        $vat_number = sanitize_text_field(
            $_POST['vat_number'] ?? ''
        );

        $message = sanitize_textarea_field(
            $_POST['message'] ?? ''
        );

        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        /*
         * Required validation
         */
        if (
            empty($company_name) ||
            empty($contact_person) ||
            empty($email) ||
            empty($phone) ||
            empty($password)
        ) {

            $this->redirect_with_error(
                'missing_fields'
            );
        }

        /*
         * Email validation
         */
        if (!is_email($email)) {

            $this->redirect_with_error(
                'invalid_email'
            );
        }

        /*
         * Existing user
         */
        if (email_exists($email)) {

            $this->redirect_with_error(
                'email_exists'
            );
        }

        /*
         * Password match
         */
        if ($password !== $confirm_password) {

            $this->redirect_with_error(
                'password_mismatch'
            );
        }

        /*
         * Generate username
         */
        $username = sanitize_user(
            current(
                explode('@', $email)
            )
        );

        if (username_exists($username)) {

            $username .= '_' . wp_rand(100, 999);
        }

        /*
         * Create user
         */
        $user_id = wp_create_user(
            $username,
            $password,
            $email
        );

        if (is_wp_error($user_id)) {

            $this->redirect_with_error(
                'user_creation_failed'
            );
        }

        /*
         * Assign role
         */
        $user = new WP_User($user_id);

        $user->set_role('nyp_partner');

        /*
         * Save meta
         */
        update_user_meta(
            $user_id,
            'nyp_partner_status',
            'pending'
        );

        update_user_meta(
            $user_id,
            'nyp_company_name',
            $company_name
        );

        update_user_meta(
            $user_id,
            'nyp_contact_person',
            $contact_person
        );

        update_user_meta(
            $user_id,
            'nyp_phone',
            $phone
        );

        update_user_meta(
            $user_id,
            'nyp_website',
            $website
        );

        update_user_meta(
            $user_id,
            'nyp_vat_number',
            $vat_number
        );

        update_user_meta(
            $user_id,
            'nyp_message',
            $message
        );

        /*
         * Success
         */
        wp_safe_redirect(
            add_query_arg(
                'registration',
                'success',
                wp_get_referer()
            )
        );

        exit;
    }

    private function redirect_with_error(
        $code
    ) {
        wp_safe_redirect(
            add_query_arg(
                [
                    'registration' => 'failed',
                    'nyp_error' => $code,
                ],
                wp_get_referer()
            )
        );

        exit;
    }
}