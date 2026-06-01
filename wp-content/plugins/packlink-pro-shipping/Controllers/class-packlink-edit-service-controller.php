<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Edit_Service_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Edit_Service_Controller extends Packlink_Base_Controller {


	/**
	 * Shipping method controller.
	 *
	 * @var ShippingMethodController
	 */
	private $controller;

	/**
	 * Shipping method service.
	 *
	 * @var ShippingMethodService
	 */
	private $service;

	/**
	 * Shop shipping method service.
	 *
	 * @var ShopShippingMethodService
	 */
	private $shop_shipping_method_service;

	/**
	 * Packlink_Edit_Service_Controller constructor.
	 */
	public function __construct() {
		$this->controller                   = new ShippingMethodController();
		$this->service                      = ServiceRegister::getService( ShippingMethodService::CLASS_NAME );
		$this->shop_shipping_method_service = ServiceRegister::getService( ShopShippingMethodService::CLASS_NAME );
	}

	/**
	 * Retrieves shipping service.
	 */
	public function get_service() {
		// Method get_query_var fails to provide the id query parameter when it is present.

		if ( empty( $_GET['id'] ) ) { // phpcs:ignore
			$this->return_error( 'Not found!', 404 );

			return;
		}

		$method = $this->controller->getShippingMethod( $_GET['id'] ); // phpcs:ignore
		if ( null === $method ) {
			$this->return_error( 'Not found!', 404 );

			return;
		}

		$this->return_json( $method->toArray() );
	}

	/**
	 * Updates shipping service.
	 */
	public function update_service() {
		$this->validate( 'yes', true );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		try {
			$configuration = ShippingMethodConfiguration::fromArray( $payload );
		} catch ( FrontDtoValidationException $e ) {
			$this->return_dto_entities_response( $e->getValidationErrors(), 400 );

			return;
		}

		$response = $this->controller->save( $configuration );

		$this->return_json( $response ? $response->toArray() : array() );
	}
}
