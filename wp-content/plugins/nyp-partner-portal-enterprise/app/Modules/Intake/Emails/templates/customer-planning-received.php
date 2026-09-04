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
            'Hallo %s,',
            'nyp'
        ),
        esc_html($order->get_billing_first_name())
    );
    ?>
</p>

<p>
    <?php esc_html_e(
        'Vielen Dank, dass Sie sich für NYP Kitchen Design entschieden haben.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'Wir haben Ihre Zahlung und Ihre Planungsanfrage erfolgreich erhalten.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'Unser Planungsteam prüft nun Ihre eingereichten Unterlagen. Nach erfolgreicher Prüfung wird Ihr Projekt freigegeben und der Planungsprozess gestartet.',
        'nyp'
    ); ?>
</p>

<h2>
    <?php esc_html_e(
        'Bestelldetails',
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
        'Wenn Sie Fragen zu Ihrer Planungsanfrage haben, antworten Sie einfach auf diese E-Mail. Unser Team hilft Ihnen gerne weiter.',
        'nyp'
    ); ?>
</p>

<p>
    <?php esc_html_e(
        'Mit freundlichen Grüßen,',
        'nyp'
    ); ?>
    <br>
    <strong>NYP Kitchen Design</strong>
</p>

<?php
do_action(
    'woocommerce_email_footer',
    $email
);
?>