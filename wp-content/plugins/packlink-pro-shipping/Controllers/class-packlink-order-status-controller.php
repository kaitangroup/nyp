<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\OrderStatusMappingController;
use Packlink\BusinessLogic\Language\Translator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Order_Status_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Order_Status_Controller extends Packlink_Base_Controller {

	/**
	 * Order status mapping controller.
	 *
	 * @var OrderStatusMappingController
	 */
	private $controller;

	/**
	 * Configuration service.
	 *
	 * @var Configuration
	 */
	private $config_service;

	/**
	 * Packlink_Order_Status_Controller constructor.
	 */
	public function __construct() {
		$this->controller     = new OrderStatusMappingController();
		$this->config_service = ServiceRegister::getService( \Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME );
	}

	/**
	 * Retrieves order status mapping data.
	 */
	public function get() {
		$this->return_json(
			array(
				'systemName'       => $this->config_service->getIntegrationName(),
				'mappings'         => $this->controller->getMappings(),
				'packlinkStatuses' => $this->controller->getPacklinkStatuses(),
				'orderStatuses'    => $this->get_system_statuses(),
			)
		);
	}

	/**
	 * Sets order status mappings.
	 */
	public function submit() {
		$this->validate( 'yes', true );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		$this->controller->setMappings( $payload );

		$this->return_json( array( 'success' => true ) );
	}

	/**
	 * Retrieves system order statuses.
	 *
	 * @return array System order statuses.
	 */
	private function get_system_statuses() {
		$result = array(
			'' => Translator::translate( 'orderStatusMapping.none' ),
		);

		$system_statuses = wc_get_order_statuses();
		foreach ( $system_statuses as $code => $label ) {
			$result[ $code ] = $label;
		}

		return $result;
	}

}
