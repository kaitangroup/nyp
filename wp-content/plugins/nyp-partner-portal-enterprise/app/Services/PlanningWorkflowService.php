<?php

declare(strict_types=1);

namespace NYP\Services;

use NYP\Services\PlanningSessionStorage;

// Ensure the PlanningSessionStorage class exists in the specified namespace
// or adjust the namespace accordingly.

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Planning Workflow Service
|--------------------------------------------------------------------------
|
| Coordinates the customer planning workflow.
|
| Product
|     ↓
| Planning Brief
|     ↓
| Checkout
|     ↓
| Payment
|
| This service contains workflow decisions only.
| It does not save orders, uploads or planning data.
|
*/

class PlanningWorkflowService
{
    /**
     * Planning session.
     *
     * @var PlanningSessionStorage
     */
    protected PlanningSessionStorage $session;

    /**
     * Constructor.
     *
     * @param PlanningSessionStorage|null $session
     */
    public function __construct(
        ?PlanningSessionStorage $session = null
    ) {
        $this->session = $session ?: new PlanningSessionStorage();
    }

    /*
    |--------------------------------------------------------------------------
    | Planning State
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a planning session exists.
     *
     * @return bool
     */
    public function hasPlanningSession(): bool
    {
        return $this->session->has();
    }

    /**
     * Determine whether the planning brief has been submitted.
     *
     * @return bool
     */
    public function isPlanningSubmitted(): bool
    {
        return $this->session->get(
            '_nyp_brief_submitted',
            'no'
        ) === 'yes';
    }

    /**
     * Determine whether checkout is allowed.
     *
     * @return bool
     */
    public function canProceedToCheckout(): bool
    {
        return $this->hasPlanningSession()
            && $this->isPlanningSubmitted();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    */

    /**
     * Get the current workflow step.
     *
     * @return string
     */
    public function currentStep(): string
    {
        if (!$this->hasPlanningSession()) {
            return 'planning';
        }

        if (!$this->isPlanningSubmitted()) {
            return 'planning';
        }

        return 'checkout';
    }

    /**
     * Get the next workflow step.
     *
     * @return string
     */
    public function nextStep(): string
    {
        if (!$this->isPlanningSubmitted()) {
            return 'planning';
        }

        return 'checkout';
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the customer should be redirected
     * to the planning brief.
     *
     * @return bool
     */
    public function shouldRedirectToPlanning(): bool
    {
        return !$this->canProceedToCheckout();
    }

    /**
     * Get the Planning Brief URL.
     *
     * NOTE:
     * Replace this implementation if the Planning Brief
     * page becomes configurable.
     *
     * @return string
     */
    public function getPlanningUrl(): string
    {
        return home_url('/planning-brief/');
    }

    /**
     * Get the Checkout URL.
     *
     * @return string
     */
    public function getCheckoutUrl(): string
    {
        return wc_get_checkout_url();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Actions
    |--------------------------------------------------------------------------
    */

    /**
 * Mark the Planning Brief as submitted.
 *
 * @return void
 */
public function markPlanningSubmitted(): void
{
    $data = $this->session->all();

    $data['_nyp_brief_submitted'] = 'yes';

    $data['_nyp_brief_submitted_at'] = current_time(
        'mysql'
    );

    $this->session->put($data);
}

/**
 * Prepare the WooCommerce cart for checkout.
 *
 * @return bool
 */
public function prepareCheckout(): bool
{
    if (!WC()->cart) {
        return false;
    }

    $productId = (int) $this->session->get(
        '_nyp_product_id'
    );

    if ($productId <= 0) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Fresh Cart
    |--------------------------------------------------------------------------
    */

    WC()->cart->empty_cart();

    /*
    |--------------------------------------------------------------------------
    | Planning Package and Service Upgrade
    |--------------------------------------------------------------------------
    */

    try {

        $added = WC()->cart->add_to_cart($productId);
    
        if (!$added) {
            return false;
        }
    
        if (
            $this->session->get('_nyp_service_speed') === 'express'
        ) {
            WC()->cart->add_to_cart(30);
        }
    
    } finally {
    
        $this->session->remove(
            '_nyp_allow_cart_add'
        );
    
    }

    return true;
}

    /**
     * Reset the planning workflow.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->session->clear();
    }
}