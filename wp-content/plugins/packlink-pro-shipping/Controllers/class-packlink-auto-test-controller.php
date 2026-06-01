<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\BusinessLogic\Controllers\AutoTestController;
use Packlink\WooCommerce\Components\Services\Logger_Service;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Packlink_Auto_Test_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Auto_Test_Controller extends Packlink_Base_Controller {
	/**
	 * Auto test controller.
	 *
	 * @var AutoTestController
	 */
	private $controller;

	/**
	 * Packlink_Auto_Test_Controller constructor.
	 */
	public function __construct() {
		$this->controller = new AutoTestController();
	}

	/**
	 * Renders appropriate view.
	 */
	public function render() {
		Script_Loader::load_css(
			array(
				'css/packlink.css',
				'css/packlink-auto-test.css',
				'css/packlink-wp-override.css',
			)
		);
		Script_Loader::load_js(
			array(
				'packlink/js/StateUUIDService.js',
				'packlink/js/ResponseService.js',
				'packlink/js/TemplateService.js',
				'packlink/js/AjaxService.js',
				'packlink/js/UtilityService.js',
				'packlink/js/AutoTestController.js',
			)
		);

		include dirname( __DIR__ ) . '/resources/views/auto-test.php';
	}

	/**
	 * Runs the auto-test and returns the queue item ID.
	 */
	protected function start() {
		$status = $this->controller->start();
		if ( ! empty( $status['success'] ) ) {
			$action_id = $this->get_latest_auto_test_action_id();
			if ( $action_id !== null ) {
				$status['queueItemId'] = $action_id;
				$status['itemId']      = $action_id;
			}
		}

		$this->return_json( $status );
	}

	/**
	 * Checks the status of the auto-test task.
	 *
	 * @throws QueryFilterInvalidParamException When queue filter is wrong.
	 * @throws RepositoryClassException When repository class is not available.
	 * @throws RepositoryNotRegisteredException When repository is not registered in bootstrap.
	 */
	protected function checkStatus() {
		$queue_item_id = (int) $this->get_param( 'queueItemId' );
		$status        = $queue_item_id > 0 ? $this->get_action_scheduler_status( $queue_item_id ) : null;
		if ( $status === null ) {
			$status = $this->controller->checkStatus( $queue_item_id );
		}

		if ( $status['finished'] ) {
			$this->controller->stop(
				static function () {
					return Logger_Service::getInstance();
				}
			);
		}

		$this->return_json( $status );
	}

	/**
	 * Retrieves Action Scheduler status for provided action ID.
	 *
	 * @param int $action_id Action id.
	 *
	 * @return array|null
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_action_scheduler_status( $action_id ) {
		if ( ! class_exists( '\ActionScheduler' ) ) {
			return null;
		}

		$status = (string) \ActionScheduler::store()->get_status( $action_id );
		if ( $status === '' ) {
			return null;
		}

		$finished_statuses = array( 'complete', 'completed', 'failed', 'canceled', 'cancelled' );
		$failed_statuses   = array( 'failed', 'canceled', 'cancelled' );

		return array(
			'finished'     => in_array( $status, $finished_statuses, true ),
			'error'        => in_array( $status, $failed_statuses, true ) ? 'Scheduled action failed.' : '',
			'logs'         => $this->controller->getLogs(),
			'actionStatus' => $status,
			'queueItemId'  => $action_id,
		);
	}

	/**
	 * Finds latest scheduled auto-test action id.
	 *
	 * @return int|null
	 */
	private function get_latest_auto_test_action_id() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( '\ActionScheduler_Store' ) ) {
			return null;
		}

		$ids = as_get_scheduled_actions(
			array(
				'hook'     => 'packlink_execute_task',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'orderby'  => 'date',
				'order'    => 'DESC',
				'per_page' => 20,
			),
			'ids'
		);

		if ( empty( $ids ) ) {
			return null;
		}

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 ) {
				continue;
			}

			if ( $this->is_auto_test_action( $id ) ) {
				return $id;
			}
		}

		return null;
	}

	/**
	 * Checks whether action belongs to auto-test task.
	 *
	 * @param int $action_id Action id.
	 *
	 * @return bool
	 */
	private function is_auto_test_action( $action_id ) {
		if ( ! class_exists( '\ActionScheduler' ) ) {
			return false;
		}

		$store = \ActionScheduler::store();
		if ( ! method_exists( $store, 'fetch_action' ) ) {
			return false;
		}

		$action = $store->fetch_action( $action_id );
		if ( ! $action || ! method_exists( $action, 'get_args' ) ) {
			return false;
		}

		$args = $action->get_args();
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		return ! empty( $args['task_class'] ) && strpos( (string) $args['task_class'], 'AutoTestBusinessTask' ) !== false;
	}

	/**
	 * Exports all logs as a JSON file.
	 *
	 * @throws RepositoryNotRegisteredException When repository is not registered in bootstrap.
	 */
	protected function exportLogs() {
		if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
			define( 'JSON_PRETTY_PRINT', 128 );
		}

		$data = wp_json_encode( $this->controller->getLogs(), JSON_PRETTY_PRINT );
		self::dieFileFromString( $data, 'auto-test-logs.json' );
	}

	/**
	 * Sets string specified by $content as a file response.
	 *
	 * @param string $content Content to output as file.
	 * @param string $file_name The name of the file.
	 */
	private static function dieFileFromString( $content, $file_name ) {
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . $file_name );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $content ) );

		echo $content; //phpcs:ignore

		die( 200 );
	}

	/**
	 * Resolves dashboard view arguments.
	 *
	 * @return array Dashboard view arguments.
	 */
	protected function resolve_view_arguments() {
		return array(
			'dashboard_logo'   => Shop_Helper::get_plugin_base_url() . 'resources/images/logo-pl.svg',
			'download_log_url' => Shop_Helper::get_controller_url( 'Auto_Test', 'exportLogs' ),
			'debug_url'        => Shop_Helper::get_controller_url( 'Debug', 'download' ),
			'module_url'       => menu_page_url( 'packlink-pro-shipping', false ),
			'start_test_url'   => Shop_Helper::get_controller_url( 'Auto_Test', 'start' ),
			'check_status_url' => Shop_Helper::get_controller_url( 'Auto_Test', 'checkStatus' ),
		);
	}
}
