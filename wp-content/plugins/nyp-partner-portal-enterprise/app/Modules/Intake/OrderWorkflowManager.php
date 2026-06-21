<?php

namespace NYP\Modules\Intake;

class OrderWorkflowManager
{
    public function register(): void
    {
        add_action(
            'woocommerce_order_status_processing',
            [$this, 'moveToPendingIntake']
        );
    }

    public function moveToPendingIntake($orderId): void
    {
        $order = wc_get_order($orderId);

        if (!$order) {
            return;
        }

        $order->update_status(
            'pending-intake',
            'Waiting for planning brief.'
        );
    }
}