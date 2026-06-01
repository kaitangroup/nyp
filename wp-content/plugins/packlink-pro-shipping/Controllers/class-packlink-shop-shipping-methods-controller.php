<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Shop_Shipping_Methods_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Shop_Shipping_Methods_Controller extends Packlink_Base_Controller {

	/**
	 * Disables shop shipping methods.
	 */
	public function disable_shop_shipping_methods() {
		/** @var ShopShippingMethodService $service */ // phpcs:ignore
		$service = ServiceRegister::getService( ShopShippingMethodService::CLASS_NAME );
		$status  = $service->disableShopServices();

		$result = array( 'success' => $status );
		if ( $status ) {
			$result['message'] = Translator::translate( 'shippingServices.successfullyDisabledShippingMethods' );
		} else {
			$result['message'] = Translator::translate( 'shippingServices.failedToDisableShippingMethods' );
		}

		$this->return_json( $result );
	}

}
