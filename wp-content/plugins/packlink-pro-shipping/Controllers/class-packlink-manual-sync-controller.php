<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\WooCommerce\Components\Services\Config_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Manual_Sync_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Manual_Sync_Controller extends Packlink_Base_Controller {

	/**
	 * Returns whether manual synchronization is enabled.
	 *
	 * @return void
	 */
	public function is_manual_sync_enabled() {
		$this->return_json( [ 'manual_sync_status' => $this->get_config_service()->is_manual_sync_enabled() ] );
	}

	/**
	 * Saves whether manual synchronization is enabled.
	 *
	 * @return void
	 */
	public function set_manual_sync_enabled() {
		$this->validate( 'yes', true );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		$this->get_config_service()->set_manual_sync_enabled( $payload['manual_sync_status'] );

		$this->return_json( [ 'success' => true ] );
	}

	/**
	 * Returns an instance of configuration service.
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		/** @var Config_Service $configService */
		$configService = ServiceRegister::getService( Configuration::CLASS_NAME );

		return $configService;
	}
}
