<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Checkout;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Order\Order_Drop_Off_Map;
use Packlink\WooCommerce\Components\Order\Paid_Order_Handler;
use Packlink\WooCommerce\Components\Services\Offline_Payments_Service;
use Packlink\WooCommerce\Components\ShippingMethod\Packlink_Shipping_Method;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;
use WC_Shipping_Rate;

/**
 * Class Checkout_Handler
 *
 * @package Packlink\WooCommerce\Components\Checkout
 */
class Checkout_Handler {

	/**
	 * Drop-off id hidden input name
	 */
	const PACKLINK_DROP_OFF_ID = 'packlink_drop_off_id';
	/**
	 * Drop-off address hidden input name
	 */
	const PACKLINK_DROP_OFF_EXTRA = 'packlink_drop_off_extra';
	/**
	 * Default Packlink shipping title
	 */
	const DEFAULT_SHIPPING = 'shipping cost';

    /**
     * @var Offline_Payments_Service
     */
    private $offline_payments_service;

    public function __construct()
    {
        $this->offline_payments_service = ServiceRegister::getService(
            OfflinePaymentsServices::CLASS_NAME);
    }

	/**
	 * This hook is triggered after shipping method label, and it will insert hidden input values.
	 *
	 * @param WC_Shipping_Rate $rate Shipping rate.
	 * @param int              $index Shipping method index.
	 *
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function after_shipping_rate( WC_Shipping_Rate $rate, $index ) {
		$rate_data       = $this->get_rate_data( $rate );
		$shipping_method = Shipping_Method_Helper::get_packlink_shipping_method( $rate_data['instance_id'] );

		if ( null === $shipping_method ) {
			return;
		}

        $cart = WC()->cart;
        $totals = $cart->get_totals();

        $subtotal   = isset($totals['cart_contents_total']) ? (float) $totals['cart_contents_total'] : 0;
        $shipping   = isset($totals['shipping_total']) ? (float) $totals['shipping_total'] : 0;
        $discount   = isset($totals['discount_total']) ? (float) $totals['discount_total'] : 0;

        $current_total = $subtotal + $shipping - $discount;

        $offlinePaymentName = $this->getOfflinePaymentName();


        $fields = array(
			'packlink_image_url'   => $shipping_method->getLogoUrl() ?: Shop_Helper::get_plugin_base_url() . 'resources/images/box.svg',
			'packlink_show_image'  => $shipping_method->isDisplayLogo() ? 'yes' : 'no',
			'packlink_is_drop_off' => $shipping_method->isDestinationDropOff() ? 'yes' : 'no',
            'packlink_cash_on_delivery' => $this->is_cash_on_delivery_enabled($shipping_method) ? 'yes' : 'no',
            'packlink_cash_on_delivery_fee' => $this->offline_payments_service->calculateFee($shipping_method->getId(), $current_total),
            'packlink_cash_on_delivery_name' => $offlinePaymentName ?: '',

        );

		foreach ( $fields as $field => $value ) {
			$this->print_hidden_input( $field, $value );
		}

		$chosen_method = wc()->session->chosen_shipping_methods[ $index ];
		if ( wc()->session->get( Shipping_Method_Helper::SHIPPING_ID, '' ) !== $chosen_method ) {
			wc()->session->set( Shipping_Method_Helper::DROP_OFF_ID, '' );
			wc()->session->set( Shipping_Method_Helper::SHIPPING_ID, '' );
		}

		if ( $rate_data['rate_id'] === $chosen_method && $shipping_method->isDestinationDropOff() ) {
			include dirname( __DIR__ ) . '/../resources/views/shipping-method-drop-off.php';
		}
	}

	/**
	 * Initializes script on cart page.
	 */
	public function after_shipping_calculator() {
		echo '<script style="display: none;">
				if (typeof Packlink !== "undefined") {
					Packlink.checkout.init();
				}
			</script>';
	}

	/**
	 * Sets hidden field for drop-off data and initializes script.
	 */
	public function after_shipping() {
		echo '<script style="display: none;">
				if (typeof Packlink !== "undefined") {
					Packlink.checkout.init();
				}
			</script>';
	}

