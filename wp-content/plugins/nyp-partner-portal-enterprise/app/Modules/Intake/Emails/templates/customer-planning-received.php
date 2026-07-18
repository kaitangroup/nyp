<?php

defined('ABSPATH') || exit;

/**
 * @var WC_Order $order
 * @var WC_Email $email
 * @var string $email_heading
 */

do_action(
    'woocommerce_email_header',
    $email_heading,
    $email
);
?>

<p>
    <?php
    printf(
        esc_html__(
            'Hi %s,',
            'nyp'
        ),
        esc_html($order->get_billing_first_name())
    );
    ?>
</p>

<p>
    <?php esc_html_e(
        'Thank you for choosing NYP Digital Kitchen.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'We have successfully received your payment and your planning request.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'Our planning team will now review your submission. Once the review is complete, we will begin preparing your kitchen design.',
        'nyp'
    ); ?>
</p>

<h2>
    <?php esc_html_e(
        'Order Details',
        'nyp'
    ); ?>
</h2>

<?php
do_action(
    'woocommerce_email_order_details',
    $order,
    false,
    false,
    $email
);

do_action(
    'woocommerce_email_order_meta',
    $order,
    false,
    false,
    $email
);

do_action(
    'woocommerce_email_customer_details',
    $order,
    false,
    false,
    $email
);
?>

<p>
    <?php esc_html_e(
        'If you have any questions, simply reply to this email and our team will be happy to assist you.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'Kind regards,',
        'nyp'
    ); ?>
    <br>
    <strong><?php bloginfo('name'); ?></strong>
</p>

<?php
do_action(
    'woocommerce_email_footer',
    $email
);