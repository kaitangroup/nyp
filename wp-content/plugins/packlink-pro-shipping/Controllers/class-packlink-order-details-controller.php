<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\Interfaces\ShipmentDraftServiceInterface;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Helper;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use WC_Order_Factory;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;
use WP_Post;

/**
 * Class Packlink_Order_Detail
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Order_Details_Controller extends Packlink_Base_Controller {

	/**
	 * Renders Packlink PRO Shipping post box content.
	 *
	 * @param int $id Order id.
	 *
	 * @throws QueryFilterInvalidParamException When query filter invalid.
	 * @throws RepositoryNotRegisteredException When repository not registered.
	 *
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	public function render( int $id ) {
		Script_Loader::load_css( array( 'css/packlink-order-details.css' ) );
		Script_Loader::load_js(
			array(
				'packlink/js/StateUUIDService.js',
				'packlink/js/ResponseService.js',
				'packlink/js/AjaxService.js',
				'js/packlink-order-details.js',
			)
		);

		$wc_order = WC_Order_Factory::get_order( $id );

		/** @var OrderShipmentDetailsService $shipment_details_service */ // phpcs:ignore
		$shipment_details_service = ServiceRegister::getService( OrderShipmentDetailsService::CLASS_NAME );
		/** @var ShipmentDraftServiceInterface $draft_service */ // phpcs:ignore
		$draft_service      = ServiceRegister::getService( ShipmentDraftServiceInterface::CLASS_NAME );
		$order_details      = $shipment_details_service->getDetailsByOrderId( (string) $id );
		$last_status_update = '';
		if ( $order_details && $order_details->getLastStatusUpdateTime() ) {
			$update_timestamp   = $order_details->getLastStatusUpdateTime()->getTimestamp();
			$last_status_update = date( get_option( 'links_updated_date_format' ), $update_timestamp ); // phpcs:ignore
		}

		$shipment_deleted = $order_details ? $shipment_details_service->isShipmentDeleted(
			$order_details->getReference() ? $order_details->getReference() : '') : true;
		$draft_status     = $draft_service->getDraftStatus( (string) $id );
		$shipping_method  = Shipping_Method_Helper::get_packlink_shipping_method_from_order( $wc_order );

		if ( $shipping_method && empty( $shipping_method->getLogoUrl() ) ) {
			$shipping_method->setLogoUrl( Shop_Helper::get_plugin_base_url() . 'resources/images/box.svg' );
		}

		$integration_active = ServiceRegister::getService(Configuration::CLASS_NAME)->isIntegrationActive();

		include dirname( __DIR__ ) . '/resources/views/meta-post-box.php';
	}

	/**
	 * Forces create of shipment draft for order.
	 *
	 */
	public function create_draft() {
		$this->validate( 'yes' );
		$raw     = $this->get_raw_input();
		$payload = json_decode( $raw, true );
		if ( ! array_key_exists( 'id', $payload ) ) {
			$this->return_json( array( 'success' => false ), 400 );
		}

		/** @var ShipmentDraftServiceInterface $draft_service */ // phpcs:ignore
		$draft_service = ServiceRegister::getService( ShipmentDraftServiceInterface::CLASS_NAME );
		$draft_service->enqueueCreateShipmentDraftTask( (string) $payload['id'] );

		$this->return_json( array( 'success' => true ) );
	}
}
