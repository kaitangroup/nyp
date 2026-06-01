<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Exception;
use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Register_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Register_Controller extends Packlink_Base_Controller {

	/**
	 * Array that identifies e-commerce
	 *
	 * @var string[]
	 */
	protected static $ecommerce_identifiers = array( 'Woocommerce' );

	/**
	 * Base controller that handles registration requests.
	 *
	 * @var RegistrationController
	 */
	private $base_controller;

	/**
	 * Packlink_Register_Controller constructor.
	 */
	public function __construct() {
		$this->base_controller = new RegistrationController();
	}

	/**
	 * Retrieves registration data.
	 */
	public function get() {
		if ( empty( $_GET['country'] ) ) { // phpcs:ignore
			$this->return_error( 'Not found!', 404 );
		}

		$this->return_json( $this->base_controller->getRegisterData( $_GET['country'] ) ); // phpcs:ignore
	}

	/**
	 * Handles registration request.
	 */
	public function submit() {
		$this->validate( 'yes', true );
		$raw                   = $this->get_raw_input();
		$payload               = json_decode( $raw, true );
		$payload['ecommerces'] = static::$ecommerce_identifiers;
		try {
			$result = $this->base_controller->register( $payload );
			$response = array('success' => $result['success']);
			if (!$result['success'] && !empty($result['errorCode'])) {
				$response['error'] = $result['errorCode'];
			}
		} catch ( FrontDtoValidationException $e ) {
			$this->return_dto_entities_response( $e->getValidationErrors() );

			return;
		} catch ( Exception $e ) {
			$this->return_json(
				array(
					'success' => false,
					'error'   => $e->getMessage(),
				)
			);

			return;
		}

		$this->return_json( $response );
	}
}
