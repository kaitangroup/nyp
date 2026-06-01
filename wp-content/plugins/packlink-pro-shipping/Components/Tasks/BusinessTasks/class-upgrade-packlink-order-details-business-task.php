<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Tasks\BusinessTasks;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;

/**
 * Business task for upgrading Packlink order details.
 */
class Upgrade_Packlink_Order_Details_Business_Task implements BusinessTask {

	const INITIAL_PROGRESS_PERCENT = 5;
	const DEFAULT_BATCH_SIZE       = 200;

	/**
	 * Order shipment details service.
	 *
	 * @var OrderShipmentDetailsService
	 */
	private $order_shipment_details_service;

	/**
	 * Proxy instance.
	 *
	 * @var Proxy
	 */
	private $proxy;

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	private $batch_size = self::DEFAULT_BATCH_SIZE;

	/**
	 * Total orders count.
	 *
	 * @var int
	 */
	private $total_orders_count;

	/**
	 * Start date timestamp.
	 *
	 * @var int
	 */
	private $start_date;

	/**
	 * Order ids.
	 *
	 * @var array
	 */
	private $order_ids;

	/**
	 * Optional execution config override.
	 *
	 * @var TaskExecutionConfig|null
	 */
	private $execution_config;

	/**
	 * Upgrade_Packlink_Order_Details_Business_Task constructor.
	 *
	 * @param array $order_ids Order ids.
	 * @param int|null $start_date Start date timestamp.
	 * @param TaskExecutionConfig|null $execution_config Optional execution config override.
	 */
	public function __construct( array $order_ids, $start_date = null, TaskExecutionConfig $execution_config = null ) {
		/** @var TimeProvider $time_provider */
		$time_provider            = ServiceRegister::getService( TimeProvider::CLASS_NAME );
		$this->order_ids          = $order_ids;
		$this->total_orders_count = count( $order_ids );
		$this->start_date         = $start_date ?: $time_provider->getDateTime( strtotime( '-60 days' ) )->getTimestamp();
		$this->execution_config   = $execution_config;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {

		if ( $this->total_orders_count === 0 ) {
			yield 100;
			return;
		}

		yield self::INITIAL_PROGRESS_PERCENT;

		$count = count( $this->order_ids );
		while ( $count > 0 ) {
			$order_ids = $this->get_batch_order_ids();
			yield null;

			foreach ( $order_ids as $order_id ) {
				$order     = \WC_Order_Factory::get_order( $order_id );
				$reference = get_post_meta( $order_id, '_packlink_draft_reference', true );
				if ( ! $order || ! $reference ) {
					continue;
				}

				$inactive    = $order->has_status( array( 'completed', 'failed', 'cancelled', 'refunded' ) );
				$modified_at = $order->get_date_modified();

				$in_time_limit = $modified_at && $modified_at->getTimestamp() >= $this->start_date;
				if ( $in_time_limit && ! $inactive ) {
					try {
						$shipment = $this->get_proxy()->getShipment( $reference );
						if ( $shipment ) {
							$this->set_shipment_details( $order, $shipment );
						}
					} catch ( \Exception $e ) {
						Logger::logError( $e->getMessage(), 'Integration' );
					}
				} else {
					$order->update_meta_data( '_is_packlink_shipment', 'yes' );
					$order->update_meta_data( '_packlink_shipment_reference', $reference );
				}

				delete_post_meta( $order_id, '_packlink_draft_reference' );
			}

			$this->remove_finished_batch();
			yield $this->get_batch_progress();

			$count = count( $this->order_ids );
		}

		yield 100;
	}

	/**
	 * @inheritDoc
	 */
	public function toArray(): array {
		$data = array(
			'order_ids'  => $this->order_ids,
			'start_date' => $this->start_date,
		);

		if ( $this->execution_config ) {
			$data['execution_config'] = $this->execution_config->toArray();
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public static function fromArray( array $data ): BusinessTask {
		$execution_config = null;
		if ( ! empty( $data['execution_config'] ) && is_array( $data['execution_config'] ) ) {
			$execution_config = TaskExecutionConfig::fromArray( $data['execution_config'] );
		}

		return new static(
			$data['order_ids'] ?? array(),
			$data['start_date'] ?? null,
			$execution_config
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getExecutionConfig() {
		return $this->execution_config;
	}

	/**
	 * Returns array of order ids that should be processed in this batch.
	 *
	 * @return array Batch of order ids.
	 */
	private function get_batch_order_ids() {
		return array_slice( $this->order_ids, 0, $this->batch_size );
	}

	/**
	 * Remove finished batch orders
	 */
	private function remove_finished_batch() {
		$this->order_ids = array_slice(
			$this->order_ids,
			$this->batch_size
		);
	}

	/**
	 * Calculates batch progress for logging.
	 *
	 * @return float
	 */
	private function get_batch_progress() {
		$synced = $this->total_orders_count - count( $this->order_ids );
		$progress_step = $synced * ( 100 - self::INITIAL_PROGRESS_PERCENT ) / $this->total_orders_count;

		return self::INITIAL_PROGRESS_PERCENT + $progress_step;
	}

	/**
	 * Sets order shipment details.
	 *
	 * @param \WC_Order $order Order object.
	 * @param Shipment  $shipment Shipment details.
	 */
	private function set_shipment_details( \WC_Order $order, Shipment $shipment ) {
		if ( $this->set_reference( $order, $shipment->reference ) ) {
			$this->set_shipping_status( $shipment );
			$this->set_tracking_info( $shipment );
		}
	}

	/**
	 * Sets reference number for order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $reference Shipment reference number.
	 *
	 * @return bool Success flag.
	 */
	private function set_reference( \WC_Order $order, $reference ) {
		$order->update_meta_data( '_is_packlink_shipment', 'yes' );
		$order->save();

		$this->get_order_shipment_details_service()->setReference( (string) $order->get_id(), $reference );

		return true;
	}

	/**
	 * Sets order shipment status.
	 *
	 * @param Shipment $shipment Shipment details.
	 */
	private function set_shipping_status( Shipment $shipment ) {
		$shipping_status = ShipmentStatus::getStatus( $shipment->status );
		$this->get_order_shipment_details_service()->setShippingStatus( $shipment->reference, $shipping_status );
	}

	/**
	 * Sets order shipment tracking info.
	 *
	 * @param Shipment $shipment Shipment details.
	 */
	private function set_tracking_info( Shipment $shipment ) {
		try {
			$tracking_info = $this->get_proxy()->getTrackingInfo( $shipment->reference );
			$this->get_order_shipment_details_service()->setTrackingInfo( $shipment, '', $tracking_info );
		} catch ( \Exception $e ) {
			Logger::logError( $e->getMessage(), 'Integration' );
		}
	}

	/**
	 * Returns order repository instance.
	 *
	 * @return OrderShipmentDetailsService Order repository.
	 */
	private function get_order_shipment_details_service() {
		if ( ! $this->order_shipment_details_service ) {
			$this->order_shipment_details_service = ServiceRegister::getService(
				OrderShipmentDetailsService::CLASS_NAME
			);
		}

		return $this->order_shipment_details_service;
	}

	/**
	 * Returns proxy instance.
	 *
	 * @return Proxy Proxy instance.
	 */
	private function get_proxy() {
		if ( ! $this->proxy ) {
			$this->proxy = ServiceRegister::getService( Proxy::CLASS_NAME );
		}

		return $this->proxy;
	}
}
