<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Packlink\BusinessLogic\Controllers\OnboardingController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Onboarding_State_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Onboarding_State_Controller extends Packlink_Base_Controller {

	/**
	 * Provides current onboarding state.
	 */
	public function get_current_state() {
		$controller = new OnboardingController();
		$state      = $controller->getCurrentState();

		$this->return_json( $state->toArray() );
	}

}
