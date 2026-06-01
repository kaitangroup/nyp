<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\WooCommerce\Components\Utility\Shop_Helper;

$src = Shop_Helper::get_plugin_base_url() . 'resources/images/logo.png';
?>

<div class="pl-hidden-fields-container">
	<input type="hidden" id="pl-create-endpoint"
		   value="<?php echo Shop_Helper::get_controller_url( 'Order_Details', 'create_draft' ); ?>"/>
	<input type="hidden" id="pl-check-manual-sync-status"
		   value="<?php echo Shop_Helper::get_controller_url( 'Manual_Sync', 'is_manual_sync_enabled' ); ?>"/>
	<input type="hidden" id="pl-check-status"
		   value="<?php echo Shop_Helper::get_controller_url( 'Order_Overview', 'get_draft_status' ); ?>"/>

	<input type="hidden" id="pl-draft-in-progress"
		   value="<?php echo __( 'Draft is currently being created.', 'packlink-pro-shipping' ) ?>"/>
	<input type="hidden" id="pl-draft-failed"
		   value="<?php echo __( 'Previous attempt to create a draft failed.', 'packlink-pro-shipping' ) ?>"/>
	<input type="hidden" id="pl-no-shippable-items"
		   value="<?php echo __( 'This order contains only virtual products and cannot be shipped.', 'packlink-pro-shipping' ); ?>"/>

	<a target="_blank" id="pl-draft-button-template" class="button pl-draft-button hidden">
		<img class="pl-image" src="<?php echo esc_url( $src ) ?>" alt="">
		<?php echo __( 'View on Packlink', 'packlink-pro-shipping' ) ?>
	</a>

	<button id="pl-create-draft-template" class="button pl-draft-button hidden">
		<img class="pl-image" src="<?php echo esc_url( $src ) ?>" alt="">
		<?php echo __( 'Send with Packlink', 'packlink-pro-shipping' ) ?>
	</button>
</div>
