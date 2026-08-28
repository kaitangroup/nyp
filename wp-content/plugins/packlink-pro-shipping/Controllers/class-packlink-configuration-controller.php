<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\ConfigurationController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Configuration_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Configuration_Controller extends Packlink_Base_Controller {

	/**
	 * Retrieves data for the configuration page.
	 */
	public function get() {
		$controller = new ConfigurationController();

		$service    = ServiceRegister::getService( Configuration::CLASS_NAME );
		$user_info  = $service->getUserInfo();
		$data       = array(
			'helpUrl' => $controller->getHelpLink(),
			'version' => $service->getModuleVersion(),
			'email'   => null !== $user_info ? $user_info->email : '',
		);

		$this->return_json( $data );
	}
}
