<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\ShippingMethod;

use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Singleton;
use Packlink\BusinessLogic\Controllers\AnalyticsController;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\WooCommerce\Components\Checkout\Checkout_Handler;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Class Shop_Shipping_Method_Service
 *
 * @package Packlink\WooCommerce\Components\ShippingMethod
 */
class Shop_Shipping_Method_Service extends Singleton implements ShopShippingMethodService {

	/**
	 * Singleton instance of this class.
	 *
	 * @var static
	 */
	protected static $instance;
	/**
	 * Repository instance.
	 *
	 * @var RepositoryInterface
	 */
	protected $repository;

    /**
     * @var array
     */
    private static $updatedMethods = array();

    /**
     * Shop_Shipping_Method_Service constructor.
     *
     * @throws RepositoryNotRegisteredException If bootstrap is not called.
     */
    public function __construct()
    {
        parent::__construct();

		$this->repository = RepositoryRegistry::getRepository( Shipping_Method_Map::CLASS_NAME );
	}

	/**
	 * Adds / Activates shipping method in shop integration.
	 *
	 * @param ShippingMethod $shipping_method Shipping method.
	 *
	 * @return bool TRUE if activation succeeded; otherwise, FALSE.
	 */
	public function add( ShippingMethod $shipping_method ) {
		try {
			$zone_ids = $this->get_selected_shipping_zones( $shipping_method );

			foreach ( $zone_ids as $zone_id ) {
				$this->add_method_to_zone( $shipping_method, $zone_id );
			}
		} catch ( Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration', $shipping_method->toArray() );

			return false;
		}

		return true;
	}

	/**
	 * Adds default Packlink shipping method.
	 *
	 * @param ShippingMethod $shipping_method Shipping method.
	 *
	 * @return bool TRUE if backup shipping method is added; otherwise, FALSE.
	 */
	public function addBackupShippingMethod( ShippingMethod $shipping_method ) {
		$default = new ShippingMethod();
		$default->setId( - 1 );
		$default->setTitle( Checkout_Handler::DEFAULT_SHIPPING );
		$default->setUsePacklinkPriceIfNotInRange( true );

		foreach ( $shipping_method->getPricingPolicies() as $policy ) {
			$default->addPricingPolicy( clone $policy );
		}

		$default->setShipToAllCountries( true );
		$default->setTaxClass( $shipping_method->getTaxClass() );

		$this->add( $default );
		$this->set_default_shipping_method( $default );

		return true;
	}

	/**
	 * Gets the carrier logo path based on carrier name.
	 *
	 * @param string $carrier_name Carrier name.
	 *
	 * @return string Carrier logo path.
	 */
	public function getCarrierLogoFilePath( $carrier_name ) {
		$file_name = strtolower( str_replace( ' ', '-', $carrier_name ) );

		$file_path  = dirname( dirname( __DIR__ ) ) . '/resources/packlink/images/carriers/' . $file_name . '.png';
		$image_path = Shop_Helper::get_plugin_base_url() . 'resources/packlink/images/carriers/' . $file_name . '.png';
		$default    = Shop_Helper::get_plugin_base_url() . 'resources/images/box.svg';

		return file_exists( $file_path ) ? $image_path : $default;
	}

	/**
	 * Disables shop shipping services/carriers.
	 *
	 * @return boolean TRUE if operation succeeded; otherwise, false.
	 */
	public function disableShopServices() {
		Shipping_Method_Helper::disable_shop_shipping_methods();
		AnalyticsController::sendOtherServicesDisabledEvent();

		return true;
	}

	/**
	 * Adds all active shipping methods to zone.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone.
	 */
	public function add_active_methods_to_zone( WC_Shipping_Zone $zone ) {
		/**
		 * Shipping method service.
		 *
		 * @var ShippingMethodService $service
		 */
		$service = ServiceRegister::getService( ShippingMethodService::CLASS_NAME );
		/**
		 * Configuration service.
		 *
		 * @var Config_Service $configuration
		 */
		$configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );

		$default_method   = $configuration->get_default_shipping_method();
		$shipping_methods = $service->getActiveMethods();
		if ( $default_method ) {
			$shipping_methods[] = $default_method;
		}

