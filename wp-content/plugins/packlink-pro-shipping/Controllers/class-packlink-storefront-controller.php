<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\Interfaces\Proxy;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Storefront_Controller
 *
 * Renders customer-facing Packlink elements on storefront order pages.
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Storefront_Controller {

	/**
	 * Renders the shared (public) tracking page link directly under the shipping
	 * address on the customer's order page (My Account -> view order and the
	 * order-received page). The drop-off pickup point, when used, is stored as the
	 * order's shipping address, so this places the link beside that information.
	 *
	 * Hooked on `woocommerce_order_details_after_customer_address`; only renders for
	 * the shipping address.
	 *
	 * @param string   $address_type Address being rendered ('billing' or 'shipping').
	 * @param WC_Order $order        Order being viewed.
	 */
	public function render_tracking_link( $address_type, $order ) {
		if ( 'shipping' !== $address_type || ! $order instanceof WC_Order ) {
			return;
		}

		try {
			/** @var OrderShipmentDetailsService $shipment_details_service */ // phpcs:ignore
			$shipment_details_service = ServiceRegister::getService( OrderShipmentDetailsService::CLASS_NAME );
			$order_details            = $shipment_details_service->getDetailsByOrderId( (string) $order->get_id() );

			if ( ! $order_details || ! $order_details->getReference() ) {
				return;
			}

			/** @var Proxy $proxy */ // phpcs:ignore
			$proxy        = ServiceRegister::getService( Proxy::CLASS_NAME );
			$tracking_url = (string) $proxy->getPublicTrackingUrl(
				$order_details->getReference(),
				Shop_Helper::get_tracking_locale()
			);
		} catch ( Exception $e ) {
			return; // Fail silently; never break the customer order page.
		}

		if ( empty( $tracking_url ) ) {
			return;
		}

		// External-link ("open in new tab") arrow, inheriting the link colour via currentColor.
		$icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
			. ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"'
			. ' style="margin-left:4px;flex-shrink:0;">'
			. '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>'
			. '<polyline points="15 3 21 3 21 9"></polyline>'
			. '<line x1="10" y1="14" x2="21" y2="3"></line>'
			. '</svg>';

		echo '<p class="pl-tracking-link" style="margin-top:8px;">'
			. '<a href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener"'
			. ' style="display:inline-flex;align-items:center;color:#2095f2;font-weight:600;text-decoration:none;">'
			. esc_html__( 'View tracking page', 'packlink-pro-shipping' )
			. $icon
			. '</a></p>';
	}
}
