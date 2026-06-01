<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\ShippingMethod;

use Logeecom\Infrastructure\ORM\Entity;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\WooCommerce\Components\Order\Order_Drop_Off_Map;
use Packlink\WooCommerce\Components\Services\Config_Service;
use WC_Order;
use WC_Shipping_Method;
use WC_Shipping_Zones;

/**
 * Class Shipping_Method_Helper
 *
 * @package Packlink\WooCommerce\Components\ShippingMethod
 */
class Shipping_Method_Helper {

	const SHIPPING_ID    = '_packlink_shipping_method_id';
	const DROP_OFF_ID    = '_packlink_drop_off_point_id';
	const DROP_OFF_EXTRA = '_packlink_drop_off_extra';
	const BLOCK_CHECKOUT = '_packlink_is_block_checkout';

	/**
	 * Retrieves map for a given shipping method id.
	 *
	 * @param int $method_id Packlink shipping method id.
	 *
	 * @return Entity[]
	 * @throws QueryFilterInvalidParamException When query filter invalid.
	 * @throws RepositoryNotRegisteredException When repository not registered.
	 */
	public static function get_shipping_method_map_for_packlink_shipping_method( $method_id ) {
		/** @noinspection PhpUnhandledExceptionInspection */
		$repository = RepositoryRegistry::getRepository( Shipping_Method_Map::CLASS_NAME );
		$query      = new QueryFilter();
		$query->where( 'packlinkShippingMethodId', '=', $method_id );

		return $repository->select( $query );
	}

	/**
	 * Returns Packlink shipping method.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 *
	 * @return ShippingMethod Returns Packlink shipping method.
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public static function get_packlink_shipping_method_from_order( WC_Order $wc_order ) {
		$shipping = $wc_order->get_shipping_methods();
		if ( empty( $shipping ) ) {
			return null;
		}

		$shipping_item      = reset( $shipping );
		$shipping_data      = $shipping_item->get_data();
		$shipping_method_id = $shipping_data['instance_id'];

		return self::get_packlink_shipping_method( (int) $shipping_method_id );
	}

	/**
	 * Returns Packlink shipping method that is assigned to this WooCommerce shipping method.
	 *
	 * @param int $wc_shipping_method_id Shipping method identifier.
	 *
	 * @return ShippingMethod Shipping method.
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws QueryFilterInvalidParamException
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public static function get_packlink_shipping_method( $wc_shipping_method_id ) {
		$filter = new QueryFilter();
		$filter->where( 'woocommerceShippingMethodId', '=', $wc_shipping_method_id );

		$repository = RepositoryRegistry::getRepository( Shipping_Method_Map::CLASS_NAME );
		/**
		 * Shipping method map entity.
		 *
		 * @var Shipping_Method_Map $map_entry
		 */
		$map_entry = $repository->selectOne( $filter );
		if ( null === $map_entry ) {
			return null;
		}

		$id = $map_entry->getPacklinkShippingMethodId();
		if ( - 1 === $id ) {
			/**
			 * Configuration service.
			 *
			 * @var Config_Service $configuration
			 */
			$configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );

