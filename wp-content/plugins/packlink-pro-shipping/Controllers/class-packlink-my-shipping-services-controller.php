<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Packlink\BusinessLogic\Controllers\ShippingMethodController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_My_Shipping_Services_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_My_Shipping_Services_Controller extends Packlink_Base_Controller {

	/**
	 * Shipping methods controller.
	 *
	 * @var ShippingMethodController
	 */
	private $controller;

	/**
	 * Packlink_My_Shipping_Services_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new ShippingMethodController();
	}

	/**
	 * Retrieves enabled services.
	 */
	public function get() {
		$this->return_dto_entities_response( $this->controller->getActive() );
	}

	/**
	 * Deactivates shipping service.
	 */
	public function deactivate() {
		$this->validate( 'yes', true );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		$status  = false;

		if ( ! empty( $payload['id'] ) ) {
			$status = $this->controller->deactivate( $payload['id'] );
		}

		$this->return_json( array( 'status' => $status ) );
	}
}