	/**
	 * This hook is used to validate drop-off point.
	 */
	public function checkout_process() {
		$shipping_param = $this->get_param( 'shipping_method', false );
		if ( ! $shipping_param ) {
			return;
		}

		$parts = explode( ':', $shipping_param );
		$code  = $parts[0];

		if ( Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD !== $code ) {
			return;
		}

		$shipping_method = Shipping_Method_Helper::get_packlink_shipping_method( (int) $parts[1] );
		$is_drop_off     = $shipping_method->isDestinationDropOff();
		$drop_off_id     = $this->get_param( static::PACKLINK_DROP_OFF_ID );
		if ( $is_drop_off && empty( $drop_off_id ) ) {
			wc_add_notice( __( 'Please choose a drop-off location.', 'packlink-pro-shipping' ), 'error' );
		}
	}

	/**
	 * Substitutes order shipping address with drop-off address.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @param array     $data Order data.
	 *
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function checkout_update_shipping_address( \WC_Order $order, array $data ) {
		$shipping_method = $this->get_shipping_method( $data );
		if ( ! $shipping_method ) {
			return;
		}

		$is_drop_off = $shipping_method->isDestinationDropOff();
		if ( $is_drop_off ) {
			try {
				$drop_off_address = json_decode( $this->get_param( static::PACKLINK_DROP_OFF_EXTRA ), true );
				$order->set_shipping_company( $drop_off_address['name'] );
				$order->set_shipping_city( $drop_off_address['city'] );
				$order->set_shipping_postcode( $drop_off_address['zip'] );
				$order->set_shipping_state( $drop_off_address['state'] );
				$order->set_shipping_address_1( $drop_off_address['address'] );
			} catch ( \WC_Data_Exception $e ) {
				Logger::logError( 'Unable to substitute delivery address with drop-off location.', 'Integration', $data );
			}
		}
	}

	/**
	 * This hook is used to update drop-off point value.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param int   $order_id WooCommerce order identifier.
	 * @param array $data WooCommerce order meta data.
	 */
	public function checkout_update_drop_off( $order_id, array $data ) {
		$shipping_method = $this->get_shipping_method( $data );
		if ( ! $shipping_method ) {
			return;
		}

		if ( $shipping_method->isDestinationDropOff() ) {
			$order_drop_off_map_repository = RepositoryRegistry::getRepository( Order_Drop_Off_Map::CLASS_NAME );
			$order_drop_off_map            = new Order_Drop_Off_Map();
			$order_drop_off_map->set_order_id( $order_id );
			$order_drop_off_map->set_drop_off_point_id( $this->get_param( static::PACKLINK_DROP_OFF_ID ) );
			$order_drop_off_map_repository->save( $order_drop_off_map );

			wc()->session->set( Shipping_Method_Helper::DROP_OFF_ID, '' );
		}

		$wc_order = \WC_Order_Factory::get_order( $order_id );
		if ( $wc_order !== false ) {
			Paid_Order_Handler::handle( $order_id, $wc_order );
		}
	}

	/**
	 * Checks if default Packlink shipping method should be removed.
	 *
	 * @param array $rates Shipping rates.
	 *
	 * @return array Filtered shipping rates.
	 */
	public function check_additional_packlink_rate( $rates ) {
		if ( count( $rates ) === 1 ) {
			return $rates;
		}

		/**
		 * Map with key as shipping method id and rate as its value.
		 *
		 * @var string           $key
		 * @var WC_Shipping_Rate $rate
		 */
		foreach ( $rates as $key => $rate ) {
			$rate_data = $this->get_rate_data( $rate );
			if ( Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD === $rate_data['method_id'] && __( 'shipping cost', 'packlink-pro-shipping' ) === $rate_data['label'] ) {
				unset( $rates[ $key ] );
				break;
			}
		}

		return $rates;
	}

	/**
	 * Loads javascript and css resources
	 */
	public function load_scripts() {
		if ( is_cart() || is_checkout() ) {
			Script_Loader::load_js(
				array(
					'packlink/js/StateUUIDService.js',
					'packlink/js/ResponseService.js',
					'packlink/js/AjaxService.js',
					'js/location-picker/packlink-translations.js',
					'js/location-picker/packlink-location-picker.js',
					'js/packlink-checkout.js',
				)
			);
			Script_Loader::load_css(
				array(
					'css/packlink-checkout.css',
					'css/packlink-location-picker.css',
				)
			);
		}
	}

