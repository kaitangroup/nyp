<?php

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Tasks\BusinessTasks\Upgrade_Packlink_Order_Details_Business_Task;
use Packlink\WooCommerce\Components\Utility\Database;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

global $wpdb;

$database = new Database( $wpdb );
// This section will be triggered when upgrading from 1.0.2 to 2.0.0 or later version of plugin.
if ( ! $database->plugin_already_initialized() ) {
	Shop_Helper::create_log_directory();
	$database->install();

	/**
	 * Configuration service.
	 *
	 * @var Config_Service $config_service
	 */
	$config_service = ServiceRegister::getService( Config_Service::CLASS_NAME );

		$statuses = array(
			'processing' => 'wc-processing',
			'delivered'  => 'wc-completed',
		);

		$config_service->setOrderStatusMappings( $statuses );
}

try {
	$api_key = get_option( 'wc_settings_tab_packlink_api_key' );
	if ( $api_key ) {
		/**
		 * User account service.
		 *
		 * @var UserAccountService $user_service
		 */
		$user_service = ServiceRegister::getService( UserAccountService::CLASS_NAME );
		$user_service->login( $api_key );
		delete_option( 'wc_settings_tab_packlink_api_key' );

		try {

			$order_posts = $wpdb->get_results(
				"SELECT `post_id` as `ID` FROM {$wpdb->postmeta} WHERE `meta_key` = '_packlink_draft_reference'",
				ARRAY_A
			);

			/** @var TaskExecutorInterface $task_executor */
			$task_executor = ServiceRegister::getService( TaskExecutorInterface::CLASS_NAME );
			$task_executor->enqueue(
				new Upgrade_Packlink_Order_Details_Business_Task( array_column( $order_posts, 'ID' ) )
			);
		} catch ( Exception $e ) {
			Logger::logError( 'Migration of order shipments failed.', 'Integration' );
		}
	}
} catch ( QueueStorageUnavailableException $e ) {
	Logger::logError( 'Migration of users API key failed.', 'Integration' );
}

return array();
