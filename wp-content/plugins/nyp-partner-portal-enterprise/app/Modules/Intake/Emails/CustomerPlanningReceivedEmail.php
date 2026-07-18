<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\Emails;

use WC_Email;
use WC_Order;

defined('ABSPATH') || exit;

class CustomerPlanningReceivedEmail extends WC_Email
{
    /**
     * @var WC_Order|null
     */
   

    public function __construct()
    {
        $this->id             = 'nyp_customer_planning_received';
        $this->customer_email = true;

        $this->title = __(
            'Planning Received',
            'nyp'
        );

        $this->description = __(
            'Sent to customers after payment has been received and their planning request is awaiting NYP review.',
            'nyp'
        );

        $this->heading = __(
            'We have received your planning request',
            'nyp'
        );

        $this->subject = __(
            '[{site_title}] We have received your planning request (Order #{order_number})',
            'nyp'
        );

        $this->template_html  = 'customer-planning-received.php';
        $this->template_plain = 'plain/customer-planning-received.php';

  
        $this->template_base = NYP_PLUGIN_PATH . 'app/Modules/Intake/Emails/templates/';
        parent::__construct();
    }

    /**
     * Trigger the email.
     *
     * @param int $orderId
     *
     * @return void
     */
    public function trigger(
        int $orderId
    ): void {

        if (!$orderId) {
            return;
        }

        $this->object = wc_get_order(
            $orderId
        );

        if (!$this->object instanceof WC_Order) {
            return;
        }

        $this->recipient = $this->object->get_billing_email();

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
            'Thank you for your order. We have received your payment and planning request. Our team will now review your submission before beginning the planning process.',
            'nyp'
        );
    }
}