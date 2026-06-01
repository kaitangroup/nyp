<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\Logger\Interfaces\LoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\Logger\LoggerConfiguration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Singleton;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Logger_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Logger_Service extends Singleton implements LoggerAdapter {

	const LOG_DEF     = "[%s][%d][%s] %s\n";
	const CONTEXT_DEF = "\tContext[%s]: %s\n";

	/**
	 * Singleton instance of this class.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Returns log file name.
	 *
	 * @return string Log file name.
	 */
	public static function get_log_file() {
		$upload_dir = wp_get_upload_dir();

		return $upload_dir['basedir'] . '/packlink-logs/' . date( 'Y-m-d' ) . '.log';
	}

	/**
	 * Log message in system.
	 *
	 * @param LogData $data Log data.
	 */
	public function logMessage( LogData $data ) {
		/**
		 * Logger configuration service.
		 *
		 * @var LoggerConfiguration $config_service
		 */
		$config_service = LoggerConfiguration::getInstance();
		/**
		 * Configuration service.
		 *
		 * @var Config_Service $configuration
		 */
		$configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );
		$min_log_level = $config_service->getMinLogLevel();
		$log_level     = $data->getLogLevel();
		if ( ! Shop_Helper::is_woocommerce_active() ) {
			return;
		}

		// Min log level is actually max log level.
		if ( $log_level > $min_log_level && ! $configuration->isDebugModeEnabled() ) {
			return;
		}

		$level = 'info';
		switch ( $log_level ) {
			case Logger::ERROR:
				$level = 'error';
				break;
			case Logger::WARNING:
				$level = 'warning';
				break;
			case Logger::DEBUG:
				$level = 'debug';
				break;
		}

		$message = sprintf( static::LOG_DEF, $level, $data->getTimestamp(), $data->getComponent(), $data->getMessage() );
		foreach ( $data->getContext() as $item ) {
			$message .= sprintf( static::CONTEXT_DEF, $item->getName(), $item->getValue() );
		}

		$filename = self::get_log_file();
		if ( ( $log = fopen( $filename, 'ab+' ) ) !== false ) {
			fwrite( $log, $message );
			fclose( $log );
		}
	}
}
