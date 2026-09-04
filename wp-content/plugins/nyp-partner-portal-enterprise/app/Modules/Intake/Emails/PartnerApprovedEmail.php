<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\Emails;

defined('ABSPATH') || exit;

class PartnerApprovedEmail
{
    public function register(): void
    {
        add_action(
            'nyp_partner_approved',
            [$this, 'send'],
            10,
            1
        );
    }

    public function send(int $user_id): void
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        $to = $user->user_email;

        $subject = 'Ihr NYP Partnerkonto wurde freigeschaltet';

        $login_url = wc_get_page_permalink('myaccount');

        $message = '
        <div style="font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#333333;max-width:600px;margin:0 auto;">

            <h2 style="color:#222222;">Willkommen bei NYP Kitchen Design</h2>

            <p>Hallo ' . esc_html($user->display_name) . ',</p>

            <p>Ihr Partnerkonto wurde erfolgreich freigeschaltet.</p>

            <p>Sie können jetzt:</p>

            <ul>
                <li>Preise für unsere Planungsleistungen einsehen</li>
                <li>Neue Planungsanfragen starten</li>
                <li>Auf Ihr Partnerportal zugreifen</li>
                <li>Bestellungen und Rechnungen verwalten</li>
            </ul>

            <p style="margin:30px 0;">
                <a href="' . esc_url($login_url) . '"
                   style="
                        background:#C9962F;
                        color:#FFFFFF;
                        text-decoration:none;
                        padding:12px 22px;
                        border-radius:6px;
                        display:inline-block;
                        font-weight:600;
                   ">
                    Zum Partnerportal
                </a>
            </p>

            <p>Vielen Dank für Ihre Partnerschaft mit <strong>NYP Kitchen Design</strong>.</p>

            <p>
                Mit freundlichen Grüßen<br>
                <strong>NYP Kitchen Design</strong>
            </p>

        </div>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        wp_mail(
            $to,
            $subject,
            $message,
            $headers
        );
    }
}