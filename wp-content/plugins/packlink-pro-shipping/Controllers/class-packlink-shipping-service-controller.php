<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Model\TaskStatus;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Shipping_Service_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Shipping_Service_Controller extends Packlink_Base_Controller {

	/**
	 * Shipping method controller.
	 *
	 * @var ShippingMethodController
	 */
	private $controller;

	/**
	 * @var UpdateShippingServiceTaskStatusServiceInterface
	 */
	private $updateShippingServiceStatus;

	/**
	 * Packlink_Shipping_Service_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new ShippingMethodController();
		$this->updateShippingServiceStatus = ServiceRegister::getService(
			UpdateShippingServiceTaskStatusServiceInterface::class );
	}

	/**
	 * Provides inactive shipping services.
	 */
	public function get() {
		$this->return_dto_entities_response( $this->controller->getInactive() );
	}

	/**
	 * Provides active shipping services.
	 */
	public function get_active() {
		$this->return_dto_entities_response( $this->controller->getActive() );
	}

	/**
	 * Provides UpdateShippingServicesTask status.
	 */
	public function get_task_status() {
		if ( count( $this->controller->getAll() ) > 0 ) {
			$this->return_json( array( 'status' => TaskStatus::COMPLETED ) );

			return;
		}

		try {

			/**
			 * @var Configuration $configuration
			 */
			$configuration = ServiceRegister::getService(\Logeecom\Infrastructure\Configuration\Configuration::CLASS_NAME);

			$status = $this->updateShippingServiceStatus->getLatestStatus($configuration->getContext());

		} catch ( Exception $e ) {
			$status = TaskStatus::FAILED;
		}

		$this->return_json( array( 'status' => $status ) );
	}
}
