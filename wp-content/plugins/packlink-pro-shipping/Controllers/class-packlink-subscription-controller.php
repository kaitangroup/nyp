<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Exception;
use Packlink\BusinessLogic\Controllers\SubscriptionController;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Subscription_Controller
 *
 * Exposes the core SubscriptionController (plan tier + promotional banner) to the frontend.
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Subscription_Controller extends Packlink_Base_Controller {
	/**
	 * Core subscription controller.
	 *
	 * @var SubscriptionController
	 */
	private $controller;

	/**
	 * Packlink_Subscription_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new SubscriptionController();
	}

	/**
	 * Returns the merchant's subscription plan tier and display name.
	 */
	public function get_plan() {
		try {
			$this->return_json( $this->controller->getPlan()->toArray() );
		} catch ( Exception $e ) {
			$this->return_json( array( 'planTier' => null, 'planName' => null ) );
		}
	}

	/**
	 * Returns promotional banner data (plan tier, banner label, upgrade URL).
	 */
	public function get_promotional_banner() {
		try {
			$this->return_json( $this->controller->getPromotionalBanner()->toArray() );
		} catch ( Exception $e ) {
			$this->return_json( array( 'planTier' => null, 'bannerLabel' => null, 'upgradeUrl' => null ) );
		}
	}
}
