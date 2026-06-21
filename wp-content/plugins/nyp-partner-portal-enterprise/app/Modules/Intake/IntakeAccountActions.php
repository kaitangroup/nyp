<?php

namespace NYP\Modules\Intake;

if (!defined('ABSPATH')) {
    exit;
}

class IntakeAccountActions
{
    public function register(): void
    {
        add_filter(
            'woocommerce_my_account_my_orders_actions',
            [$this, 'addIntakeAction'],
            10,
            2
        );
    }

    public function addIntakeAction(
        array $actions,
        \WC_Order $order
    ): array {

        if (
            $order->get_status() !== 'pending-intake'
        ) {
            return $actions;
        }

        $actions['complete_planning_brief'] = [
            'url'  => $this->getIntakeUrl(
                $order->get_id()
            ),
            'name' => __('Complete Planning Brief', 'nyp'),
        ];

        return $actions;
    }

    private function getIntakeUrl(
        int $orderId
    ): string {

        return add_query_arg(
            [
                'order_id' => $orderId,
            ],
            site_url('/planning-brief/')
        );
    }
}