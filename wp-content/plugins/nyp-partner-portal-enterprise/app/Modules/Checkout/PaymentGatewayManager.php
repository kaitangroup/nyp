<?php

declare(strict_types=1);

namespace NYP\Modules\Checkout;

use NYP\Services\PlanningSessionStorage;

defined('ABSPATH') || exit;

class PaymentGatewayManager
{
    protected PlanningSessionStorage $session;

    public function __construct(
        PlanningSessionStorage $session
    ) {
        $this->session = $session;
    }

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'filterGateways']
        );
    }

    /**
     * Restrict payment gateways.
     *
     * Express Planning
     * → Stripe only.
     */
    public function filterGateways(
        array $gateways
    ): array {
       

        if (
            is_admin() 
        ) {
            return $gateways;
        }

        if (!$this->isExpressCheckout()) {
            return $gateways;
        }

        foreach ($gateways as $id => $gateway) {

            if (
                strpos(
                    $id,
                    'stripe'
                ) === false
            ) {
                unset($gateways[$id]);
            }
        }

        return $gateways;
    }

    protected function isExpressCheckout(): bool
{
    if (!WC()->cart) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $item) {

        if ((int) $item['product_id'] === 30) {
            return true;
        }
    }

    return false;
}
}