		foreach ( $shipping_methods as $shipping_method ) {
			if ( ! $shipping_method->isShipToAllCountries() ) {
				continue;
			}

			$this->add_method_to_zone( $shipping_method, $zone->get_id() );
		}
	}

	/**
	 * Updates shipping method in shop integration.
	 *
	 * @param ShippingMethod $shipping_method Shipping method.
	 *
	 * @throws QueryFilterInvalidParamException
	 */
	public function update( ShippingMethod $shipping_method ) {
	    if (in_array($shipping_method->getId(), self::$updatedMethods)) {
            return;
        }

        self::$updatedMethods[] = $shipping_method->getId();
		$zone_ids       = $this->get_selected_shipping_zones( $shipping_method );
		$existing_zones = array();
		$items          = $this->get_woocommerce_shipping_methods( $shipping_method->getId() );
		$instance_ids   = array();
		$all_zones      = Shipping_Method_Helper::get_all_shipping_zone_ids();
		foreach ( $items as $item ) {
			if ( $item->getWoocommerceShippingMethodId() !== null ) {
				$instance_ids[] = $item->getWoocommerceShippingMethodId();
			}
		}

		if ( ! empty( $instance_ids ) ) {
			$filter = new QueryFilter();
			$filter->where( 'woocommerceShippingMethodId', Operators::IN, $instance_ids );
			/** @var Shipping_Method_Map[] $map_items */
			$map_items = $this->repository->select( $filter );
			foreach ( $map_items as $map_item ) {
				$zone_id = $map_item->getZoneId();
				if ( ! in_array( $zone_id, $all_zones ) ) {
					$this->repository->delete( $map_item );
					continue;
				}

				$instance_id                 = $map_item->getWoocommerceShippingMethodId();
				$woocommerce_shipping_method = new Packlink_Shipping_Method( $instance_id );
				$zone                        = new WC_Shipping_Zone( $zone_id );
				$zone_methods                = array_keys( $zone->get_shipping_methods() );
				/** @noinspection TypeUnsafeArraySearchInspection */
				if ( ! in_array( $zone_id, $zone_ids ) || ! in_array( $instance_id, $zone_methods ) ) {
					$this->delete_woocommerce_shipping_method( $woocommerce_shipping_method );
					$this->repository->delete( $map_item );

					if ( ! in_array( $instance_id, $zone_methods, true ) ) {
						$this->add_method_to_zone( $shipping_method, $zone_id );
					}
				} else {
					$this->update_woocommerce_shipping_method( $shipping_method, $woocommerce_shipping_method );
				}
				if ( ! in_array( $zone_id, $existing_zones, true ) ) {
					$existing_zones[] = $zone_id;
				}
			}
		}

		$new_zones = array_diff( $zone_ids, $existing_zones );
		foreach ( $new_zones as $new_zone ) {
			$this->add_method_to_zone( $shipping_method, $new_zone );
		}
	}

	/**
	 * Deletes shipping method in shop integration.
	 *
	 * @param ShippingMethod $shipping_method Shipping method.
	 *
	 * @return bool TRUE if deletion succeeded; otherwise, FALSE.
	 */
	public function delete( ShippingMethod $shipping_method ) {
		try {
			$items = $this->get_woocommerce_shipping_methods( $shipping_method->getId() );
			foreach ( $items as $item ) {
				$instance_id                 = $item->getWoocommerceShippingMethodId();
				$woocommerce_shipping_method = new Packlink_Shipping_Method( $instance_id );
				$this->delete_woocommerce_shipping_method( $woocommerce_shipping_method );

				$this->repository->delete( $item );
			}
		} catch ( Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration', $shipping_method->toArray() );

			return false;
		}

		return true;
	}

	/**
	 * Removes default Packlink shipping method.
	 *
	 * @return bool TRUE if backup shipping method is deleted; otherwise, FALSE.
	 */
	public function deleteBackupShippingMethod() {
		$default = new ShippingMethod();
		$default->setId( - 1 );

		$this->delete( $default );
		$this->set_default_shipping_method();

		return true;
	}

	/**
	 * Adds shipping method to zone.
	 *
	 * @param ShippingMethod $shipping_method Shipping method to be added.
	 * @param int            $zone_id Zone id.
	 */
	protected function add_method_to_zone( ShippingMethod $shipping_method, $zone_id ) {
		$zone = WC_Shipping_Zones::get_zone( $zone_id );
		if ( ! $zone ) {
			return;
		}

		$instance_id    = $zone->add_shipping_method( 'packlink_shipping_method' );
		$pricing_policy = $this->get_shipping_method_pricing_policy( $shipping_method );

		if ( 0 !== $instance_id ) {
			$woocommerce_shipping_method = new Packlink_Shipping_Method( $instance_id );
			$woocommerce_shipping_method->set_post_data(
				array(
					'woocommerce_packlink_shipping_method_title'        => $shipping_method->getTitle(),
					'woocommerce_packlink_shipping_method_price_policy' => $pricing_policy,
				)
			);

			$_REQUEST['instance_id'] = $instance_id;
			$woocommerce_shipping_method->process_admin_options();
			$this->add_to_shipping_method_map( $instance_id, $shipping_method->getId(), $zone_id );
		}
	}

	/**
	 * Returns shipping method pricing policy.
	 *
	 * @param ShippingMethod $shipping_method Shipping method object.
	 *
	 * @return string Pricing policy.
	 */
	private function get_shipping_method_pricing_policy( ShippingMethod $shipping_method ) {
		$result = '';

		$pricing_policies = $shipping_method->getPricingPolicies();

		foreach ( $pricing_policies as $price_policy ) {
			switch ( $price_policy->pricingPolicy ) { // phpcs:ignore
				case ShippingPricePolicy::POLICY_PACKLINK:
					$result .= __( 'Packlink prices', 'packlink_pro_shipping' ) . ' | ';
					break;
				case ShippingPricePolicy::POLICY_PACKLINK_ADJUST:
					$result .= __( '% of Packlink prices', 'packlink_pro_shipping' ) . ' | ';
					break;
				case ShippingPricePolicy::POLICY_FIXED_PRICE:
					$result .= __( 'Fixed price', 'packlink_pro_shipping' ) . ' | ';
					break;
			}
		}

		return rtrim( $result, ' | ' );
	}

	/**
	 * Adds pair of WooCommerce and Packlink shipping methods to map.
	 *
	 * @param int $woocommerce_method_id WooCommerce shipping method identifier.
	 * @param int $packlink_method_id Packlink shipping method identifier.
	 * @param int $zone_id WooCommerce shipping zone identifier.
	 */
	private function add_to_shipping_method_map( $woocommerce_method_id, $packlink_method_id, $zone_id ) {
		$map_item = new Shipping_Method_Map();
		$map_item->setWoocommerceShippingMethodId( $woocommerce_method_id );
		$map_item->setPacklinkShippingMethodId( $packlink_method_id );
		$map_item->setZoneId( $zone_id );

		$this->repository->save( $map_item );
	}

	/**
	 * Returns a list of map items for provided Packlink shipping method identifier.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param int $packlink_method_id Packlink shipping method identifier.
	 *
	 * @return Shipping_Method_Map[]
	 */
	private function get_woocommerce_shipping_methods( $packlink_method_id ) {
		$filter = new QueryFilter();
		/** @noinspection PhpUnhandledExceptionInspection */ // phpcs:ignore
		$filter->where( 'packlinkShippingMethodId', '=', $packlink_method_id );

		/**
		 * Shipping method map entries.
		 *
		 * @var Shipping_Method_Map[] $entities
		 */
		$entities = $this->repository->select( $filter );

		return $entities;
	}

	/**
	 * Stores default shipping method into configuration.
	 *
	 * @param ShippingMethod|null $shipping_method Shipping method.
	 */
	private function set_default_shipping_method( ShippingMethod $shipping_method = null ) {
		/**
		 * Configuration service.
		 *
		 * @var Config_Service $configuration
		 */
		$configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );
		$configuration->set_default_shipping_method( $shipping_method );
	}

	/**
	 * Returns selected shipping zones for the provided shipping method.
	 *
	 * @param ShippingMethod $shipping_method
	 *
	 * @return array|int[]
	 */
	private function get_selected_shipping_zones( ShippingMethod $shipping_method ) {
		if ( $shipping_method->isShipToAllCountries() ) {
			return Shipping_Method_Helper::get_all_shipping_zone_ids();
		}

		return $shipping_method->getShippingCountries();
	}

	/**
	 * Updates WooCommerce shipping method.
	 *
	 * @param ShippingMethod           $method
	 * @param Packlink_Shipping_Method $woocommerce_shipping_method
	 */
	private function update_woocommerce_shipping_method( ShippingMethod $method, Packlink_Shipping_Method $woocommerce_shipping_method ) {
		$woocommerce_shipping_method->instance_settings['title']        = $method->getTitle();
		$woocommerce_shipping_method->instance_settings['price_policy'] = $this->get_shipping_method_pricing_policy( $method );

		update_option(
			$woocommerce_shipping_method->get_instance_option_key(),
			apply_filters(
				'woocommerce_packlink_shipping_method_' . $woocommerce_shipping_method->instance_id . '_settings',
				$woocommerce_shipping_method->instance_settings,
				$woocommerce_shipping_method
			)
		);
	}

	/**
	 * Deletes WooCommerce shipping method.
	 *
	 * @param Packlink_Shipping_Method $woocommerce_shipping_method
	 */
	private function delete_woocommerce_shipping_method( Packlink_Shipping_Method $woocommerce_shipping_method ) {
		global $wpdb;

		$option_key = $woocommerce_shipping_method->get_instance_option_key();
		$table      = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
		if ( $wpdb->delete( $table, array( 'instance_id' => $woocommerce_shipping_method->instance_id ) ) ) { // phpcs:ignore
			delete_option( $option_key );
		}
	}
}
