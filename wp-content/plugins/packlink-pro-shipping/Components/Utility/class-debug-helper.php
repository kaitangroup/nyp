<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Utility;

use DateTime;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Services\Logger_Service;
use WooCommerce;
use ZipArchive;

/**
 * Class Debug_Helper
 *
 * @package Packlink\WooCommerce\Components\Utility
 */
class Debug_Helper {

	const PHP_INFO_FILE_NAME         = 'phpinfo.html';
	const SYSTEM_INFO_FILE_NAME      = 'system-info.json';
	const LOG_FILE_NAME              = 'logs.txt';
	const WC_LOG_FILE_NAME           = 'wc-logs.txt';
	const USER_INFO_FILE_NAME        = 'packlink-user-info.json';
	const QUEUE_INFO_FILE_NAME       = 'queue.json';
	const PARCEL_WAREHOUSE_FILE_NAME = 'parcel-warehouse.json';
	const SERVICE_INFO_FILE_NAME     = 'services.json';
	const DATABASE                   = 'MySQL';

	/**
	 * Configuration service.
	 *
	 * @var Config_Service
	 */
	private static $config_service;

	/**
	 * Returns path to zip archive that contains current system information.
	 *
	 * @return string Temporary file path.
	 *
	 * @throws QueryFilterInvalidParamException If filter is not available.
	 */
	public static function get_system_info() {
		$file = tempnam( sys_get_temp_dir(), 'packlink_system_info' );

		$zip = new ZipArchive();
		$zip->open( $file, ZipArchive::CREATE );

		$php_info = static::get_php_info();

		if ( false !== $php_info ) {
			$zip->addFromString( static::PHP_INFO_FILE_NAME, $php_info );
		}

		$dir = dirname( Logger_Service::get_log_file() );
		$zip->addFromString( static::SYSTEM_INFO_FILE_NAME, static::get_woocommerce_shop_info() );
		$zip->addFromString( static::LOG_FILE_NAME, static::get_logs( $dir ) );
		/** Ignore. @noinspection PhpUndefinedConstantInspection */
		$zip->addFromString( static::WC_LOG_FILE_NAME, static::get_logs( WC_LOG_DIR ) );
		$zip->addFromString( static::USER_INFO_FILE_NAME, static::get_user_info() );
		$zip->addFromString( static::QUEUE_INFO_FILE_NAME, static::get_queue_status() );
		$zip->addFromString( static::PARCEL_WAREHOUSE_FILE_NAME, static::get_parcel_and_warehouse_info() );
		$zip->addFromString( static::SERVICE_INFO_FILE_NAME, self::get_entities( ShippingMethod::CLASS_NAME ) );

		$zip->close();

		return $file;
	}

	/**
	 * Retrieves php info.
	 *
	 * @return false | string
	 */
	protected static function get_php_info() {
		ob_start();
		phpinfo(); //phpcs:ignore

		return ob_get_clean();
	}

