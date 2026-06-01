<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\BusinessLogic\Tasks\BusinessTasks\SendDraftBusinessTask;

/**
 * Class Shipment_Draft_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Shipment_Draft_Service extends ShipmentDraftService
{

	/**
	 * If manual sync is enabled, executes task for creating shipment draft for provided order id
	 * and displays success or error message,
	 * otherwise it enqueues the task for creating shipment draft for provided order id
	 * and ensures proper mapping between the order and the created task are persisted.
	 *
	 * @param $orderId
	 * @param $isDelayed
	 * @param $delayInterval
	 *
	 * @return void
	 */
	public function enqueueCreateShipmentDraftTask( $orderId, $isDelayed = false, $delayInterval = 5 )
	{
		delete_transient( 'packlink-pro-success-messages' );
		delete_transient( 'packlink-pro-error-messages' );

		if ( ! $this->get_config_service()->is_manual_sync_enabled() ) {
			parent::enqueueCreateShipmentDraftTask( $orderId, $isDelayed, $delayInterval );
		} else {
			try {
				if ( $this->is_draft_created( $orderId ) ) {
					throw new \RuntimeException( 'Draft already exists' );
				}

                $taskExecutor = $this->get_task_executor();
                $taskExecutor->executeNow( new SendDraftBusinessTask( $orderId ) );

				$translation = __(
					'Shipment draft for order %s created successfully',
					'packlink-pro-shipping'
				);
				$text = sprintf( $translation, $orderId );
				set_transient( 'packlink-pro-success-messages', $text, 30 );
			} catch ( \Exception $e ) {
				$translation = __(
					'Previous attempt to create a draft failed. Error: %s',
					'packlink-pro-shipping'
				);
				$text = sprintf( $translation, $e->getMessage() );
				set_transient( 'packlink-pro-error-messages', $text, 30 );
			}
		}
	}

	/**
	 * Retrieves config service.
	 *
	 * @return Config_Service Configuration service.
	 */
	protected function get_config_service()
	{
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Config_Service::CLASS_NAME );

		return $config_service;
	}

	/**
	 * Checks whether draft has already been created for a particular order.
	 *
	 * @param string $orderId Order id in an integrated system.
	 *
	 * @return boolean Returns TRUE if draft has been created; FALSE otherwise.
	 */
	private function is_draft_created( $orderId )
	{
		$shipmentDetails = $this->get_order_shipment_details_service()->getDetailsByOrderId( $orderId );

		if ( $shipmentDetails === null ) {
			return false;
		}

		$reference = $shipmentDetails->getReference();

		return ! empty( $reference );
	}

	/**
	 * Retrieves order-shipment details service.
	 *
	 * @return OrderShipmentDetailsService Service instance.
	 */
	private function get_order_shipment_details_service()
	{
		/** @var OrderShipmentDetailsService $orderShipmentDetailsService */
		$orderShipmentDetailsService = ServiceRegister::getService( OrderShipmentDetailsService::CLASS_NAME );

		return $orderShipmentDetailsService;
	}

    /**
     * Retrieves task executor service.
     *
     * @return TaskExecutorInterface
     */
    private function get_task_executor()
    {
        /**@var TaskExecutorInterface $taskExecutor */
        $taskExecutor = ServiceRegister::getService( TaskExecutorInterface::CLASS_NAME );

        return $taskExecutor;
    }

}
