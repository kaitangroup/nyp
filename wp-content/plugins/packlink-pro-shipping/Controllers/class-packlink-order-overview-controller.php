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

	const COLUMN_ID          = 'packlink_print_label';
	const COLUMN_PACKLINK_ID = 'packlink_column';
	const BULK_ACTION_ID     = 'packlink_print_labels';
	/**
	 * @var OrderShipmentDetailsService
	 */
	private $order_shipment_details_service;
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
		$bulk_actions[ static::BULK_ACTION_ID ] = __( 'Print labels', 'packlink-pro-shipping' );

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
		$labels        = $shipment_details->getShipmentLabels();

		if ( ! $order_service->isReadyToFetchShipmentLabels( $shipment_details->getShippingStatus() ) ) {
			return esc_html( __( 'Label is not yet available.', 'packlink-pro-shipping' ) );
		}

		$is_printed = false;

		$params    = array( 'order_id' => $order_id );
		$label_url = Shop_Helper::get_controller_url( 'Order_Overview', 'print_single_label', $params );

		if ( ! empty( $labels ) ) {
			$is_printed = $labels[0]->isPrinted();
			$label_url  = $labels[0]->getLink();
		}

		$class = 'pl-print-label button ' . ( $is_printed ? '' : 'button-primary' );
		$label = $is_printed
			? __( 'Printed label', 'packlink-pro-shipping' )
			: __( 'Print label', 'packlink-pro-shipping' );

		return '<button data-pl-id="' . esc_attr( $order_id ) . '" data-pl-label="' . esc_url( $label_url )
		       . '" type="button" class="' . esc_attr( $class ) . '" >' . esc_html( $label ) . '</button>';
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
	 * Handles bulk printing of labels.
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
	public function bulk_print_labels( $redirect_to, $action, $ids ) {
		if ( self::BULK_ACTION_ID !== $action ) {
			return esc_url_raw( $redirect_to );
		}

		$ids   = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$links = array();
		foreach ( $ids as $order_id ) {
			$shipment_details = $this->get_order_shipment_details_service()->getDetailsByOrderId( (string) $order_id );
			if ( null !== $shipment_details ) {
				$labels  = $this->get_labels_to_print( $shipment_details );
				$links[] = $this->print_labels( $shipment_details->getReference(), $labels );
			}
		}

		$links = call_user_func_array( 'array_merge', $links );
		if ( ! empty( $links ) ) {
			$this->merge_labels( $links );
			$this->set_download_cookie();
			exit;
		}

		$redirect_to = add_query_arg(
			array(
				'post_type'   => 'shop_order',
				'bulk_action' => self::BULK_ACTION_ID,
				'changed'     => 0,
				'ids'         => implode( ',', $ids ),
			),
			$redirect_to
		);

		return esc_url_raw( $redirect_to );
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
				// Bundled FPDI 1.6.2 emits PHP 7.4+/8.x warnings while parsing certain
				// label PDFs; buffer and discard so they cannot leak into the binary stream.
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
	 * @param string $file File path.
	 */
	private function return_file( $file ) {
		// Discard anything WordPress / plugins have buffered so far so that
		// stray PHP notices/warnings cannot corrupt the PDF byte stream.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		@ini_set( 'display_errors', '0' );

		$now  = date( 'Y-m-d' );
		$name = "Packlink-bulk-shipping-labels_$now.pdf";

		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . strlen( $file ) );
		header( 'Content-disposition: attachment; filename=' . $name );
		header( 'Cache-Control: public, must-revalidate, max-age=0' );
		header( 'Pragma: public' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );

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

