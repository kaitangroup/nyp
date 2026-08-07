<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WC_Order;

defined('ABSPATH') || exit;

class CalWebhookController
{
    /**
     * Register REST endpoint.
     */
    public function register(): void
    {
        add_action(
            'rest_api_init',
            [$this, 'registerRoutes']
        );
    }

    /**
     * Register routes.
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            'nyp/v1',
            '/cal/webhook',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleWebhook'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(
        WP_REST_Request $request
    ): WP_REST_Response|WP_Error {

        $payload = $request->get_json_params();
        $payload = $request->get_json_params();

error_log(
    '================ CAL WEBHOOK ================'
);

error_log(
    wp_json_encode(
        $payload,
        JSON_PRETTY_PRINT
    )
);

error_log(
    '============================================='
);

        if (
            empty($payload)
        ) {

            return new WP_Error(
                'invalid_payload',
                __('Invalid webhook payload.', 'nyp'),
                [
                    'status' => 400,
                ]
            );

        }

        /*
        |--------------------------------------------------------------------------
        | TODO
        | Verify webhook signature
        |--------------------------------------------------------------------------
        */

        // $this->verifySignature($request);

        $event = strtoupper(
            (string) (
                $payload['triggerEvent']
                ?? ''
            )
        );

        switch ($event) {

            case 'BOOKING_CREATED':

                $this->bookingCreated(
                    $payload
                );

                break;

            case 'BOOKING_CANCELLED':

                $this->bookingCancelled(
                    $payload
                );

                break;

            default:

                error_log(
                    'Cal.com webhook ignored: '
                    . $event
                );

        }

        return new WP_REST_Response(
            [
                'success' => true,
            ],
            200
        );
    }

    /**
 * Booking created.
 */
protected function bookingCreated(
    array $payload
): void {

    $booking = $payload['payload'] ?? [];

    $metadata = $booking['metadata'] ?? [];

    $orderId = absint(
        $metadata['order_id'] ?? 0
    );

    if (!$orderId) {

        error_log(
            'Cal.com booking missing order_id.'
        );

        return;
    }

    $order = wc_get_order(
        $orderId
    );

    if (
        !$order instanceof WC_Order
    ) {

        error_log(
            sprintf(
                'Order #%d not found.',
                $orderId
            )
        );

        return;
    }

    $order->update_meta_data(
        '_nyp_cal_booking_id',
        $booking['bookingId'] ?? ''
    );

    $order->update_meta_data(
        '_nyp_cal_booking_uid',
        $booking['uid'] ?? ''
    );

    $order->update_meta_data(
        '_nyp_cal_booking_status',
        strtolower(
            $booking['status'] ?? 'scheduled'
        )
    );

    $order->update_meta_data(
        '_nyp_cal_booking_start',
        $booking['startTime'] ?? ''
    );

    $order->update_meta_data(
        '_nyp_cal_booking_end',
        $booking['endTime'] ?? ''
    );

    $order->update_meta_data(
        '_nyp_cal_booking_url',
        $booking['videoCallData']['url'] ?? ''
    );

    $order->save();

    error_log(
        'Booking ID after save: ' .
        $order->get_meta('_nyp_cal_booking_id')
    );
    
    error_log(
        'Status after save: ' .
        $order->get_meta('_nyp_cal_booking_status')
    );

    error_log(
        sprintf(
            'Cal.com booking #%s saved for order #%d',
            $booking['bookingId'] ?? '',
            $orderId
        )
    );
}

    /**
     * Booking cancelled.
     */
    protected function bookingCancelled(
        array $payload
    ): void {

        $booking = $payload['payload'] ?? [];

        $metadata = $booking['metadata'] ?? [];

        $orderId = absint(
            $metadata['order_id'] ?? 0
        );

        if (!$orderId) {
            return;
        }

        $order = wc_get_order(
            $orderId
        );

        if (
            !$order instanceof WC_Order
        ) {
            return;
        }

        $order->update_meta_data(
            '_nyp_cal_booking_status',
            'cancelled'
        );

        $order->save();

        error_log(
            sprintf(
                'Cal.com booking cancelled for order #%d',
                $orderId
            )
        );
    }
}