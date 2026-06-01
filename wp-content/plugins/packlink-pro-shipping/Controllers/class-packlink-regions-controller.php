<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Packlink\BusinessLogic\Controllers\RegistrationRegionsController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Regions_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Regions_Controller extends Packlink_Base_Controller {
	/**
	 * Registration regions controller.
	 *
	 * @var RegistrationRegionsController
	 */
	private $controller;

	/**
	 * Packlink_Regions_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new RegistrationRegionsController();
	}

	/**
	 * Retrieves available registration regions.
	 */
	public function get_regions() {
		$this->return_dto_entities_response( $this->controller->getRegions() );
	}
}
