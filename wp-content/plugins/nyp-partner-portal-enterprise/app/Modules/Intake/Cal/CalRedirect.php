<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\Cal;

use WC_Order;

defined('ABSPATH') || exit;

class CalRedirect
{
    public function register(): void
    {
        add_action(
            'woocommerce_thankyou',
            [$this, 'renderRedirect'],
            99
        );

        add_action(
            'template_redirect',
            [$this, 'protectExpressConsultation']
        );

        add_shortcode(
            'nyp_express_booking',
            [$this, 'renderBooking']
        );

        // add_filter(
        //     'woocommerce_my_account_my_orders_actions',
        //     [$this, 'consultationAction'],
        //     20,
        //     2
        // );
    }


    /**
 * Render the Cal.com booking widget.
 */
public function renderBooking(): string
{
    $orderId = absint(
        $_GET['order'] ?? 0
    );

    if (!$orderId) {
        return '';
    }

    $order = wc_get_order(
        $orderId
    );

    if (
        !$order instanceof WC_Order
    ) {
        return '';
    }

    ob_start();

    ?>

    <p>

        Thank you for your purchase.

        Your Express Planning package has been activated.

        Please select a convenient appointment time below.

    </p>

    <div id="my-cal-inline"></div>

    <script>

    (function (C, A, L) {

        let p = function (a, ar) {
            a.q.push(ar);
        };

        let d = C.document;

        C.Cal = C.Cal || function () {

            let cal = C.Cal;

            let ar = arguments;

            if (!cal.loaded) {

                cal.ns = {};

                cal.q = cal.q || [];

                d.head.appendChild(
                    d.createElement("script")
                ).src = A;

                cal.loaded = true;
            }

            if (ar[0] === L) {

                const api = function () {
                    p(api, arguments);
                };

                const namespace = ar[1];

                api.q = api.q || [];

                if (typeof namespace === "string") {

                    cal.ns[namespace] =
                        cal.ns[namespace] || api;

                    p(cal.ns[namespace], ar);

                    p(cal, [
                        "initNamespace",
                        namespace
                    ]);

                } else {

                    p(cal, ar);

                }

                return;
            }

            p(cal, ar);

        };

    })(

        window,

        "https://app.cal.com/embed/embed.js",

        "init"

    );

    Cal("init", "express");

    Cal.ns.express("inline", {

        elementOrSelector:"#my-cal-inline",

        calLink:"nyp-kitchen-dev/express-kitchen-planning",

        layout:"month_view",

        metadata:{

            order_id:
                <?php echo (int) $orderId; ?>,

            order_number:
                <?php echo wp_json_encode(
                    $order->get_order_number()
                ); ?>,

            customer_email:
                <?php echo wp_json_encode(
                    $order->get_billing_email()
                ); ?>

        }

    });

    Cal.ns.express("ui", {

        hideEventTypeDetails:false,

        layout:"month_view"

    });

    </script>

    <?php

    return ob_get_clean();
}


