<?php

namespace NYP\Modules\PartnerAccess;

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
                    Sie sind bereits angemeldet.
                </div>
            ';
        }

        ob_start();

        $this->display_messages();
        ?>

        <div class="nyp-registration-intro">
            <h2>Partner werden</h2>

            <p>
                Registrieren Sie Ihr Küchenstudio oder Unternehmen für den
                NYP Partnerbereich. Nach Prüfung Ihrer Angaben schalten wir
                Ihren Zugang frei. Erst nach Freischaltung erhalten Sie Zugriff
                auf Preise, Planungsanfragen und das Partnerportal.
            </p>
        </div>

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
                    Firmenname *
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
                    Ansprechpartner *
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
                    E-Mail-Adresse *
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
                    Telefonnummer *
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
                    USt-IdNr. / Steuernummer
                </label>

                <input
                    type="text"
                    id="vat_number"
                    name="vat_number"
                >
            </div>

            <div class="nyp-form-row">
                <label for="message">
                    Nachricht
                </label>

                <textarea
                    id="message"
                    name="message"
                    rows="4"
                ></textarea>
            </div>

            <div class="nyp-form-row">
                <label for="password">
                    Passwort *
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
                    Passwort bestätigen *
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >
            </div>

            <div class="nyp-form-row nyp-checkbox-row">
                <label>
                    <input
                        type="checkbox"
                        name="privacy_consent"
                        value="1"
                        required
                    >

                    Ich habe die Datenschutzerklärung gelesen und stimme der
                    Verarbeitung meiner Angaben zur Prüfung meines Partnerzugangs zu.
                </label>
            </div>

            <button type="submit">
                Partnerzugang beantragen
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
                Vielen Dank für Ihre Registrierung.<br>
                Ihr Partnerkonto wurde erstellt und wartet auf Freischaltung durch NYP Kitchen Design.
            </div>';

            return;
        }

        if ($registration !== 'failed') {
            return;
        }

        $error = isset($_GET['nyp_error'])
            ? sanitize_text_field(wp_unslash($_GET['nyp_error']))
            : '';

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
                'Bitte füllen Sie alle Pflichtfelder aus.',

            'invalid_email' =>
                'Bitte geben Sie eine gültige E-Mail-Adresse ein.',

            'email_exists' =>
                'Für diese E-Mail-Adresse existiert bereits ein Konto.',

            'password_mismatch' =>
                'Die Passwörter stimmen nicht überein.',

            'user_creation_failed' =>
                'Ihr Konto konnte nicht erstellt werden. Bitte versuchen Sie es erneut.',

        ];

        return $messages[$error]
            ?? 'Die Registrierung ist fehlgeschlagen. Bitte versuchen Sie es erneut.';
    }
}