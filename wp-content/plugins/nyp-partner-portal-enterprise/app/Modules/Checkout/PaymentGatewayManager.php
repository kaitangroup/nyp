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
            is_admin() ||
            !is_checkout()
        ) {
            return $gateways;
        }

        if (
            $this->session->get(
                '_nyp_service_speed'
            ) !== 'express'
        ) {
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
}