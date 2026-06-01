<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\Data\DataTransferObject;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Packlink_Base_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Base_Controller {
	/**
	 * Is request call internal.
	 *
	 * @var bool
	 */
	protected $is_internal = true;

	/**
	 * Defines actions that shop manager is allowed to do
	 */
	protected static $SHOP_MANAGER_ALLOWED_ACTIONS = array(
		'create_draft',
		'is_manual_sync_enabled',
		'get_draft_status'
	);

	/**
	 * Processes request. Reads 'action' parameter and calls action method if provided.
	 *
	 * @param string|null $action Request action.
	 */
	public function process( $action = '' ) {
		Configuration::setUICountryCode( Shop_Helper::get_user_locale() );
		if ( $this->is_internal ) {
			$this->validate_internal_call();
		}

		if ( empty( $action ) ) {
			$action = $this->get_param( 'action' );
		}

		if ( $action ) {
			if ( method_exists( $this, $action ) ) {
				$this->$action();
			} else {
				$this->return_json( array( 'error' => "Method $action does not exist!" ), 404 );
			}
		}
	}

	/**
	 * Validates if call made from plugin code is secure by checking session token.
	 * If call is not secure, returns 401 status and terminates request.
	 */
	protected function validate_internal_call() {
		$logged_user_id = get_current_user_id();
		$current_action = $this->get_param('action') ? $this->get_param('action') : '';

		if ( empty( $logged_user_id ) ||
		     ! current_user_can('administrator') &&
		     ! (current_user_can('manage_woocommerce') &&
		        in_array($current_action, self::$SHOP_MANAGER_ALLOWED_ACTIONS))) {

			status_header( 401 );
			nocache_headers();

			exit();
		}
	}

	/**
	 * Converts DTOs to array and returns a JSON response.
	 *
	 * @param DataTransferObject[] $entities Entities to be transformed to json.
	 * @param int                  $status Response status.
	 */
	protected function return_dto_entities_response( array $entities, $status = 200 ) {
		$response = array();

		foreach ( $entities as $entity ) {
			$response[] = $entity->toArray();
		}

		if ( ! empty( $entities[0] ) && $entities[0] instanceof ValidationError ) {
			$response = array( 'messages' => $response );
		}

		$this->return_json( $response, $status );
	}

	/**
	 * Returns error message with the provided status.
	 *
	 * @param string $error_message Message to be returned.
	 * @param int    $status Status code.
	 */
	protected function return_error( $error_message, $status = 400 ) {
		$this->return_json( array( 'message' => $error_message ), $status );
	}

	/**
	 * Sets response header content type to json, echos supplied $data as a json string and terminates request.
	 *
	 * @param array $data Array to be returned as a json response.
	 * @param int   $status_code Response status code.
	 */
	protected function return_json( array $data, $status_code = 200 ) {
		wp_send_json( $data, $status_code );
	}

	/**
	 * Checks whether current request is POST.
	 *
	 * @return bool
	 */
	protected function is_post() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Gets request parameter if exists. Otherwise, returns null.
	 *
	 * @param string $key Request parameter key.
	 *
	 * @return mixed
	 */
	protected function get_param( $key ) {
		if ( isset( $_REQUEST[ $key ] ) ) {
			return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
		}

		return null;
	}

	/**
	 * Gets raw request.
	 *
	 * @return string
	 */
	protected function get_raw_input() {
		return file_get_contents( 'php://input' );
	}

	/**
	 * Returns 404 response and terminates request.
	 */
	protected function redirect404() {
		status_header( 404 );
		nocache_headers();

		require get_404_template();

		exit();
	}

	/**
	 * Validates if plugin is enabled and if it is post request.
	 *
	 * @param string $post Is post request.
	 * @param bool   $only_admin Only admin should have access.
	 */
	protected function validate( $post = 'no', $only_admin = false ) {
		if ( ! Shop_Helper::is_plugin_enabled() ) {
			exit();
		}

		if ( 'yes' === $post && ! $this->is_post() ) {
			$this->redirect404();
		}

		if ( $only_admin && ! current_user_can( 'administrator' ) ) {
			$this->redirect404();
		}
	}
}
