<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Checkout\Checkout_Handler;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Shipping method model.
 *
 * @var ShippingMethod $shipping_method
 */
/**
 * Checkout handler.
 *
 * @var Checkout_Handler $this
 */

$id_value     = wc()->session->get( Shipping_Method_Helper::DROP_OFF_ID, '' );
$button_label = $id_value ? __( 'Change Drop-Off Location', 'packlink-pro-shipping' ) : __( 'Select Drop-Off Location', 'packlink-pro-shipping' );
$parts        = explode( '_', get_locale() );
$locale       = $parts[0];

$translations = array(
	'pickDropOff'   => __( 'Select Drop-Off Location', 'packlink-pro-shipping' ),
	'changeDropOff' => __( 'Change Drop-Off Location', 'packlink-pro-shipping' ),
	'dropOffTitle'  => __( 'Package will be delivered to:', 'packlink-pro-shipping' ),
);

$locations = $this->get_drop_off_locations( $shipping_method->getId() );
$no_drop_off_locations_message = $this->get_drop_off_locations_missing_message();

if ( $id_value ) {
	$location_ids = array_column( $locations, 'id' );
	if ( ! in_array( $id_value, $location_ids, true ) ) {
		$button_label = __( 'Select Drop-Off Location', 'packlink-pro-shipping' );
	}
}

if ( ! is_cart() ) {
	include dirname( __DIR__ ) . '/../resources/templates/custom/location-picker.html';
}
?>

<script style="display: none;">
	Packlink.checkout.setLocale('<?php echo $locale; ?>');
	Packlink.checkout.setTranslations(<?php echo wp_json_encode( $translations ); ?>);
	Packlink.checkout.setIsCart(<?php echo is_cart() ? 'true' : 'false'; ?>);
	Packlink.checkout.setLocations(<?php echo wp_json_encode( $locations ); ?>);
	Packlink.checkout.setSelectedLocationId('<?php echo $id_value; ?>');
	Packlink.checkout.setSaveEndpoint('<?php echo Shop_Helper::get_controller_url( 'Checkout', 'save_selected' ); ?>');
	Packlink.checkout.setNoDropOffLocationsMessage('<?php echo $no_drop_off_locations_message; ?>');
	<?php if ( ! is_cart() ) : ?>
	Packlink.checkout.setDropOffAddress();
	<?php endif; ?>
</script>