    public function consultationAction(
        array $actions,
        WC_Order $order
    ): array {
    
        /*
        |--------------------------------------------------------------------------
        | Express only
        |--------------------------------------------------------------------------
        */
    
        if (
            
            $order->get_meta('_nyp_service_speed') !== 'express'
        ) {
            return $actions;
        }
    
        /*
        |--------------------------------------------------------------------------
        | Already scheduled?
        |--------------------------------------------------------------------------
        */
    
        if (
            $order->get_meta('_nyp_cal_booking_id')
        ) {
    
            $actions['consultation'] = [
    
                'url' => '#',
    
                'name' => sprintf(
                    __('Consultation Scheduled (%s)', 'nyp'),
                    $order->get_meta(
                        '_nyp_cal_booking_date'
                    )
                ),
    
            ];
    
            return $actions;
        }
    
        $url = add_query_arg(
            [
                'order' => $order->get_id(),
                '_wpnonce' => wp_create_nonce(
                    'nyp_express_booking_' . $order->get_id()
                ),
            ],
            home_url('/schedule-express-consultation/')
        );
    
        $actions['consultation'] = [
    
            'url' => $url,
    
            'name' => __('Schedule Consultation', 'nyp'),
    
        ];
    
        return $actions;
    }


/**
 * Protect the Express Consultation page.
 */
public function protectExpressConsultation(): void
{
    if (
        !is_page('schedule-express-consultation')
    ) {
        return;
    }

    if (
        !is_user_logged_in()
    ) {
        auth_redirect();
    }

    $orderId = absint(
        $_GET['order'] ?? 0
    );

    if (!$orderId) {

        wp_die(
            esc_html__(
                'Invalid consultation request.',
                'nyp'
            ),
            '',
            [
                'response' => 403,
            ]
        );

    }

    

    /*
    |--------------------------------------------------------------------------
    | Verify nonce
    |--------------------------------------------------------------------------
    */

    $nonce = sanitize_text_field(
        $_GET['_wpnonce'] ?? ''
    );

    if (
        !wp_verify_nonce(
            $nonce,
            'nyp_express_booking_' . $orderId
        )
    ) {

        wp_die(
            esc_html__(
                'Invalid booking link.',
                'nyp'
            ),
            '',
            [
                'response' => 403,
            ]
        );

    }

    $order = wc_get_order(
        $orderId
    );

    if (
        !$order instanceof WC_Order
    ) {

        wp_die(
            esc_html__(
                'Order not found.',
                'nyp'
            ),
            '',
            [
                'response' => 404,
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Owner or admin only
    |--------------------------------------------------------------------------
    */

    if (

        !current_user_can(
            'manage_woocommerce'
        )

        &&

        $order->get_user_id()
        !== get_current_user_id()

    ) {

        wp_die(
            esc_html__(
                'Access denied.',
                'nyp'
            ),
            '',
            [
                'response' => 403,
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Payment required
    |--------------------------------------------------------------------------
    */

    // if (
    //     !$order->is_paid()
    // ) {

    //     wp_die(
    //         esc_html__(
    //             'Payment has not been completed.',
    //             'nyp'
    //         ),
    //         '',
    //         [
    //             'response' => 403,
    //         ]
    //     );

    // }

    /*
    |--------------------------------------------------------------------------
    | Express only
    |--------------------------------------------------------------------------
    */

    if (

        $order->get_meta(
            '_nyp_service_speed'
        )

        !== 'express'

    ) {

        wp_die(
            esc_html__(
                'This order is not eligible for Express Consultation.',
                'nyp'
            ),
            '',
            [
                'response' => 403,
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Already booked
    |--------------------------------------------------------------------------
    */

    if (

        !empty(

            $order->get_meta(
                '_nyp_cal_booking_id'
            )

        )

    ) {

        wp_safe_redirect(

            wc_get_endpoint_url(
                'view-order',
                (string) $orderId,
                wc_get_page_permalink(
                    'myaccount'
                )
            )

        );

        exit;

    }
}


    /**
     * Redirect Express customers after payment.
     */
    public function renderRedirect(
        int $orderId
    ): void {

        if (!$orderId) {
            return;
        }

        $order = wc_get_order(
            $orderId
        );

        if (!$order instanceof WC_Order) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Only Express Orders
        |--------------------------------------------------------------------------
        */

        if (
            $order->get_meta('_nyp_service_speed')
            !== 'express'
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Skip if already booked
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $order->get_meta(
                    '_nyp_cal_booking_id'
                )
            )
        ) {
            return;
        }

        $redirectUrl = add_query_arg(
            [
                'order' => $order->get_id(),
                '_wpnonce' => wp_create_nonce(
                    'nyp_express_booking_' . $order->get_id()
                ),
            ],
            home_url('/schedule-express-consultation/')
        );

        ?>

        <div
            class="woocommerce-message"
            style="margin-top:30px;"
        >

            <strong>
                Express Planning
            </strong>

            <p>
                Your payment has been received successfully.
                You will now be redirected to schedule your
                Express consultation.
            </p>

            <p>
                Redirecting in
                <span id="nyp-countdown">5</span>
                seconds...
            </p>

            <p>

                <a
                    class="button"
                    href="<?php echo esc_url(
                        $redirectUrl
                    ); ?>"
                >
                    Schedule Now
                </a>

            </p>

        </div>

        <script>

        (function(){

            let seconds = 5;

            const countdown =
                document.getElementById(
                    'nyp-countdown'
                );

            const timer = setInterval(function(){

                seconds--;

                if(countdown){

                    countdown.textContent =
                        seconds;

                }

                if(seconds <= 0){

                    clearInterval(timer);

                    window.location.href = <?php echo json_encode($redirectUrl); ?>;

                }

            },1000);

        })();

        </script>

        <?php
    }
}