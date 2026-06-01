<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\LoginController;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;
use Packlink\BusinessLogic\User\UserAccountService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Login_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Login_Controller extends Packlink_Base_Controller {

	/**
	 * Performs user login.
	 *
	 */
	public function login() {
		$this->validate( 'yes', true );
		$raw        = $this->get_raw_input();
		$payload    = json_decode( $raw, true );
		$controller = new LoginController(
			ServiceRegister::getService(UserAccountService::CLASS_NAME),
			ServiceRegister::getService(IntegrationRegistrationServiceInterface::CLASS_NAME),
			ServiceRegister::getService(Configuration::CLASS_NAME)
		);
		$result     = $controller->login(
			! empty(
			$payload['apiKey']
			) ? $payload['apiKey'] : ''
		);

		$response = array('success' => $result['success']);

		if (!$result['success'] && !empty($result['errorCode'])) {
			$response['error'] = $result['errorCode'];
		}

		$this->return_json( $response );
	}

}
