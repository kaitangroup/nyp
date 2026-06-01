<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Checkout;


use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\WooCommerce\Components\Services\Offline_Payments_Service;
use Packlink\WooCommerce\Components\ShippingMethod\Packlink_Shipping_Method;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;

/**
 * Class Surcharge_Handler
 *
 * @package Packlink\WooCommerce\Components\Checkout
 */
class Surcharge_Handler {

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
     * @param $order
     * @param $request
     *
     * @return void
     */
	public function add_surcharge_block($order, $request) {
        try {
            $pm = (string) $request->get_param('payment_method');

            if (!$this->offline_payments_service->shouldSurchargeApply($pm)) {
                return;
            }

            foreach ($order->get_items('fee') as $item) {
                if ($item->get_name() === Translator::translate('cashOnDelivery.surcharge')) {
                    return;
                }
            }

            $totals = WC()->cart->get_totals();

            $subtotal = isset($totals['cart_contents_total']) ? (float) $totals['cart_contents_total'] : 0;
            $shipping = isset($totals['shipping_total']) ? (float) $totals['shipping_total'] : 0;
            $discount = isset($totals['discount_total']) ? (float) $totals['discount_total'] : 0;

            $current_total = $subtotal + $shipping - $discount;

            $surcharge = $this->offline_payments_service->calculateFee(
                $this->get_shipping_method_id(),
                $current_total
            );

            if ($surcharge > 0) {
                $fee = new \WC_Order_Item_Fee();
                $fee->set_name(Translator::translate('cashOnDelivery.surcharge'));
                $fee->set_amount($surcharge);
                $fee->set_total($surcharge);
                $order->add_item($fee);

                $order->calculate_totals(false);
                $order->save();
            }

        } catch (\Exception $e) {
        }
	}

	/**
	 * Adds surcharge for the classic checkout
	 *
	 * @param $order
	 *
	 * @return void
	 */

    public function add_surcharge_to_order( $order) {
        try {
            $chosen_gateway = WC()->session->get('chosen_payment_method');

            if ($this->offline_payments_service->shouldSurchargeApply($chosen_gateway)) {
                foreach ($order->get_items('fee') as $item) {
                    if ($item->get_name() === Translator::translate('cashOnDelivery.surcharge')) {
                        return;
                    }
                }

                $totals = WC()->cart->get_totals();

                $subtotal = isset($totals['cart_contents_total']) ? (float) $totals['cart_contents_total'] : 0;
                $shipping = isset($totals['shipping_total']) ? (float) $totals['shipping_total'] : 0;
                $discount = isset($totals['discount_total']) ? (float) $totals['discount_total'] : 0;

                $current_total = $subtotal + $shipping - $discount;

                $surcharge = $this->offline_payments_service->calculateFee(
                    $this->get_shipping_method_id(),
                    $current_total
                );

                if ($surcharge > 0) {
                    $fee = new \WC_Order_Item_Fee();
                    $fee->set_name(Translator::translate('cashOnDelivery.surcharge'));
                    $fee->set_amount($surcharge);
                    $fee->set_total($surcharge);
                    $order->add_item($fee);

                    $order->calculate_totals( false );
                    $order->save();
                }
            }
        } catch (\Exception $e) {
        }
    }


	/**
	 * @return int|null
	 *
	 * @throws QueryFilterInvalidParamException
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_shipping_method_id()
	{
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if(!$chosen_shipping_methods) {
			return null;
		}

		$chosen_shipping = $chosen_shipping_methods[0];

		if(!$chosen_shipping) {
			return null;
		}

		$parts = explode( ':', $chosen_shipping );
		$instance_id = isset( $parts[1] ) ? (int) $parts[1] : 0;

		if (  $parts[0] !== Packlink_Shipping_Method::PACKLINK_SHIPPING_METHOD || $instance_id <= 0 ) {
			return null;
		}

		$packlink_method = Shipping_Method_Helper::get_packlink_shipping_method( $instance_id );

		if (!$packlink_method) {
			return null;
		}

		return $packlink_method->getId();
	}
}