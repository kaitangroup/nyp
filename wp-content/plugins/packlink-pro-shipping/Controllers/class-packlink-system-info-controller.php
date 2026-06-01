<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Packlink\BusinessLogic\Controllers\SystemInfoController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_System_Info_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_System_Info_Controller extends Packlink_Base_Controller {
	/**
	 * System info controller.
	 *
	 * @var SystemInfoController
	 */
	private $controller;

	/**
	 * Packlink_Regions_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new SystemInfoController();
	}

	/**
	 * Retrieves system information.
	 */
	public function get() {
		$this->return_dto_entities_response( $this->controller->get() );
	}
}
