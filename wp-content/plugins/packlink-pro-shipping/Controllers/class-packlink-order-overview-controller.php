<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Automattic\WooCommerce\Admin\API\Reports\ParameterException;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use iio\libmergepdf\Merger;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecutor\Model\TaskStatus;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDocument\DTO\ShipmentDocument;
use Packlink\BusinessLogic\ShipmentDocument\Interfaces\LabelMergeServiceInterface;
use Packlink\BusinessLogic\ShipmentDocument\Interfaces\ShipmentDocumentServiceInterface;
use Packlink\BusinessLogic\ShipmentDocument\ShipmentDocumentType;
use Packlink\BusinessLogic\ShipmentDraft\Interfaces\ShipmentDraftServiceInterface;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Services\Shipment_Draft_Service;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Packlink_Order_Overview_Controller
 *
 * @package Packlink\WooCommerce\Components\Order
 */
class Packlink_Order_Overview_Controller extends Packlink_Base_Controller {

	const COLUMN_ID               = 'packlink_print_label';
	const COLUMN_PACKLINK_ID      = 'packlink_column';
	const BULK_DOWNLOAD_ACTION_ID = 'packlink_download_labels';
	const BULK_PRINT_ACTION_ID    = 'packlink_print_labels';
	/**
	 * @var OrderShipmentDetailsService
	 */
	private $order_shipment_details_service;
	/**
	 * @var ShipmentDocumentServiceInterface
	 */
	private $shipment_document_service;
	/**
	 * @var LabelMergeServiceInterface
	 */
	private $label_merge_service;
	/**
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Request-level cache for shipment details by order id.
	 *
	 * @var array
	 */
	private $order_shipment_details_cache = array();

	/**
	 * Adds Packlink column for printing label.
	 *
	 * @param array $columns Columns.
	 *
	 * @return array Columns.
	 */
	public function add_packlink_order_columns( $columns ) {
		$result = array();

		foreach ( $columns as $key => $value ) {
			$result[ $key ] = $value;
			if ( 'order_date' === $key ) {
				$result[ static::COLUMN_PACKLINK_ID ] = __( 'Packlink PRO Shipping', 'packlink-pro-shipping' );
			}
		}

		$result[ static::COLUMN_ID ] = __( 'Packlink label', 'packlink-pro-shipping' );

		return $result;
	}

	/**
	 * Adds Packlink bulk action for printing labels.
	 *
	 * @param array $bulk_actions Bulk actions.
	 *
	 * @return array Bulk actions.
	 */
	public function add_packlink_bulk_action( $bulk_actions ) {
		$bulk_actions[ static::BULK_DOWNLOAD_ACTION_ID ] = __( 'Download Packlink shipping labels', 'packlink-pro-shipping' );
		$bulk_actions[ static::BULK_PRINT_ACTION_ID ]    = __( 'Print Packlink shipping labels', 'packlink-pro-shipping' );

		return $bulk_actions;
	}

	/**
	 * Populates one column with print label buttons
	 * and one column with Packlink shipping button.
	 *
	 * @param string $column Column.
	 * @param mixed  $data Data which is sent.
	 *
	 * @throws QueryFilterInvalidParamException When invalid filter parameters are set.
	 */
	public function populate_packlink_column( $column, $data ) {

		if ( static::COLUMN_ID !== $column && static::COLUMN_PACKLINK_ID !== $column ) {
			return;
		}

		$order_id = $this->resolve_order_id( $data );

		if ( static::COLUMN_ID === $column ) {
			echo $this->render_label_column( $order_id );

			return;
		}

		echo $this->render_packlink_column( $data );
	}

	/**
	 * Resolves the order ID for both HPOS and legacy storage.
	 *
	 * @param mixed $data Order data.
	 *
	 * @return mixed
	 */
	private function resolve_order_id( $data ) {
		if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return $data->get_id();
		}

