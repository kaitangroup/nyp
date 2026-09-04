<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\Emails;

use WC_Emails;
use WC_Order;
use NYP\Modules\Intake\Emails\CustomerPlanningReceivedEmail;
use NYP\Modules\Intake\Emails\PartnerApprovedEmail;

class EmailManager
{
    public function register(): void
    {

        (new PartnerApprovedEmail())->register();
        add_filter(
            'woocommerce_email_classes',
            [$this, 'registerEmailClasses']
        );

        add_action(
            'woocommerce_order_status_awaiting-review',
            [$this, 'sendPlanningReceivedEmail']
        );
    }

    /**
     * Register custom WooCommerce email classes.
     *
     * @param array $emails
     *
     * @return array
     */
    public function registerEmailClasses(
        array $emails
    ): array {

        $emails['NYP_Customer_Planning_Received'] =
            new CustomerPlanningReceivedEmail();

        return $emails;
    }

    /**
     * Trigger the customer planning received email.
     *
     * @param int $orderId
     *
     * @return void
     */
    public function sendPlanningReceivedEmail(
        int $orderId
    ): void {

        $order = wc_get_order($orderId);

        if (!$order instanceof WC_Order) {
            return;
        }

        /**
         * @var WC_Emails $mailer
         */
        $mailer = WC()->mailer();

        $emails = $mailer->get_emails();

        /** @var CustomerPlanningReceivedEmail|null $email */
        $email = $emails['NYP_Customer_Planning_Received'] ?? null;

        if ($email) {
            $email->trigger($orderId);
        }
    }
}