	/**
	 * Returns information about WooCommerce and plugin.
	 *
	 * @return string
	 */
	protected static function get_woocommerce_shop_info() {
		global $wpdb, $wp_version;

		$result['WooCommerce version'] = WooCommerce::instance()->version;
		$result['WordPress version']   = $wp_version;
		$result['Theme']               = wp_get_theme()->get( 'Name' );
		$result['Base admin URL']      = get_admin_url();
		$result['Database']            = static::DATABASE;
		$result['Database version']    = $wpdb->db_version();
		$result['Plugin version']      = Shop_Helper::get_plugin_version();
		$result['Auto-test URL']       = admin_url( 'admin.php?page=packlink-pro-auto-test' );

		return wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Retrieves logs from WooCommerce.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param string $dir Logs directory path.
	 *
	 * @return string Log file contents.
	 */
	protected static function get_logs( $dir ) {
		$ignore      = array( '.', '..', 'index.html', '.htaccess' );
		$dir_content = scandir( $dir, SCANDIR_SORT_NONE );

		$dir   = rtrim( $dir, '/' ) . '/';
		$start = new DateTime( '-7 days' );
		$start->setTime( 0, 0 );
		$files = array();
		foreach ( $dir_content as $file ) {
			if ( in_array( $file, $ignore, true ) ) {
				continue;
			}

			// only logs from past 7 days.
			$file_time = filemtime( $dir . '/' . $file );
			if ( $file_time >= $start->getTimestamp() ) {
				$files[ $file ] = $file_time;
			}
		}

		asort( $files );
		$result = '';
		foreach ( array_keys( $files ) as $file ) {
			$result .= file_get_contents( $dir . $file ) . "\n"; // phpcs:ignore
		}

		return $result;
	}

	/**
	 * Retrieves user info.
	 *
	 * @return string User info.
	 */
	protected static function get_user_info() {
		return wp_json_encode(
			array(
				'User info' => self::get_config_service()->getUserInfo(),
				'API Key'   => self::get_config_service()->getAuthorizationToken(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * Retrieves current queue status.
	 *
	 * @return string Queue status.
	 *
	 */
	protected static function get_queue_status() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_Store' ) ) {
			return wp_json_encode( array() );
		}

		$hooks = array( 'packlink_execute_task', 'packlink_scheduler' );

		$statuses = array(
			\ActionScheduler_Store::STATUS_PENDING,
			\ActionScheduler_Store::STATUS_RUNNING,
			\ActionScheduler_Store::STATUS_FAILED,
			\ActionScheduler_Store::STATUS_CANCELED,
		);

		$result = array();
		$store  = \ActionScheduler_Store::instance();

		foreach ( $hooks as $hook ) {
			$ids = as_get_scheduled_actions(
				array(
					'hook'     => $hook,
					'status'   => $statuses,
					'orderby'  => 'date',
					'order'    => 'DESC',
					'per_page' => 100,
				),
				'ids'
			);

			foreach ( $ids as $id ) {
				$action = $store->fetch_action( $id );
				if ( ! $action ) {
					continue;
				}

				$status = self::get_action_scheduler_status( $store, $action, $id );

				$args  = method_exists( $action, 'get_args' ) ? $action->get_args() : array();
				$group = method_exists( $action, 'get_group' ) ? $action->get_group() : null;

				$serialized_task = wp_json_encode(
					array(
						'hook'  => $hook,
						'args'  => $args,
						'group' => $group,
					)
				);

				$failure_description = null;
				$retries             = null;
				$logs = [];

				if ( $status === \ActionScheduler_Store::STATUS_FAILED || $status === 'failed' ) {
					$logs = self::get_action_scheduler_logs( $id );
				}

				if ( ! empty( $logs ) ) {
					$failure_description = implode( "\n", $logs );
					$retries             = count( $logs );
				}

				$times = self::get_action_scheduler_times( $action, $status );

				$result[] = array(
					'class_name'                     => 'ActionScheduler_Action',
					'id'                             => (string) $id,
					'status'                         => $status,

					'serializedTask'                 => is_string( $serialized_task ) ? $serialized_task : null,
					'group'                          => $group,
					'retries'                        => $retries,
					'failureDescription'             => $failure_description,

					'createTime'                     => $times['createTime'],
					'startTime'                      => $times['startTime'],
					'finishTime'                     => $times['finishTime'],
					'failTime'                       => $times['failTime'],
					'earliestStartTime'              => $times['earliestStartTime'],
					'queueTime'                      => $times['queueTime'],
					'lastUpdateTime'                 => $times['lastUpdateTime'],

					'priority'                       => self::get_action_scheduler_priority( $action ),
				);
			}
		}

		return wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Retrieves parcel and warehouse information.
	 *
	 * @return string Parcel and warehouse info.
	 */
	protected static function get_parcel_and_warehouse_info() {
		return wp_json_encode(
			array(
				'Default parcel'    => self::get_config_service()->getDefaultParcel() ?: array(),
				'Default warehouse' => self::get_config_service()->getDefaultWarehouse() ?: array(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * Retrieves entities from database info.
	 *
	 * @param string      $entity_class The class identifier of the entity.
	 *
	 * @param QueryFilter $filter Query filter.
	 *
	 * @return string Service info.
	 */
	protected static function get_entities( $entity_class, $filter = null ) {
		$result = array();

		try {
			$repository = RepositoryRegistry::getRepository( $entity_class );

			foreach ( $repository->select( $filter ) as $item ) {
				$result[] = $item->toArray();
			}
		} catch ( BaseException $e ) { // phpcs:ignore
		}

		return wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}


	/**
	 * Safely resolves Action Scheduler action status across versions.
	 *
	 * @param \ActionScheduler_Store $store
	 * @param mixed                 $action
	 * @param int                   $action_id
	 *
	 * @return string
	 */
	private static function get_action_scheduler_status( $store, $action, $action_id ) {

		if ( is_object( $store ) && method_exists( $store, 'get_status' ) ) {
			try {
				$s = $store->get_status( $action_id );
				if ( is_string( $s ) && $s !== '' ) {
					return $s;
				}
			} catch ( \Exception $e ) {
				// ignore
			}
		}

		// Fallback: action method if exists (some versions have it)
		if ( is_object( $action ) && method_exists( $action, 'get_status' ) ) {
			try {
				$s = $action->get_status();
				if ( is_string( $s ) && $s !== '' ) {
					return $s;
				}
			} catch ( \Exception $e ) {
				// ignore
			}
		}

		return '';
	}

	/**
	 * Gets Action Scheduler logs for an action id (best-effort).
	 * Returns lines like "Attempt N: <message>" (closest to your failureDescription format).
	 *
	 * @param int $action_id
	 *
	 * @return array
	 */
	private static function get_action_scheduler_logs( $action_id ) {
		$lines = array();

		// Action Scheduler logger exists in most Woo installs.
		if ( ! class_exists( 'ActionScheduler_Logger' ) ) {
			return $lines;
		}

		try {
			$logger = \ActionScheduler_Logger::instance();

			// Newer versions: get_logs( $action_id )
			if ( method_exists( $logger, 'get_logs' ) ) {
				$logs = $logger->get_logs( $action_id );

				$attempt = 1;
				foreach ( (array) $logs as $log ) {
					$msg = null;

					if ( is_object( $log ) && method_exists( $log, 'get_message' ) ) {
						$msg = $log->get_message();
					} elseif ( is_array( $log ) && isset( $log['message'] ) ) {
						$msg = $log['message'];
					} elseif ( is_object( $log ) && isset( $log->message ) ) {
						$msg = $log->message;
					}

					if ( is_string( $msg ) && $msg !== '' ) {
						$lines[] = 'Attempt ' . $attempt . ': ' . $msg;
						$attempt++;
					}
				}
			}
		} catch ( \Exception $e ) {
			// ignore
		}

		return $lines;
	}

	/**
	 * Extract times in ISO-8601 (best-effort across AS versions).
	 *
	 * @param mixed  $action
	 * @param string $status
	 *
	 * @return array
	 */
	private static function get_action_scheduler_times( $action, $status ) {
		$nulls = array(
			'createTime'        => null,
			'startTime'         => null,
			'finishTime'        => null,
			'failTime'          => null,
			'earliestStartTime' => null,
			'queueTime'         => null,
			'lastUpdateTime'    => null,
		);

		// Scheduled/earliest run time is the most reliably available piece.
		$scheduled = null;

		try {
			if ( is_object( $action ) && method_exists( $action, 'get_schedule' ) ) {
				$schedule = $action->get_schedule();
				if ( $schedule && method_exists( $schedule, 'get_date' ) ) {
					// get_date() usually returns DateTime.
					$dt = $schedule->get_date();
					if ( $dt instanceof \DateTimeInterface ) {
						$scheduled = $dt;
					}
				}
			}
		} catch ( \Exception $e ) {
			// ignore
		}

		if ( $scheduled instanceof \DateTimeInterface ) {
			$iso = $scheduled->format( \DateTime::ATOM );
			$nulls['earliestStartTime'] = $iso;
			$nulls['queueTime']         = $iso;
			$nulls['createTime']        = $iso;
			$nulls['lastUpdateTime']    = $iso;
		}

		// For running/failed/complete we can best-effort set start/fail/finish to scheduled time if nothing else.
		if ( $scheduled instanceof \DateTimeInterface ) {
			if ( $status === \ActionScheduler_Store::STATUS_RUNNING || $status === 'running' ) {
				$nulls['startTime'] = $scheduled->format( \DateTime::ATOM );
			}
			if ( $status === \ActionScheduler_Store::STATUS_FAILED || $status === 'failed' ) {
				$nulls['startTime'] = $scheduled->format( \DateTime::ATOM );
				$nulls['failTime']  = $scheduled->format( \DateTime::ATOM );
			}
			if ( $status === 'complete' || ( defined( '\ActionScheduler_Store::STATUS_COMPLETE' ) && $status === \ActionScheduler_Store::STATUS_COMPLETE ) ) {
				$nulls['startTime']  = $scheduled->format( \DateTime::ATOM );
				$nulls['finishTime'] = $scheduled->format( \DateTime::ATOM );
			}
		}

		return $nulls;
	}

	/**
	 * Priority best-effort (not always present in AS action object).
	 *
	 * @param mixed $action
	 *
	 * @return int|null
	 */
	private static function get_action_scheduler_priority( $action ) {
		// Some AS versions/actions might expose priority.
		if ( is_object( $action ) && method_exists( $action, 'get_priority' ) ) {
			try {
				return (int) $action->get_priority();
			} catch ( \Exception $e ) {
				return null;
			}
		}

		return null;
	}

	/**
	 * Gets the configuration service.
	 *
	 * @return Config_Service Configuration service instance.
	 */
	protected static function get_config_service() {
		if ( self::$config_service === null ) { // phpcs:ignore
			self::$config_service = ServiceRegister::getService( Config_Service::CLASS_NAME );
		}

		return self::$config_service;
	}
}