	/**
	 * Returns array of locations for this shipping service.
	 *
	 * @param int $method_id Service identifier.
	 *
	 * @return array Locations.
	 */
	public function get_drop_off_locations( $method_id ) {
		$customer = wc()->session->customer;

		/**
		 * Location service.
		 *
		 * @var LocationService $location_service
		 */
		$location_service = ServiceRegister::getService( LocationService::CLASS_NAME );

		return $location_service->getLocations( $method_id, $customer['shipping_country'], $customer['shipping_postcode'] );
	}

	/**
	 * @return string
	 */
	public function get_drop_off_locations_missing_message() {
		return __( 'There are no drop-off locations available for the entered address', 'packlink-pro-shipping' );
	}

    /**
     * Returns the display name of the current offline payment method or null.
     *
     * @return string|null
     */
    private function getOfflinePaymentName()
    {
        try {
            $offlinePayments = $this->offline_payments_service->getOfflinePayments();
            $accountConfig   = $this->offline_payments_service->getAccountConfiguration();
            $offlinePaymentName = null;

            if ($accountConfig && $accountConfig->account) {
                $id = $accountConfig->account->getOfflinePaymentMethod();

                foreach ($offlinePayments as $payment) {
                    if ($payment['name'] === $id) {
                        $offlinePaymentName = $payment['displayName'];
                        break;
                    }
                }
            }

            return $offlinePaymentName;
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * @param ShippingMethod $shippingMethod
     *
     * @return bool
     */
    private function is_cash_on_delivery_enabled($shippingMethod) {
        foreach ($shippingMethod->getShippingServices() as $service) {
            if ($service->cashOnDeliveryConfig && $service->cashOnDeliveryConfig->offered) {
                return true;
            }
        }

        return false;
    }

	/**
	 * Returns Packlink shipping method.
	 *
	 * @param array $data Order data.
	 *
	 * @return ShippingMethod|null Shipping method.
	 *
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function get_shipping_method( array $data = array() ) {
		if ( empty( $data ) || ! isset( $data['shipping_method'][0] ) ) {
			return null;
		}

		$parts       = explode( ':', $data['shipping_method'][0] );
		$code        = $parts[0];
		$instance_id = (int) $parts[1];

		if ( Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD !== $code ) {
			return null;
		}

		return Shipping_Method_Helper::get_packlink_shipping_method( $instance_id );
	}

	/**
	 * Echoes sanitized input field.
	 *
	 * @param string $field Input field name.
	 * @param string $value Input field value.
	 */
	private function print_hidden_input( $field, $value = '' ) {
		$allowed_html = array(
			'input' => array(
				'type'  => array(),
				'name'  => array(),
				'value' => array(),
			),
		);

		echo wp_kses( sprintf( '<input type="hidden" name="%s" value="%s" />', $field, $value ), $allowed_html );
	}

	/**
	 * Gets request parameter if exists. Otherwise, returns null.
	 *
	 * @param string $key Request parameter key.
	 * @param bool   $is_text Is text value.
	 *
	 * @return mixed
	 */
	private function get_param( $key, $is_text = true ) {
		if ( isset( $_REQUEST[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( $is_text ? $_REQUEST[ $key ] : $_REQUEST[ $key ][0] ) );
		}

		return null;
	}

	/**
	 * Gets the data from shipping rate keeping the backward compatibility.
	 *
	 * @param WC_Shipping_Rate $rate Shipping method.
	 *
	 * @return array
	 */
	private function get_rate_data( WC_Shipping_Rate $rate ) {
		$rate_id = method_exists( $rate, 'get_id' ) ? $rate->get_id() : $rate->id;
		if ( method_exists( $rate, 'get_instance_id' ) ) {
			$instance_id = $rate->get_instance_id();
		} else {
			$parts       = explode( ':', $rate_id );
			$instance_id = ! empty( $parts[1] ) ? $parts[1] : - 1;
		}

		return array(
			'rate_id'     => $rate_id,
			'instance_id' => (int) $instance_id,
			'method_id'   => method_exists( $rate, 'get_method_id' ) ? $rate->get_method_id() : $rate->method_id,
			'label'       => $rate->get_label(),
		);
	}
}
