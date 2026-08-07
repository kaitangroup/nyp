<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

use WC_Order;

defined('ABSPATH') || exit;

class PlanningFileAuthorizer
{
    /**
     * Determine whether a user may download files
     * belonging to the given planning order.
     *
     * @param WC_Order $order
     * @param int      $userId
     *
     * @return bool
     */
    public function canDownload(
        WC_Order $order,
        int $userId
    ): bool {

        if ($userId <= 0) {
            return false;
        }

        $user = get_user_by(
            'id',
            $userId
        );

        if (!$user) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Administrators
        |--------------------------------------------------------------------------
        */

        if (
            user_can($user, 'manage_options')
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Shop Managers
        |--------------------------------------------------------------------------
        */

        if (
            user_can($user, 'manage_woocommerce')
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Customer who owns the order
        |--------------------------------------------------------------------------
        */

        if (
            (int) $order->get_user_id() === $userId
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Future Partner Ownership
        |--------------------------------------------------------------------------
        |
        | Milestone 5:
        | Add support for verifying whether an NYP partner
        | is assigned to this planning request.
        |
        */

        return apply_filters(
            'nyp_planning_file_can_download',
            false,
            $order,
            $user
        );
    }
}