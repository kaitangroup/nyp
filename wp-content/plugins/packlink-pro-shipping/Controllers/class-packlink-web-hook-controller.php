<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

namespace Packlink\WooCommerce\Controllers;

use Packlink\WooCommerce\Components\Services\WebHook_Event_Handler;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Packlink_Web_Hook_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Web_Hook_Controller extends Packlink_Base_Controller {
	/**
	 * Packlink_Web_Hook_Controller constructor.
	 */
	public function __construct() {
		$this->is_internal = false;
	}

	/**
	 * Web-hook action handler
	 */
	public function index() {
		if ( ! Shop_Helper::is_plugin_enabled() ) {
			exit();
		}

		if ( ! $this->is_post() ) {
			$this->redirect404();
		}

		$result = WebHook_Event_Handler::getInstance()->handle( $this->get_raw_input() );

		$this->return_json( array( 'success' => $result ), $result ? 200 : 400 );
	}
}
