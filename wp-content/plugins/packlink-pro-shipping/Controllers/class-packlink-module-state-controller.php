<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Packlink\BusinessLogic\Controllers\ModuleStateController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Module_State_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Module_State_Controller extends Packlink_Base_Controller {
	/**
	 * Retrieves current state.
	 */
	public function get_state() {
		$state_controller = new ModuleStateController();

		$this->return_json( $state_controller->getCurrentState()->toArray() );
	}
}
