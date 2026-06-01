<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

namespace Packlink\WooCommerce\Controllers;

use Packlink\WooCommerce\Components\Checkout\Block_Checkout_Handler;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;

/**
 * Class Packlink_Checkout_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Checkout_Controller extends Packlink_Base_Controller {

	/**
	 * Is request call internal.
	 *
	 * @var bool
	 */
	protected $is_internal = false;

	/**
	 * Saves selected drop-off point to session.
	 */
	public function save_selected() {
		$this->validate( 'yes' );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		if ( ! is_array( $payload ) ) {
			$this->return_json( array( 'success' => false ) );
		}

		wc()->session->set( Shipping_Method_Helper::DROP_OFF_ID, $payload['id'] );
		wc()->session->set( Shipping_Method_Helper::DROP_OFF_EXTRA, $payload );
		wc()->session->set( Shipping_Method_Helper::SHIPPING_ID, $chosen_method = wc()->session->chosen_shipping_methods[0] );
		$this->return_json( array( 'success' => true ) );
	}

	/**
	 * @return void
	 */
	public function initialize_block_checkout() {
		$this->validate( 'yes' );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		wc()->session->set( Shipping_Method_Helper::BLOCK_CHECKOUT, true);
		$this->return_json( (new Block_Checkout_Handler())->initialize($payload) );
	}
}
