<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\CustomsController as CoreController;
use Packlink\BusinessLogic\Customs\CustomsMappingService;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Customs_Controller
 *
 * AJAX delegate for the customs settings page. Wraps the core CustomsController, which owns all
 * customs business logic; this controller only marshals request/response for WooCommerce.
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Customs_Controller extends Packlink_Base_Controller {

	/**
	 * Core customs controller.
	 *
	 * @var CoreController
	 */
	private $controller;

	/**
	 * Packlink_Customs_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new CoreController(
			ServiceRegister::getService( CustomsMappingService::CLASS_NAME )
		);
	}

	/**
	 * Provides the saved customs mapping/defaults. Adds the `system` key the core
	 * CustomsController.js uses to render the page descriptions.
	 */
	public function get_data() {
		$this->validate();

		$mapping = $this->controller->getData();
		$data    = $mapping ? $mapping->toArray() : array();

		$data['system'] = 'WooCommerce';

		$this->return_json( $data );
	}

	/**
	 * Provides the list of supported country codes for the default-country select.
	 */
	public function get_countries() {
		$this->validate();

		$this->return_json( $this->controller->getAllCountries() );
	}

	/**
	 * Provides the platform-driven data-mapping field definitions rendered as selects.
	 */
	public function get_mapping_fields_options() {
		$this->validate();

		$this->return_json( $this->controller->getMappingFieldsOptions() );
	}

	/**
	 * Persists the customs mapping/defaults. On validation failure returns the field-level
	 * errors as JSON and persists nothing.
	 */
	public function save_data() {
		$this->validate( 'yes', true );

		$payload = json_decode( $this->get_raw_input(), true );

		try {
			$this->controller->save( $payload );
		} catch ( FrontDtoValidationException $e ) {
			$this->return_dto_entities_response( $e->getValidationErrors(), 400 );

			return;
		}

		$this->get_data();
	}
}