			return $configuration->get_default_shipping_method();
		}

		/** @var ShippingMethodService $shipping_method_service */
		$shipping_method_service = ServiceRegister::getService( ShippingMethodService::CLASS_NAME );

		return $shipping_method_service->getShippingMethod( $map_entry->getPacklinkShippingMethodId() );
	}

	/**
	 * Disable Packlink added shipping methods.
	 */
	public static function disable_packlink_shipping_methods() {
		static::change_shipping_methods_status( 0 );
	}

	/**
	 * Enable Packlink added shipping methods.
	 */
	public static function enable_packlink_shipping_methods() {
		static::change_shipping_methods_status();
	}

	/**
	 * Returns count of active shop shipping methods.
	 *
	 * @return int Count of shop active shipping methods.
	 */
	public static function get_shop_shipping_method_count() {
		$count = 0;

		foreach ( self::get_all_shipping_zone_ids() as $zone_id ) {
			$zone = WC_Shipping_Zones::get_zone( $zone_id );
			if ( ! $zone ) {
				continue;
			}

			foreach ( $zone->get_shipping_methods( true ) as $item ) {
				if ( Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD !== $item->id ) {
					$count ++;
				}
			}
		}

		return $count;
	}

	/**
	 * Disables all active shop shipping methods.
	 */
	public static function disable_shop_shipping_methods() {
		global $wpdb;

		foreach ( self::get_all_shipping_zone_ids() as $zone_id ) {
			$zone = WC_Shipping_Zones::get_zone( $zone_id );
			if ( ! $zone ) {
				continue;
			}

			/**
			 * WooCommerce shipping method.
			 *
			 * @var WC_Shipping_Method $item
			 */
			foreach ( $zone->get_shipping_methods( true ) as $item ) {
				if ( ( Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD !== $item->id )
					 && $wpdb->update( "{$wpdb->prefix}woocommerce_shipping_zone_methods", array( 'is_enabled' => 0 ), array( 'instance_id' => absint( $item->instance_id ) ) )
				) {
					do_action( 'woocommerce_shipping_zone_method_status_toggled', $item->instance_id, $item->id, $zone_id, 0 );
				}
			}
		}
	}

	/**
	 * Fully remove Packlink added shipping methods.
	 */
	public static function remove_packlink_shipping_methods() {
		global $wpdb;

		foreach ( static::get_shipping_method_map() as $item ) {
			$instance_id = $item->getWoocommerceShippingMethodId();
			$method      = new Packlink_Shipping_Method( $instance_id );
			$option_key  = $method->get_instance_option_key();
			if ( $wpdb->delete( "{$wpdb->prefix}woocommerce_shipping_zone_methods", array( 'instance_id' => $instance_id ) ) ) {
				delete_option( $option_key );
			}
		}
	}

	/**
	 * Return array of all zone ids.
	 *
	 * @return int[] Zone ids.
	 */
	public static function get_all_shipping_zone_ids() {
		$all_zones = WC_Shipping_Zones::get_zones();
		$zone_ids  = array_column( $all_zones, 'zone_id' );
		// Locations not covered by other zones.
		if ( ! in_array( 0, $zone_ids, true ) ) {
			$zone_ids[] = 0;
		}

		return $zone_ids;
	}

	/**
	 * Get drop-off map entity from database.
	 *
	 * @param int $order_id
	 *
	 * @return Entity|null
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public static function get_drop_off_map_for_order( $order_id ) {
		$order_drop_off_map_repository = RepositoryRegistry::getRepository( Order_Drop_Off_Map::CLASS_NAME );

		$filter = new QueryFilter();
		$filter->where( 'order_id', Operators::EQUALS, $order_id );

		return $order_drop_off_map_repository->selectOne( $filter );
	}

	/**
	 * Loads all packlink added shipping methods and changes their status to enabled or disabled.
	 *
	 * @param int $status Shipping status.
	 */
	private static function change_shipping_methods_status( $status = 1 ) {
		global $wpdb;

		foreach ( static::get_shipping_method_map() as $item ) {
			$instance_id = $item->getWoocommerceShippingMethodId();
			$method      = new Packlink_Shipping_Method( $instance_id );

			if ( $wpdb->update( "{$wpdb->prefix}woocommerce_shipping_zone_methods", array( 'is_enabled' => $status ), array( 'instance_id' => absint( $instance_id ) ) ) ) {
				do_action( 'woocommerce_shipping_zone_method_status_toggled', $instance_id, $method->id, $item->getZoneId(), $status );
			}
		}
	}

	/**
	 * Returns map of Packlink shipping services and WooCommerce shipping methods.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @return Shipping_Method_Map[] Array of shipping method map entries.
	 */
	private static function get_shipping_method_map() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$repository = RepositoryRegistry::getRepository( Shipping_Method_Map::CLASS_NAME );
		/**
		 * Shipping method map entries.
		 *
		 * @var Shipping_Method_Map[] $entities
		 */
		$entities = $repository->select();

		return $entities;
	}
}
