<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Order;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\ShipmentDraft\Interfaces\ShipmentDraftServiceInterface;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use WC_Order;

/**
 * Class Paid_Order_Handler
 *
 * @package Packlink\WooCommerce\Components\Utility
 */
class Paid_Order_Handler {
	/**
	 * Fully qualified name of this interface.
	 */
	const CLASS_NAME = __CLASS__;

	/**
	 * Creates Packlink shipment draft if the order is paid.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param int      $order_id Order identifier.
	 * @param WC_Order $order WooCommerce order instance.
	 */
	public static function handle( $order_id, WC_Order $order ) {

		if ( ! self::get_config_service()->is_manual_sync_enabled()
		     && $order->is_paid() && static::is_packlink_order( $order ) && static::has_shippable_product( $order ) ) {
			/** @var ShipmentDraftServiceInterface $draft_service */
			$draft_service = ServiceRegister::getService( ShipmentDraftServiceInterface::CLASS_NAME );
			$draft_service->enqueueCreateShipmentDraftTask( (string) $order_id );
		}

	}

	/**
	 * Checks if order is Packlink order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool Returns TRUE if the order is created with Packlink shipping method.
	 *
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	protected static function is_packlink_order( WC_Order $order ) {
		$method = Shipping_Method_Helper::get_packlink_shipping_method_from_order( $order );

		return $method !== null;
	}

	/**
	 * Checks if order has shippable product(s).
	 *
	 * @param WC_Order $order Order instance.
	 *
	 * @return bool Returns true if order has shippable product(s).
	 */
	protected static function has_shippable_product( WC_Order $order ) {
		/** @var \WC_Order_Item_Product $item */
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product->is_virtual() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves config service.
	 *
	 * @return Config_Service Configuration service.
	 */
	protected static function get_config_service() {
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Config_Service::CLASS_NAME );

		return $config_service;
	}

}
