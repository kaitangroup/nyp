<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\Emails;

use WC_Email;
use WC_Order;

defined('ABSPATH') || exit;

class CustomerPlanningReceivedEmail extends WC_Email
{
    public function __construct()
    {
        $this->id             = 'nyp_customer_planning_received';
        $this->customer_email = true;

        $this->title = __(
            'Planungsanfrage eingegangen',
            'nyp'
        );

        $this->description = __(
            'Wird an Partner gesendet, nachdem die Zahlung eingegangen ist und die Planungsanfrage auf die Prüfung durch NYP wartet.',
            'nyp'
        );

        $this->heading = __(
            'Ihre Planungsanfrage ist bei uns eingegangen',
            'nyp'
        );

        $this->subject = __(
            '[{site_title}] Ihre Planungsanfrage ist eingegangen (Bestellung #{order_number})',
            'nyp'
        );

        $this->template_html  = 'customer-planning-received.php';
        $this->template_plain = 'plain/customer-planning-received.php';

        $this->template_base = NYP_PLUGIN_PATH . 'app/Modules/Intake/Emails/templates/';

        /*
        |--------------------------------------------------------------------------
        | Email Placeholders
        |--------------------------------------------------------------------------
        */

        $this->placeholders = [
            '{site_title}'   => $this->get_blogname(),
            '{order_number}' => '',
        ];

        parent::__construct();
    }

    /**
     * Trigger the email.
     */
    public function trigger(int $orderId): void
    {
        if (!$orderId) {
            return;
        }

        $this->object = wc_get_order($orderId);

        if (!$this->object instanceof WC_Order) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Populate placeholders
        |--------------------------------------------------------------------------
        */

        $this->placeholders['{order_number}'] =
            $this->object->get_order_number();

        $this->recipient =
            $this->object->get_billing_email();

        if (
            !$this->is_enabled() ||
            !$this->get_recipient()
        ) {
            return;
        }

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    /**
     * HTML content.
     */
    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'email'         => $this,
                'sent_to_admin' => false,
                'plain_text'    => false,
            ],
            '',
            $this->template_base
        );
    }

    /**
     * Plain text content.
     */
    public function get_content_plain(): string
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'email'         => $this,
                'sent_to_admin' => false,
                'plain_text'    => true,
            ],
            '',
            $this->template_base
        );
    }

    /**
     * Default content.
     */
    public function get_default_content(): string
    {
        return __(
            'Vielen Dank für Ihre Bestellung. Wir haben Ihre Zahlung und Ihre Planungsanfrage erfolgreich erhalten. NYP Kitchen Design prüft nun Ihre eingereichten Projektunterlagen sowie die gewählte Planungskategorie.',
            'nyp'
        );
    }
}