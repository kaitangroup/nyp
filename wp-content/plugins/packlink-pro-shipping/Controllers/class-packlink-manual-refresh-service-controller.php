<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\ManualRefreshController;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class Packlink_Manual_Refresh_Service_Controller extends Packlink_Base_Controller {

    /**
     * @var ManualRefreshController $manual_refresh_controller
     */
    private $manual_refresh_controller;

	public function __construct() {
	    /** @var UpdateShippingServiceTaskStatusServiceInterface $statusService */
		$statusService = ServiceRegister::getService( UpdateShippingServiceTaskStatusServiceInterface::class );

		/** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
		$orchestrator = ServiceRegister::getService( UpdateShippingServicesOrchestratorInterface::class );

		$this->manual_refresh_controller = new ManualRefreshController(
			$statusService,
			$orchestrator
		);
	}

	public function refresh() {

		$this->return_json( $this->manual_refresh_controller->enqueueUpdateTask()->toArray() );
	}

	/**
	 * Get status of the task
	 */
	public function get_task_status() {
		$this->return_json( $this->manual_refresh_controller->getTaskStatus()->toArray() );
	}
}
