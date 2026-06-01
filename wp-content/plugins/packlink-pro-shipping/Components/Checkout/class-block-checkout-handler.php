<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Checkout;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ORM\Utility\IndexHelper;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Order\Order_Drop_Off_Map;
use Packlink\WooCommerce\Components\Services\Offline_Payments_Service;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;
use WC_Order;

/**
 * Class Block_Checkout_Handler
 *
 * @package Packlink\WooCommerce\Components\Checkout
 */
class Block_Checkout_Handler {

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
	 * Returns method details for all shipping methods rendered on checkout.
	 *
	 * @param array $payload - Shipping method IDs.
	 *
	 * @return array
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public function initialize( array $payload ) {
		$response = [
			'translations'                  => $this->get_checkout_translations(),
			'selected_shipping_method'      => $selected_shipping_method = $this->get_selected_shipping_method(),
			'method_details'                => [],
			'no_drop_off_locations_message' => __( 'There are no drop-off locations available for the entered address', 'packlink-pro-shipping' )
		];

        $parts = explode('_', determine_locale());
        $locale       = $parts[0];

        $response['locale'] = $locale;

        try{
            $offlinePayments = $this->offline_payments_service->getOfflinePayments();
            $accountConfig = $this->offline_payments_service->getAccountConfiguration();
            $offlinePaymentName = null;

            if($accountConfig && $accountConfig->account) {
                $id = $accountConfig->account->getOfflinePaymentMethod();

                foreach ($offlinePayments as $payment) {
                    if ($payment['name'] === $id) {
                        $offlinePaymentName = $payment['displayName'];
                        break;
                    }
                }
            }
            if ($offlinePaymentName !== null) {
                $response['offline_payment_name'] = $offlinePaymentName;
            }
        } catch (\Exception $e) {
        }

        $cart = WC()->cart;
        $totals = $cart->get_totals();

        $subtotal   = isset($totals['cart_contents_total']) ? (float) $totals['cart_contents_total'] : 0;
        $shipping   = isset($totals['shipping_total']) ? (float) $totals['shipping_total'] : 0;
        $discount   = isset($totals['discount_total']) ? (float) $totals['discount_total'] : 0;

        $current_total = $subtotal + $shipping - $discount;


        if ( ! count( $payload ) ) {
			$response['method_details'][ $selected_shipping_method ] = $this->get_shipping_method_details( (int) $selected_shipping_method, $current_total );

			return $response;
		}

		foreach ( $payload as $id ) {
			$response['method_details'][ $id ] = $this->get_shipping_method_details( $id, $current_total );
		}

		return $response;
	}

	/**
	 * Include location picker file.
	 *
	 * @return void
	 */
	public function load_data() {
		if ( is_checkout() ) {
			Script_Loader::load_js(
				array(
					'js/packlink-block-checkout.js',
                    'js/offline-payments.js',
				), true
			);
			Script_Loader::load_css(
				array(
					'css/packlink-block-checkout.css',
					'css/packlink-location-picker.css',
				)
			);
		}
	}

	/**
     * Renders drop-off for locations
     *
	 * @return void
	 */
    public function render_drop_off_markup() {
        if ( ! is_checkout() ) {
            return;
        }

        include dirname( __DIR__ ) . '/../resources/views/block-checkout-shipping-method-drop-off.php';
    }

