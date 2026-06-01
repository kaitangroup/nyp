<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\ManualRefreshController;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\WooCommerce\Components\Order\Order_Drop_Off_Map;
use Packlink\WooCommerce\Components\Repositories\Base_Repository;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Utility\Database;

// This section will be triggered when upgrading to 2.2.0 or later version of plugin.
global $wpdb;

$database = new Database( $wpdb );

$order_ids = $database->get_packlink_order_ids();

/** @var Config_Service $config_service */
$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );

// **********************************************
// STEP 1. **************************************
// Move data from meta table to Packlink table. *
// **********************************************
if ( ! empty( $order_ids ) ) {
	/** @var Base_Repository $order_shipment_details_repository */
	$order_shipment_details_repository = RepositoryRegistry::getRepository( OrderShipmentDetails::CLASS_NAME );
	/** @var Base_Repository $order_drop_off_map_repository */
	$order_drop_off_map_repository = RepositoryRegistry::getRepository( Order_Drop_Off_Map::CLASS_NAME );
	$draft_task_map_table = $wpdb->prefix . Database::BASE_TABLE;

	$user_info       = $config_service->getUserInfo();
	$user_domain     = 'com';
	if ( $user_info && in_array( $user_info->country, array( 'ES', 'DE', 'FR', 'IT' ) ) ) {
		$user_domain = strtolower( $user_info->country );
	}

	$base_shipment_url = "https://pro.packlink.$user_domain/private/shipments/";

	foreach ( $order_ids as $order_id ) {
		if ( metadata_exists( 'post', $order_id, '_packlink_shipment_reference' ) ) {
			$order_shipment_details = new OrderShipmentDetails();
			$order_shipment_details->setOrderId( (string) $order_id );
			$order_shipment_details->setReference( get_post_meta( $order_id, '_packlink_shipment_reference', true ) );
			$order_shipment_details->setShipmentUrl( $base_shipment_url . $order_shipment_details->getReference() );

			if ( metadata_exists( 'post', $order_id, '_packlink_shipment_status' ) ) {
				$order_shipment_details->setShippingStatus(
					get_post_meta( $order_id, '_packlink_shipment_status', true ),
					metadata_exists( 'post', $order_id, '_packlink_shipment_status_update_time' )
						? get_post_meta( $order_id, '_packlink_shipment_status_update_time', true ) : null
				);
			}

			if ( metadata_exists( 'post', $order_id, '_packlink_carrier_tracking_code' ) ) {
				$order_shipment_details->setCarrierTrackingNumbers( get_post_meta( $order_id, '_packlink_carrier_tracking_code', true ) );
			}

			if ( metadata_exists( 'post', $order_id, '_packlink_carrier_tracking_url' ) ) {
				$order_shipment_details->setCarrierTrackingUrl( get_post_meta( $order_id, '_packlink_carrier_tracking_url', true ) );
			}

			if ( metadata_exists( 'post', $order_id, '_packlink_shipment_price' ) ) {
				$order_shipment_details->setShippingCost( get_post_meta( $order_id, '_packlink_shipment_price', true ) );
			}

			if ( metadata_exists( 'post', $order_id, '_packlink_shipment_labels' ) ) {
				$labels = get_post_meta( $order_id, '_packlink_shipment_labels', true );
				if ( ! empty( $labels ) ) {
					$label_printed  = 'yes' === get_post_meta( $order_id, '_packlink_label_printed', true );
					$shipment_label = new ShipmentLabel( $labels[0], $label_printed );
					$order_shipment_details->setShipmentLabels( array( $shipment_label ) );
				}
			}

			if ( metadata_exists( 'post', $order_id, '_packlink_drop_off_point_id' ) ) {
				$order_shipment_details->setDropOffId( get_post_meta( $order_id, '_packlink_drop_off_point_id', true ) );

				$order_drop_off_map = new Order_Drop_Off_Map();
				$order_drop_off_map->set_order_id( $order_id );
				$order_drop_off_map->set_drop_off_point_id( get_post_meta( $order_id, '_packlink_drop_off_point_id', true ) );

				$order_drop_off_map_repository->save( $order_drop_off_map );
			}

			$order_shipment_details_repository->save( $order_shipment_details );

			if ( metadata_exists( 'post', $order_id, '_packlink_send_draft_task_id' ) ) {
				$order_id_str = (string) $order_id;
				$execution_id = get_post_meta( $order_id, '_packlink_send_draft_task_id', true );

				$order_draft_task_map = new OrderSendDraftTaskMap();
				$order_draft_task_map->setOrderId( $order_id_str );
				$order_draft_task_map->setExecutionId( $execution_id );

				$map_type    = $order_draft_task_map->getConfig()->getType();
				$existing_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$draft_task_map_table} WHERE type = %s AND index_1 = %s LIMIT 1",
						$map_type,
						$order_id_str
					)
				);

				if ( ! $existing_id ) {
					$wpdb->insert(
						$draft_task_map_table,
						array(
							'type'    => $map_type,
							'index_1' => $order_id_str,
							'index_2' => $execution_id,
							'index_3' => null,
							'index_4' => null,
							'index_5' => null,
							'index_6' => null,
							'index_7' => null,
							'index_8' => null,
							'data'    => wp_json_encode( $order_draft_task_map->toArray() ),
						)
					);
				}
			}
		}
	}
}

// **********************************************
// STEP 2. **************************************
// Remove meta data. ****************************
// **********************************************
$database->remove_packlink_meta_data();

// **********************************************
// STEP 3. **************************************
// Enqueue task for updating shipping services. *
// **********************************************

/** @var UpdateShippingServiceTaskStatusServiceInterface $status_service */
$status_service = ServiceRegister::getService( UpdateShippingServiceTaskStatusServiceInterface::class );
/** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
$orchestrator = ServiceRegister::getService( UpdateShippingServicesOrchestratorInterface::class );
$manual_refresh_controller = new ManualRefreshController( $status_service, $orchestrator );
$manual_refresh_controller->enqueueUpdateTask();
