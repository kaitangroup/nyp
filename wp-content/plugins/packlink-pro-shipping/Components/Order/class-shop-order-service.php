<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Order;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Singleton;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as BaseShopOrderService;
use Packlink\BusinessLogic\Order\Objects\Address;
use Packlink\BusinessLogic\Order\Objects\Item;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Services\Customs_Mapping_Service;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use WC_Order;
use WC_Product;
use WP_Term;

/**
 * Class Shop_Order_Service
 *
 * @package Packlink\WooCommerce\Components\Repositories
 */
class Shop_Order_Service extends Singleton implements BaseShopOrderService {
	/**
	 * Singleton instance of this class.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Configuration service.
	 *
	 * @var Config_Service
	 */
	protected $configuration;

	/**
	 * Cached customs mapping. `false` means "not loaded yet"; `null` means "none saved".
	 *
	 * @var \Packlink\BusinessLogic\Customs\Models\CustomsMapping|null|false
	 */
	private $customs_mapping = false;

	/**
	 * Order_Repository constructor.
	 */
	protected function __construct() {
		parent::__construct();

		$this->configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );
	}

	/**
	 * Fetches and returns system order by its unique identifier.
	 *
	 * @param string $order_id $orderId Unique order id.
	 *
	 * @return Order Order object.
	 *
	 * @throws OrderNotFound When order with provided id is not found.
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public function getOrderAndShippingData( $order_id ) {
		$wc_order = $this->get_order_by_id( $order_id );

		$order = new Order();
		$order->setId( $order_id );
		$order->setOrderNumber( $wc_order->get_order_number() );
		$order->setStatus( $wc_order->get_status() );
		$order->setBasePrice( $wc_order->get_subtotal() );
		$order->setCartPrice( $wc_order->get_total() - $wc_order->get_shipping_total() );
		$order->setCurrency( $wc_order->get_currency() );
		$order->setCustomerId( $wc_order->get_customer_id() );
		$order->setNetCartPrice( $order->getCartPrice() - $wc_order->get_cart_tax() );
		$order->setOrderNumber( $wc_order->get_order_number() );
		$order->setTotalPrice( $wc_order->get_total() );
		$order->setShippingPrice( $wc_order->get_shipping_total() );

		$items = $this->get_order_items( $wc_order );
		$order->setItems( $items );
		$order->setTotalWeight( $this->calculate_total_weight( $items ) );

		$order->setBillingAddress( $this->get_billing_address( $wc_order ) );
		$order->setShippingAddress( $this->get_shipping_address( $wc_order ) );

		// One resolved value feeds both customs attributes: the core sends it as the receiver
		// tax id (private person) or VAT number (company) based on the receiver user type.
		$tax_id = $this->resolve_order_tax_id( $wc_order );
		if ( '' !== $tax_id ) {
			$order->setTaxId( $tax_id );
			$order->setVatNumber( $tax_id );
		}

        $order->setPaymentId($wc_order->get_payment_method());

		$shipping_method = Shipping_Method_Helper::get_packlink_shipping_method_from_order( $wc_order );
		if ( null !== $shipping_method ) {
			$order->setShippingMethodId( $shipping_method->getId() );
		}

		$drop_off_point_id = $this->get_drop_off_point_id( (int) $order_id );
		if ( $drop_off_point_id ) {
			$order->setShippingDropOffId( $drop_off_point_id );
		}

		return $order;
	}

	/**
	 * @inheritDoc
	 */
	public function updateTrackingInfo( $order_id, Shipment $shipment, array $tracking_history ) {
	}

	/**
	 * @inheritDoc
	 */
	public function updateShipmentStatus( $order_id, $shipping_status ) {
		$order      = $this->get_order_by_id( $order_id );
		$status_map = $this->configuration->getOrderStatusMappings();
		$old_status = $order->get_status();
		if ( $old_status === 'cancelled' ) {
			// We don't want to update order status of cancelled order.
			return;
		}

		if ( ! empty( $status_map[ $shipping_status ] ) && $status_map[ $shipping_status ] !== $old_status ) {
			$order->set_status( $status_map[ $shipping_status ], __( 'Status set by Packlink PRO.', 'packlink-pro-shipping' ) );
		}

		$order->save();
	}

	/**
	 * Returns order instance, if exists.
	 *
	 * @param string $order_id $orderId Unique order id.
	 *
	 * @return WC_Order WooCommerce order object.
	 * @throws OrderNotFound When order with provided id is not found.
	 */
	public function get_order_by_id( $order_id ) {
		$wc_order = \WC_Order_Factory::get_order( $order_id );
		if ( false === $wc_order ) {
			/* translators: %s: order identifier */
			throw new OrderNotFound( sprintf( __( 'Order with id(%s) not found!', 'packlink-pro-shipping' ), $order_id ) );
		}

		return $wc_order;
	}

	/**
	 * Returns category name.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 *
	 * @return string|null Category name.
	 */
	private function get_product_category_name( \WC_Product $product ) {
		$category_ids = $product->get_category_ids();
		if ( empty( $category_ids ) ) {
			return null;
		}

		$category = WP_Term::get_instance( $category_ids[0] );

		return $category instanceof WP_Term ? $category->name : null;
	}

	/**
	 * Returns array of formatted order items.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 *
	 * @return Item[] Array of formatted order items.
	 */
	private function get_order_items( WC_Order $wc_order ) {
		$items = array();
		/**
		 * WooCommerce order item.
		 *
		 * @var \WC_Order_Item_Product $wc_item
		 */
		foreach ( $wc_order->get_items() as $wc_item ) {
			$product = $wc_item->get_product();
			if ( $product->is_virtual() ) {
				continue;
			}

			$item = new Item();
			$item->setQuantity( $wc_item->get_quantity() );
			$item->setId( $wc_item->get_product_id() );
			$item->setTotalPrice( (float) $wc_item->get_total() );
			$item->setSku( $product->get_sku() );
			$item->setHeight( (float) $product->get_height() );
			$item->setLength( (float) $product->get_length() );
			$item->setWidth( (float) $product->get_width() );
			$item->setWeight( (float) $product->get_weight() );
			$item->setTitle( $product->get_title() );
			$item->setCategoryName( $this->get_product_category_name( $product ) );
			$item->setPrice( $wc_item->get_subtotal() );
			$item->setConcept( $product->get_description() );

			$tariff_number = $this->resolve_item_tariff_number( $product );
			if ( '' !== $tariff_number ) {
				$item->setTariffNumber( $tariff_number );
			}

			$country_of_origin = $this->resolve_item_country_of_origin( $product );
			if ( '' !== $country_of_origin ) {
				$item->setCountryOfOrigin( $country_of_origin );
			}

			$picture = wp_get_attachment_image_src( $product->get_image_id(), 'single' );
			if ( $picture ) {
				$item->setPictureUrl( $picture[0] );
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Sums the weight of all order items (unit weight multiplied by quantity).
	 *
	 * The core Order model keeps its own total-weight field, separate from the per-item weights, and
	 * it defaults to 0. The customs invoice request (CustomsService::getShipmentDetails) reads this
	 * order-level value for `parcels_weight`, which Packlink rejects when it is 0. So it must be set
	 * explicitly here even though the draft packages are built from the individual item weights.
	 *
	 * @param Item[] $items Formatted order items.
	 *
	 * @return float Total order weight in the store weight unit.
	 */
	private function calculate_total_weight( array $items ) {
		$total_weight = 0.0;

		foreach ( $items as $item ) {
			$quantity      = $item->getQuantity() ? $item->getQuantity() : 1;
			$total_weight += (float) $item->getWeight() * $quantity;
		}

		return $total_weight;
	}

	/**
	 * Returns the saved customs mapping, loading it once per request.
	 *
	 * @return \Packlink\BusinessLogic\Customs\Models\CustomsMapping|null
	 */
	private function get_customs_mapping() {
		if ( false === $this->customs_mapping ) {
			$this->customs_mapping = $this->configuration->getCustomsMappings();
		}

		return $this->customs_mapping;
	}

	/**
	 * Resolves an item tariff (HS) code: mapped product field, then the dedicated HS-code meta, then
	 * empty (core applies the configured default).
	 *
	 * @param WC_Product $product WooCommerce product.
	 *
	 * @return string
	 */
	private function resolve_item_tariff_number( WC_Product $product ) {
		$mapping = $this->get_customs_mapping();

		if ( $mapping && ! empty( $mapping->mappingTariffNumber ) ) {
			$value = $this->read_product_field( $product, $mapping->mappingTariffNumber );
			if ( '' !== $value ) {
				return $value;
			}
		}

		$fallback = $product->get_meta( Customs_Mapping_Service::PRODUCT_HS_CODE_META );

		return $fallback ? (string) $fallback : '';
	}

	/**
	 * Reads a mapped product field by its namespace: an `attr:{name}` value is a product attribute
	 * (global `pa_*` taxonomy or per-product custom attribute name, read via get_attribute), a
	 * `meta:{key}` value is a product meta key. Legacy un-namespaced values fall back to the old
	 * behavior: `pa_*` is an attribute, anything else is a product meta key.
	 *
	 * @param WC_Product $product WooCommerce product.
	 * @param string     $field   Mapped field (namespaced attribute or meta key).
	 *
	 * @return string
	 */
	private function read_product_field( WC_Product $product, $field ) {
		if ( 0 === strpos( $field, Customs_Mapping_Service::PREFIX_ATTRIBUTE ) ) {
			$name = substr( $field, strlen( Customs_Mapping_Service::PREFIX_ATTRIBUTE ) );

			return (string) $product->get_attribute( $name );
		}

		if ( 0 === strpos( $field, Customs_Mapping_Service::PREFIX_PRODUCT_META ) ) {
			$key   = substr( $field, strlen( Customs_Mapping_Service::PREFIX_PRODUCT_META ) );
			$value = $product->get_meta( $key );

			return ( '' !== $value && null !== $value ) ? (string) $value : '';
		}

		if ( 0 === strpos( $field, 'pa_' ) ) {
			return (string) $product->get_attribute( $field );
		}

		$value = $product->get_meta( $field );

		return ( '' !== $value && null !== $value ) ? (string) $value : '';
	}

	/**
	 * Reads a mapped customer field by its namespace: an `order:{key}` value is an order meta key,
	 * a `user:{key}` value is a customer user meta key (empty for guest orders). Legacy
	 * un-namespaced values fall back to the old behavior: an order meta key.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 * @param string   $field    Mapped field (namespaced order or user meta key).
	 *
	 * @return string
	 */
	private function read_customer_field( WC_Order $wc_order, $field ) {
		if ( 0 === strpos( $field, Customs_Mapping_Service::PREFIX_ORDER_META ) ) {
			$key   = substr( $field, strlen( Customs_Mapping_Service::PREFIX_ORDER_META ) );
			$value = $wc_order->get_meta( $key );

			return ( '' !== $value && null !== $value ) ? (string) $value : '';
		}

		if ( 0 === strpos( $field, Customs_Mapping_Service::PREFIX_USER_META ) ) {
			$key = substr( $field, strlen( Customs_Mapping_Service::PREFIX_USER_META ) );

			return $this->read_customer_user_meta( $wc_order, $key );
		}

		$value = $wc_order->get_meta( $field );

		return ( '' !== $value && null !== $value ) ? (string) $value : '';
	}

	/**
	 * Reads a user meta value of the order customer. Guest orders (customer id 0) have no profile,
	 * so they resolve to an empty string.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 * @param string   $key      User meta key.
	 *
	 * @return string
	 */
	private function read_customer_user_meta( WC_Order $wc_order, $key ) {
		$customer_id = (int) $wc_order->get_customer_id();
		if ( 0 === $customer_id ) {
			return '';
		}

		$value = get_user_meta( $customer_id, $key, true );

		return ( '' !== $value && null !== $value ) ? (string) $value : '';
	}

	/**
	 * Resolves an item country of origin: mapped product field, then the dedicated
	 * country-of-origin meta, then empty (core applies the configured default).
	 *
	 * @param WC_Product $product WooCommerce product.
	 *
	 * @return string
	 */
	private function resolve_item_country_of_origin( WC_Product $product ) {
		$mapping = $this->get_customs_mapping();

		if ( $mapping && ! empty( $mapping->mappingCountryOfOrigin ) ) {
			$value = $this->read_product_field( $product, $mapping->mappingCountryOfOrigin );
			if ( '' !== $value ) {
				return $value;
			}
		}

		$fallback = $product->get_meta( Customs_Mapping_Service::PRODUCT_COUNTRY_OF_ORIGIN_META );

		return $fallback ? (string) $fallback : '';
	}

	/**
	 * Resolves the customer tax ID / VAT number: mapped customer field, then the dedicated user
	 * meta of the order customer, then empty. One value serves both customs attributes - the core
	 * routes it to tax id or VAT number based on the configured receiver user type.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 *
	 * @return string
	 */
	private function resolve_order_tax_id( WC_Order $wc_order ) {
		$mapping = $this->get_customs_mapping();

		if ( $mapping && ! empty( $mapping->mappingReceiverTaxId ) ) {
			$value = $this->read_customer_field( $wc_order, $mapping->mappingReceiverTaxId );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return $this->read_customer_user_meta( $wc_order, Customs_Mapping_Service::USER_TAX_ID_META );
	}

	/**
	 * Returns billing address.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 *
	 * @return Address Billing address.
	 */
	private function get_billing_address( WC_Order $wc_order ) {
		$address = new Address();
		if ( $wc_order->get_billing_address_1() || $wc_order->get_billing_address_2() ) {
			$address->setEmail( $wc_order->get_billing_email() );
			$address->setPhone( $wc_order->get_billing_phone() );
			$address->setName( $wc_order->get_billing_first_name() );
			$address->setSurname( $wc_order->get_billing_last_name() );
			$address->setCompany( $wc_order->get_billing_company() );
			$address->setCity( $wc_order->get_billing_city() );
			$address->setStreet1( $wc_order->get_billing_address_1() );
			$address->setStreet2( $wc_order->get_billing_address_2() );
			$address->setCountry( $wc_order->get_billing_country() );
			$address->setZipCode( $wc_order->get_billing_postcode() );
		}

		return $address;
	}

	/**
	 * Returns shipping address.
	 *
	 * @param WC_Order $wc_order WooCommerce order.
	 *
	 * @return Address Shipping address.
	 */
	private function get_shipping_address( WC_Order $wc_order ) {
		$address = new Address();
		if ( $wc_order->get_shipping_address_1() || $wc_order->get_shipping_address_2() ) {
			$address->setEmail( $wc_order->get_billing_email() );
			$address->setPhone( $wc_order->get_billing_phone() );
			$address->setName( $wc_order->get_shipping_first_name() );
			$address->setSurname( $wc_order->get_shipping_last_name() );
			$address->setCompany( $wc_order->get_shipping_company() );
			$address->setCity( $wc_order->get_shipping_city() );
			$address->setStreet1( $wc_order->get_shipping_address_1() );
			$address->setStreet2( $wc_order->get_shipping_address_2() );
			$address->setCountry( $wc_order->get_shipping_country() );
			$address->setZipCode( $wc_order->get_shipping_postcode() );
		} else {
			$address = $this->get_billing_address( $wc_order );
		}

		return $address;
	}

	/**
	 * Returns order drop-off point ID, if exists.
	 *
	 * @param int $order_id
	 *
	 * @return int|null
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_drop_off_point_id( $order_id ) {
		$order_drop_off_map_repository = RepositoryRegistry::getRepository( Order_Drop_Off_Map::CLASS_NAME );

		$filter = new QueryFilter();
		$filter->where( 'order_id', Operators::EQUALS, $order_id );

		/** @var Order_Drop_Off_Map $order_drop_off_map */
		$order_drop_off_map = $order_drop_off_map_repository->selectOne( $filter );

		return $order_drop_off_map ? $order_drop_off_map->get_drop_off_point_id() : null;
	}
}
