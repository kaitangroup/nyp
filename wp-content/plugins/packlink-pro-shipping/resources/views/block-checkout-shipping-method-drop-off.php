<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Packlink\WooCommerce\Components\Checkout\Block_Checkout_Handler;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Checkout handler.
 *
 * @var Block_Checkout_Handler $this
 */

$button_label = __( 'Select Drop-Off Location', 'packlink-pro-shipping' );

if ( ! is_cart() ) {
	include dirname( __DIR__ ) . '/../resources/templates/custom/location-picker.html';
}
?>

<input type="hidden" id="pl-block-checkout-initialize-endpoint"
	   value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'initialize_block_checkout' ); ?>"/>
<input type="hidden" id="pl-block-checkout-save-selected"
	   value="<?php echo Shop_Helper::get_controller_url( 'Checkout', 'save_selected' ); ?>"/>