	/**
	 * This hook is used to update drop-off point and order shipping address value.
	 *
	 * @param WC_Order $order
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	public function checkout_update_drop_off( WC_Order $order ) {
		$selected_shipping_method = (int) $this->get_selected_shipping_method();
		$shipping_method          = Shipping_Method_Helper::get_packlink_shipping_method(
			IndexHelper::castFieldValue( $selected_shipping_method, gettype( $selected_shipping_method ) )
		);
		if ( ! $shipping_method ) {
			return;
		}

		if ( $shipping_method->isDestinationDropOff() ) {
			$drop_off_id = wc()->session->get( Shipping_Method_Helper::DROP_OFF_ID );
			if ( empty ( $drop_off_id )) {
				wc_add_notice( __( 'Please choose a drop-off location.', 'packlink-pro-shipping' ), 'error' );

				return;
			}

			$order_drop_off_map_repository = RepositoryRegistry::getRepository( Order_Drop_Off_Map::CLASS_NAME );
			$saved_order_drop_off_map      = Shipping_Method_Helper::get_drop_off_map_for_order( $order->get_id() );
			$order_drop_off_map            = $saved_order_drop_off_map ?: new Order_Drop_Off_Map();
			$order_drop_off_map->set_order_id( $order->get_id() );
			$order_drop_off_map->set_drop_off_point_id( $drop_off_id );
			$order_drop_off_map_repository->save( $order_drop_off_map );

			$this->change_order_shipping_address( $order, wc()->session->get( Shipping_Method_Helper::DROP_OFF_EXTRA ) );

			wc()->session->set( Shipping_Method_Helper::DROP_OFF_ID, '' );
		}
	}

	/**
	 * Get details for specific shipping method.
	 *
	 * @param $shipping_id
	 *
	 * @return array
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_shipping_method_details( $shipping_id, $current_total ) {
		$shipping_method = Shipping_Method_Helper::get_packlink_shipping_method(
			IndexHelper::castFieldValue( $shipping_id, gettype( $shipping_id ) )
		);

		if ( null === $shipping_method ) {
			return [];
		}

		return array(
			'packlink_image_url'          => $shipping_method->getLogoUrl() ?:
				Shop_Helper::get_plugin_base_url() . 'resources/images/box.svg',
			'packlink_show_image'         => $shipping_method->isDisplayLogo(),
			'packlink_is_drop_off'        => $shipping_method->isDestinationDropOff(),
			'packlink_drop_off_locations' => $shipping_method->isDestinationDropOff() ?
				$this->get_drop_off_locations( $shipping_method->getId() ) : [],
            'packlink_cash_on_delivery'   => $this->is_cash_on_delivery_enabled($shipping_method),
            'packlink_cash_on_delivery_fee' => $this->offline_payments_service->calculateFee($shipping_method->getId(), $current_total),
		);
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
	 * Get available locations for drop-off shipping method.
	 *
	 * @param $method_id
	 *
	 * @return array
	 */
	private function get_drop_off_locations( $method_id ) {
		$customer = wc()->session->customer;

		/**
		 * Location service.
		 *
		 * @var LocationService $location_service
		 */
		$location_service = ServiceRegister::getService( LocationService::CLASS_NAME );

		return $location_service->getLocations(
			$method_id,
			$customer['shipping_country'],
			$customer['shipping_postcode']
		);
	}

	/**
	 * Get selected shipping method.
	 *
	 * @return mixed|string
	 */
	private function get_selected_shipping_method() {
		$chosen_shipping_methods = wc()->session->get( 'chosen_shipping_methods', '' );

		if ( empty( $chosen_shipping_methods ) ){
			return '';
		}

		return explode( ':', reset( $chosen_shipping_methods ) )[1];
	}

	/**
	 * Change order shipping address when shipping method is drop-off.
	 *
	 * @param WC_Order $order
	 * @param array    $drop_off_address
	 *
	 * @return void
	 */
	private function change_order_shipping_address( WC_Order $order, array $drop_off_address ) {
		try {
			$order->set_shipping_company( $drop_off_address['name'] );
			$order->set_shipping_city( $drop_off_address['city'] );
			$order->set_shipping_postcode( $drop_off_address['zip'] );
			$order->set_shipping_address_1( $drop_off_address['address'] );
			if (!empty($drop_off_address['state'])) {
				$order->set_shipping_state($drop_off_address['state']);
			}
		} catch ( \WC_Data_Exception $e ) {
			Logger::logError(
				'Unable to substitute delivery address with drop-off location.',
				'Integration',
				$drop_off_address
			);
		}
	}

	/**
	 * All translations needed for checkout.
	 *
	 * @return array
	 */
	private function get_checkout_translations() {
		return [
			'pickDropOff'   => __( 'Select Drop-Off Location', 'packlink-pro-shipping' ),
			'changeDropOff' => __( 'Change Drop-Off Location', 'packlink-pro-shipping' ),
			'dropOffTitle'  => __( 'Package will be delivered to:', 'packlink-pro-shipping' )
		];
	}
}