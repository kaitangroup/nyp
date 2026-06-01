<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' );
// translators: WooCommerce shipping classes URL.
$desc = sprintf( __( 'These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) );
$desc .= ' ' . __( 'If no costs are set for shipping classes, costs defined in Packlink PRO configuration will be used.', 'packlink-pro-shipping' );

/**
 * Settings for Packlink PRO shipping.
 */
$settings = array(
	'title'        => array(
		'title'       => __( 'Method title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Packlink Shipping', 'packlink_pro_shipping' ),
		'desc_tip'    => true,
	),
	'price_policy' => array(
		'title'       => __( 'Pricing policy', 'packlink_pro_shipping' ),
		'type'        => 'text',
		'description' => __( 'Pricing policy selected in Packlink PRO Shipping settings.', 'packlink_pro_shipping' ),
		'default'     => isset( $this->settings['price_policy'] ) ? $this->settings['price_policy'] : __( 'Packlink prices', 'packlink_pro_shipping' ),
		'desc_tip'    => true,
	),
	'tax_status'   => array(
		'title'   => __( 'Tax status', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'taxable',
		'options' => array(
			'taxable' => __( 'Taxable', 'woocommerce' ),
			'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
		),
	),
);

$shipping_classes = WC()->shipping->get_shipping_classes();

if ( ! empty( $shipping_classes ) ) {
	$settings['class_costs'] = array(
		'title'       => __( 'Shipping class costs', 'woocommerce' ),
		'type'        => 'title',
		'default'     => '',
		'description' => $desc,
	);

	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}

		$settings[ 'class_cost_' . $shipping_class->term_id ] = array(
			/* translators: %s: shipping class name */
			'title'       => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'        => 'text',
			'placeholder' => __( 'N/A', 'woocommerce' ),
			'description' => $cost_desc,
			'default'     => $this->get_option( 'class_cost_' . $shipping_class->slug ),
			'desc_tip'    => true,
		);
	}

	$settings['class_cost_calculation_type'] = array(
		'title'   => __( 'Calculation type', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'class',
		'options' => array(
			'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
			'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
		),
	);
}

return $settings;