		return $data;
	}

	/**
	 * Resolves the order object for both HPOS and legacy storage.
	 *
	 * @param mixed $data Order data.
	 *
	 * @return mixed
	 */
	private function resolve_order_object( $data ) {
		if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return $data;
		}

		global $post;

		return $post;
	}

	/**
	 * Renders Packlink label column contents for a single order.
	 *
	 * @param mixed $order_id Order ID.
	 *
	 * @return string
	 *
	 * @throws QueryFilterInvalidParamException When invalid filter parameters are set.
	 */
	private function render_label_column( $order_id ) {
		$shipment_details = $this->get_order_shipment_details( (string) $order_id );

		if ( null === $shipment_details ) {
			return '';
		}

		/** @var OrderService $order_service */
		$order_service = ServiceRegister::getService( OrderService::CLASS_NAME );

		if ( ! $order_service->isReadyToFetchShipmentLabels( $shipment_details->getShippingStatus() ) ) {
			return esc_html( __( 'Label is not yet available.', 'packlink-pro-shipping' ) );
		}

		$this->get_labels_to_print( $shipment_details );

		$documents = array_values(
			array_filter(
				$this->get_shipment_document_service()->getDocumentsForOrder( (string) $order_id ),
				function ( ShipmentDocument $document ) {
					return ShipmentDocumentType::SHIPPING_LABEL === $document->getType();
				}
			)
		);

		$params = array( 'order_id' => $order_id );

		if ( empty( $documents ) ) {
			$fallback_url = Shop_Helper::get_controller_url( 'Order_Overview', 'print_single_label', $params );

			return '<button data-pl-id="' . esc_attr( $order_id ) . '" data-pl-label="' . esc_url( $fallback_url )
			       . '" type="button" class="pl-print-label button button-primary" >'
			       . esc_html__( 'Print label', 'packlink-pro-shipping' ) . '</button>';
		}

		$download_url = Shop_Helper::get_controller_url(
			'Order_Overview',
			'get_label_pdf',
			array_merge( $params, array( 'disposition' => 'attachment' ) )
		);
		$print_url    = Shop_Helper::get_controller_url( 'Order_Overview', 'get_label_pdf', $params );

		$wrapper_class = 'pl-label-actions' . ( $documents[0]->isPrinted() ? ' pl-label-printed' : '' );

		return '<span class="' . esc_attr( $wrapper_class ) . '">'
		       . '<button type="button" class="button pl-download-label" data-pl-id="' . esc_attr( $order_id ) . '"'
		       . ' data-pl-label="' . esc_url( $download_url ) . '"'
		       . ' title="' . esc_attr__( 'Download label', 'packlink-pro-shipping' ) . '">'
		       . '<span class="dashicons dashicons-download"></span>'
		       . '<span class="pl-label-action-text">' . esc_html__( 'Download', 'packlink-pro-shipping' ) . '</span>'
		       . '</button>'
		       . '<button type="button" class="button pl-print-label-btn" data-pl-id="' . esc_attr( $order_id ) . '"'
		       . ' data-pl-print-url="' . esc_url( $print_url ) . '"'
		       . ' title="' . esc_attr__( 'Print label', 'packlink-pro-shipping' ) . '">'
		       . '<span class="dashicons dashicons-printer"></span>'
		       . '<span class="pl-label-action-text">' . esc_html__( 'Print', 'packlink-pro-shipping' ) . '</span>'
		       . '</button>'
		       . '</span>';
	}

	/**
	 * Renders Packlink shipping column contents for a single order.
	 *
	 * @param mixed $data Order data.
	 *
	 * @return string
	 */
	private function render_packlink_column( $data ) {
		if ( empty( $this->get_config_service()->getAuthorizationToken() ) ) {
			return '';
		}

		$order_data = $this->resolve_order_object( $data );

		if ( empty( $order_data ) ) {
			return '';
		}

		return $this->get_packlink_shipping_button( $order_data );
	}

	/**
	 * Adds hidden fields for Packlink controller URLs and translations to the orders overview page.
	 */
	public function add_packlink_hidden_fields() {
		include dirname( __DIR__ ) . '/resources/views/order-overview-hidden-fields.php';
	}

	/**
	 * Returns draft status for the WooCommerce order.
	 *
	 * @throws OrderShipmentDetailsNotFound
	 * @throws ParameterException
	 */
	public function get_draft_status() {
		$this->validate_admin_or_manager('no');

		$order_id = ! empty( $_GET['order_id'] ) ? $_GET['order_id'] : null;

		if ( ! $order_id ) {
			throw new ParameterException( 'Order ID missing.' );
		}

		/** @var ShipmentDraftServiceInterface $draft_service */
		$draft_service = ServiceRegister::getService( ShipmentDraftServiceInterface::CLASS_NAME );
		$draft_status  = $draft_service->getDraftStatus( (string) $order_id );

		if ( TaskStatus::COMPLETED === $draft_status->status ) {
			$shipment_details = $this->get_order_shipment_details_service()->getDetailsByOrderId( (string) $order_id );

			if ( null === $shipment_details ) {
				throw new OrderShipmentDetailsNotFound( 'Order details not found' );
			}

			$this->return_json(
				array(
					'status'       => 'created',
					'shipment_url' => $shipment_details->getShipmentUrl(),
				)
			);
		} else {
			$response = array(
				'status'       => $draft_status->status,
				'shipment_url' => '',
			);

			$this->return_json( $response );
		}
	}

	/**
	 * Prints single label.
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws OrderShipmentDetailsNotFound
	 */
	public function print_single_label() {
		$this->validate( 'no', true );

		$order_id = ! empty( $_GET['order_id'] ) ? $_GET['order_id'] : null;

		if ( ! $order_id ) {
			echo esc_html( __( 'Label is not yet available.', 'packlink-pro-shipping' ) );
			exit;
		}

		$shipment_details = $this->get_order_shipment_details_service()->getDetailsByOrderId( (string) $order_id );
		if ( null === $shipment_details ) {
			echo esc_html( __( 'Label is not yet available.', 'packlink-pro-shipping' ) );
			exit;
		}

		$labels = $this->get_labels_to_print( $shipment_details );
		$links  = $this->print_labels( $shipment_details->getReference(), $labels );

		if ( ! empty( $links ) ) {
			header( 'Location: ' . $links[0], 302 );
			exit;
		}

		echo esc_html( __( 'Label is not yet available.', 'packlink-pro-shipping' ) );
		exit;
	}

	/**
	 * Streams a single label PDF inline (same-origin proxy for browser print).
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws OrderShipmentDetailsNotFound
	 */
	public function get_label_pdf() {
		$this->validate( 'no' );

		$order_id = ! empty( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		if ( ! $order_id ) {
			status_header( 400 );
			exit;
		}

		$disposition = ! empty( $_GET['disposition'] ) ? sanitize_key( $_GET['disposition'] ) : 'inline';

		if ( $this->stream_first_shipping_label( (string) $order_id, $disposition ) ) {
			exit;
		}

		status_header( 404 );
		exit;
	}

	/**
	 * Handles bulk downloading of shipping labels.
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $action Action name.
	 * @param array  $ids List of ids.
	 *
	 * @return string
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws OrderShipmentDetailsNotFound
	 */
	public function bulk_download_labels( $redirect_to, $action, $ids ) {
		if ( self::BULK_DOWNLOAD_ACTION_ID !== $action ) {
			return esc_url_raw( $redirect_to );
		}

		$ids = apply_filters( 'woocommerce_bulk_action_ids', array_values( array_map( 'absint', $ids ) ), $action, 'order' );

		$references = $this->collect_printable_references( $ids );
		if ( empty( $references ) ) {
			return esc_url_raw( $redirect_to );
		}

		$pdf = $this->get_label_merge_service()->getMergedLabelsPdf( $references );
		if ( '' === $pdf ) {
			return esc_url_raw( $redirect_to );
		}

		$this->set_download_cookie();
		$this->return_file( $pdf );
		exit;
	}

	/**
	 * Maps order ids to the shipment references whose labels are ready to be fetched.
	 * Cheap status-only check (no API call) so the merge service is the single fetch point.
	 *
	 * @param int[] $order_ids Selected order ids.
	 *
	 * @return string[] Shipment references in input order.
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function collect_printable_references( array $order_ids ) {
		/** @var OrderService $order_service */
		$order_service = ServiceRegister::getService( OrderService::CLASS_NAME );
		$references    = array();

		foreach ( $order_ids as $order_id ) {
			$shipment_details = $this->get_order_shipment_details_service()->getDetailsByOrderId( (string) $order_id );
			if ( null === $shipment_details || empty( $shipment_details->getReference() ) ) {
				continue;
			}

			if ( ! $order_service->isReadyToFetchShipmentLabels( $shipment_details->getShippingStatus() ) ) {
				continue;
			}

			$references[] = $shipment_details->getReference();
		}

		return $references;
	}

	/**
	 * Returns the core label-merge service implementation.
	 *
	 * @return LabelMergeServiceInterface
	 */
	private function get_label_merge_service() {
		if ( null === $this->label_merge_service ) {
			$this->label_merge_service = ServiceRegister::getService( LabelMergeServiceInterface::CLASS_NAME );
		}

		return $this->label_merge_service;
	}

	/**
	 * Bulk print labels AJAX endpoint. Returns the bulk-print PDF inline so the
	 * browser print flow can blob-load it in a hidden iframe.
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws OrderShipmentDetailsNotFound
	 */
	public function bulk_print_labels_ajax() {
		$this->validate( 'yes' );

		$payload = json_decode( $this->get_raw_input(), true );
		$ids     = isset( $payload['order_ids'] ) && is_array( $payload['order_ids'] )
			? array_values( array_filter( array_map( 'absint', $payload['order_ids'] ) ) )
			: array();

		if ( empty( $ids ) ) {
			status_header( 400 );
			exit;
		}

		$references = $this->collect_printable_references( $ids );
		if ( empty( $references ) ) {
			status_header( 204 );
			exit;
		}

		$pdf = $this->get_label_merge_service()->getMergedLabelsPdf( $references );
		if ( '' === $pdf ) {
			status_header( 204 );
			exit;
		}

		$this->return_file_inline( $pdf );
		exit;
	}

	/**
	 * Loads javascript resources on order overview page.
	 */
	public function load_scripts() {
		global $post;

		if ( ( $post && 'shop_order' === $post->post_type && 'raw' === $post->filter ) ||
		     ( ! empty( $_GET['page'] ) && $_GET['page'] === 'wc-orders' ) ) {
			Script_Loader::load_js(
				array(
					'packlink/js/StateUUIDService.js',
					'packlink/js/ResponseService.js',
					'packlink/js/AjaxService.js',
					'packlink/js/PrintService.js',
					'js/packlink-order-overview.js',
					'js/packlink-order-overview-draft.js',
				)
			);
			Script_Loader::load_css( array( 'css/packlink-order-overview.css' ) );
		}
	}

	/**
	 * Returns appropriate button (Send with Packlink or View on Packlink)
	 * or label (Draft is currently being crated)
	 *
	 * @param $post
	 *
	 * @return string
	 */
	protected function get_packlink_shipping_button( $post ) {
		$orderId          = (string) ( ( $post instanceof \WP_Post ) ? $post->ID : $post->get_id() );
		$src              = Shop_Helper::get_plugin_base_url() . 'resources/images/logo.png';
		$shipment_details = $this->get_order_shipment_details( $orderId );

		if ( $shipment_details && ! empty( $shipment_details->getReference() ) ) {
			$deleted = $this->get_order_shipment_details_service()->isShipmentDeleted( $shipment_details->getReference() );
			$url     = '';
			if ( ! $deleted ) {
				$url = $shipment_details->getShipmentUrl();
			}

			return '<a ' . ( $deleted ? 'disabled' : 'target="_blank"  href="' . esc_url( $url ) . '"' )
			       . ' class="button pl-draft-button" ><img class="pl-image" src="' . esc_url( $src ) . '" alt="">'
			       . '<span>' . __( 'View on Packlink', 'packlink-pro-shipping' ) . '</span></a>';
		}

		if ( ! $this->get_config_service()->is_manual_sync_enabled() ) {
			$draft_status           = $this->get_shipment_draft_service()->getDraftStatus( $orderId );
			if ( in_array( $draft_status->status, [ TaskStatus::PENDING, TaskStatus::IN_PROGRESS ], true ) ) {
				return '<div class="pl-draft-in-progress" data-order-id="' . $orderId . '">'
				       . __( 'Draft is currently being created.', 'packlink-pro-shipping' )
				       . '</div>';
			}
		}

		if ( ! $this->get_config_service()->isIntegrationActive() ) {
			return '<button class="button pl-create-draft-button" '
			       . 'disabled '
			       . 'style="opacity:0.5;cursor:not-allowed;pointer-events:none;" '
			       . 'title="' . __( 'Integration is disabled', 'packlink-pro-shipping' ) . '">'
			       . '<img class="pl-image" src="' . esc_url( $src ) . '" alt="">'
			       . '<span>' . __( 'Send with Packlink', 'packlink-pro-shipping' ) . '</span>'
			       . '</button>';
		}

		return '<button class="button pl-create-draft-button" data-order-id="' . $orderId . '"><img class="pl-image" src="' . esc_url( $src ) . '" alt="">'
		       . '<span>' . __( 'Send with Packlink', 'packlink-pro-shipping' ) . '</span>'
		       . '</button>';
	}

	/**
	 * Resolves the first SHIPPING_LABEL document for the given order, fetches its PDF bytes,
	 * marks the document printed and streams the response. Returns true once the response has
	 * started (caller is expected to `exit()` immediately after) or false when nothing was
	 * streamed (no shipment, no shipping-label documents, or the CDN fetch failed).
	 *
	 * Used by `get_label_pdf`, `bulk_download_labels` and `bulk_print_labels_ajax` so all three
	 * paths share identical persistence + mark-printed semantics.
	 *
	 * @param string $order_id    Order id as string.
	 * @param string $disposition 'attachment' to stream as a download with a filename, anything
	 *                            else streams inline (for the browser print iframe).
	 *
	 * @return bool true if a PDF was streamed, false otherwise.
	 *
	 * @throws RepositoryNotRegisteredException
	 * @throws OrderShipmentDetailsNotFound
	 */
	private function stream_first_shipping_label( $order_id, $disposition ) {
		//TODO: function to be removed when packlink merge endpoint is implemented
		$shipment_details = $this->get_order_shipment_details_service()->getDetailsByOrderId( $order_id );
		if ( null === $shipment_details ) {
			return false;
		}

		$this->get_labels_to_print( $shipment_details );

		$documents = array_values(
			array_filter(
				$this->get_shipment_document_service()->getDocumentsForOrder( $order_id ),
				function ( ShipmentDocument $document ) {
					return ShipmentDocumentType::SHIPPING_LABEL === $document->getType();
				}
			)
		);

		if ( empty( $documents ) ) {
			return false;
		}

		$link = $documents[0]->getLink();
		$data = file_get_contents( $link );
		if ( false === $data ) {
			return false;
		}

		$this->get_shipment_document_service()->markDocumentPrinted(
			$shipment_details->getReference(),
			ShipmentDocumentType::SHIPPING_LABEL,
			$link
		);

		if ( 'attachment' === $disposition ) {
			$this->return_file( $data, sprintf( 'Packlink-shipping-label-%s.pdf', preg_replace( '/[^0-9]/', '', $order_id ) ) );
		} else {
			$this->return_file_inline( $data );
		}

		return true;
	}

	/**
	 * Returns cached shipment details for an order id.
	 *
	 * @param string $order_id
	 *
	 * @return OrderShipmentDetails|null
	 */
	private function get_order_shipment_details( $order_id ) {
		if ( array_key_exists( $order_id, $this->order_shipment_details_cache ) ) {
			return $this->order_shipment_details_cache[ $order_id ];
		}

		$this->order_shipment_details_cache[ $order_id ] =
			$this->get_order_shipment_details_service()->getDetailsByOrderId( $order_id );

		return $this->order_shipment_details_cache[ $order_id ];
	}

	/**
	 * Merges shipment labels and sets merged pdf file for download.
	 *
	 * @param array $links Array of shipment label links.
	 */
	protected function merge_labels( array $links ) {
		try {
			$paths = array();
			foreach ( $links as $link ) {
				if ( $path = $this->download_pdf( $link ) ) {
					$paths[] = $path;
				}
			}

			if ( ! empty( $paths ) ) {
				ob_start();
				try {
					$merger = new Merger();
					foreach ( $paths as $path ) {
						$merger->addFromFile( $path );
					}

					$file = $merger->merge();
				} finally {
					ob_end_clean();
				}

				if ( $file ) {
					$this->return_file( $file );
				}
			}
		} catch ( Exception $e ) {
			Logger::logError(
				__( 'Unable to create bulk labels file', 'packlink-pro-shipping' ),
				'Integration',
				array( 'labels' => $links )
			);
		}
	}

	/**
	 * Returns order labels and marks them as printed.
	 *
	 * @param OrderShipmentDetails $shipment_details
	 *
	 * @return ShipmentLabel[] Label paths.
	 *
	 * @throws RepositoryNotRegisteredException
	 */
	private function get_labels_to_print( $shipment_details ) {
		/** @var OrderService $order_service */
		$order_service = ServiceRegister::getService( OrderService::CLASS_NAME );
		if ( ! $order_service->isReadyToFetchShipmentLabels( $shipment_details->getShippingStatus() ) ) {
			return array();
		}

		$labels = $shipment_details->getShipmentLabels();

		if ( empty( $labels ) ) {
			$labels = $order_service->getShipmentLabels( $shipment_details->getReference() );
			$shipment_details->setShipmentLabels( $labels );
			$shipment_details_repository = RepositoryRegistry::getRepository( OrderShipmentDetails::CLASS_NAME );
			$shipment_details_repository->update( $shipment_details );
		}

		return $labels;
	}

	/**
	 * Marks labels as printed and returns their links.
	 *
	 * @param string          $reference
	 * @param ShipmentLabel[] $labels
	 *
	 * @return array
	 *
	 * @throws OrderShipmentDetailsNotFound
	 */
	private function print_labels( $reference, $labels ) {
		$links = array();
		foreach ( $labels as $label ) {
			$this->get_order_shipment_details_service()->markLabelPrinted( $reference, $label->getLink() );
			$links[] = $label->getLink();
		}

		return $links;
	}

	/**
	 * Sets the cookie to indicate that the file is downloaded.
	 */
	private function set_download_cookie() {
		$token = $this->get_param( 'packlink_download_token' );
		setcookie( 'packlink_download_token', $token, time() + 3600, '/' );
	}

	/**
	 * Prints file to output and sets download headers.
	 *
	 * @param string      $file     File contents.
	 * @param string|null $filename Optional download filename. Defaults to the bulk-labels filename.
	 */
	private function return_file( $file, $filename = null ) {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		@ini_set( 'display_errors', '0' );

		if ( null === $filename ) {
			$now      = date( 'Y-m-d' );
			$filename = "Packlink-bulk-shipping-labels_$now.pdf";
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . strlen( $file ) );
		header( 'Content-disposition: attachment; filename=' . $filename );
		header( 'Cache-Control: public, must-revalidate, max-age=0' );
		header( 'Pragma: public' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );

		echo $file;
	}

	/**
	 * Streams file inline (browser opens it in a viewer/iframe instead of downloading).
	 *
	 * @param string $file File contents.
	 */
	private function return_file_inline( $file ) {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		@ini_set( 'display_errors', '0' );

		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . strlen( $file ) );
		header( 'Content-Disposition: inline' );
		header( 'Cache-Control: public, must-revalidate, max-age=0' );
		header( 'Pragma: public' );

		echo $file;
	}

	/**
	 * Downloads pdf.
	 *
	 * @param string $link
	 *
	 * @return bool | string
	 */
	protected function download_pdf( $link ) {
		if ( ( $data = file_get_contents( $link ) ) === false ) {
			return $data;
		}

		$file = tempnam( sys_get_temp_dir(), 'packlink_pdf' );
		file_put_contents( $file, $data );

		return $file;
	}

	/**
	 * Returns an instance of order shipment details service.
	 *
	 * @return OrderShipmentDetailsService
	 */
	private function get_order_shipment_details_service() {
		if ( null === $this->order_shipment_details_service ) {
			$this->order_shipment_details_service = ServiceRegister::getService( OrderShipmentDetailsService::CLASS_NAME );
		}

		return $this->order_shipment_details_service;
	}

	/**
	 * Returns an instance of shipment document service.
	 *
	 * @return ShipmentDocumentServiceInterface
	 */
	private function get_shipment_document_service() {
		if ( null === $this->shipment_document_service ) {
			$this->shipment_document_service = ServiceRegister::getService( ShipmentDocumentServiceInterface::CLASS_NAME );
		}

		return $this->shipment_document_service;
	}

	/**
	 * Returns an instance of configuration service.
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( null === $this->config_service ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}

	/**
	 * Returns an instance of shipment draft service.
	 *
	 * @return Shipment_Draft_Service
	 */
	private function get_shipment_draft_service() {
		/** @var Shipment_Draft_Service $draft_service */
		$draft_service = ServiceRegister::getService( ShipmentDraftServiceInterface::CLASS_NAME );

		return $draft_service;
	}

	/**
	 * Validates if plugin is enabled and if it is post request for calls permitted only for admins and shop managers
	 *
	 * @return void
	 */
	protected function validate_admin_or_manager( $post = 'no' ) {
		if ( ! Shop_Helper::is_plugin_enabled() ) {
			exit();
		}

		if ( 'yes' === $post && ! $this->is_post() ) {
			$this->redirect404();
		}

		if (!is_user_logged_in()) {
			$this->redirect404();
		}

		if (!current_user_can('administrator') && !current_user_can('manage_woocommerce')) {
			$this->redirect404();
		}
	}
}

