<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;

/**
 * Class Packlink_Integration_Status_Controller
 *
 * Returns current integration activation status.
 * Called from StateController.js after the initial state check.
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Integration_Status_Controller extends Packlink_Base_Controller
{
	/**
	 * Retrieves current integration status
	 */
	public function get_status()
	{
		/** @var Configuration $configService */
		$configService = ServiceRegister::getService( Configuration::CLASS_NAME );

		$this->return_json( array(
			'status' => $configService->getIntegrationStatus() ?: 'ACTIVE',
		) );
	}
}
