<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Exception;
use iio\libmergepdf\Merger;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDocument\Interfaces\LabelMergeServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Label_Merge_Service
 *
 * Integration implementation of the core LabelMergeServiceInterface. Fetches the
 * shipping-label PDFs for the given shipment references, merges them into a single
 * PDF (via libmergepdf) and marks each label as printed. Used for bulk print/download.
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Label_Merge_Service implements LabelMergeServiceInterface {

	/**
	 * @var OrderService
	 */
	private $order_service;

	/**
	 * @var OrderShipmentDetailsService
	 */
	private $order_shipment_details_service;

	/**
	 * Label_Merge_Service constructor.
	 *
	 * @param OrderService                $order_service Core order service.
	 * @param OrderShipmentDetailsService $order_shipment_details_service Core shipment details service.
	 */
	public function __construct( $order_service, $order_shipment_details_service ) {
		$this->order_service                  = $order_service;
		$this->order_shipment_details_service = $order_shipment_details_service;
	}

	/**
	 * Merges the shipping-label PDFs for the given shipment references into a single
	 * PDF and returns the raw bytes. Page order follows the array order. Returns an
	 * empty string when no label could be fetched/merged.
	 *
	 * @param string[] $shipmentReferences Packlink shipment reference IDs.
	 *
	 * @return string Raw merged PDF bytes.
	 */
	public function getMergedLabelsPdf( array $shipmentReferences ): string {
		$paths = array();

		foreach ( $shipmentReferences as $reference ) {
			$reference = (string) $reference;

			foreach ( $this->order_service->getShipmentLabels( $reference ) as $label ) {
				$link = $label->getLink();
				if ( empty( $link ) ) {
					continue;
				}

				$data = file_get_contents( $link );
				if ( false === $data ) {
					continue;
				}

				$path = tempnam( sys_get_temp_dir(), 'packlink_pdf' );
				if ( false === $path ) {
					continue;
				}

				file_put_contents( $path, $data );
				$paths[] = $path;

				$this->mark_printed( $reference, $link );
			}
		}

		if ( empty( $paths ) ) {
			return '';
		}

		return $this->merge( $paths );
	}

	/**
	 * Merges the downloaded PDF files into a single PDF and returns its bytes.
	 *
	 * @param string[] $paths Absolute paths to temporary PDF files.
	 *
	 * @return string Raw merged PDF bytes, or empty string on failure.
	 */
	private function merge( array $paths ) {
		$merged = '';

		ob_start();
		try {
			$merger = new Merger();
			foreach ( $paths as $path ) {
				$merger->addFromFile( $path );
			}

			$merged = $merger->merge();
		} catch ( Exception $e ) {
			Logger::logError(
				__( 'Unable to create bulk labels file', 'packlink-pro-shipping' ),
				'Integration',
				array( 'message' => $e->getMessage() )
			);
		} finally {
			ob_end_clean();
			foreach ( $paths as $path ) {
				if ( is_file( $path ) ) {
					unlink( $path );
				}
			}
		}

		return is_string( $merged ) ? $merged : '';
	}

	/**
	 * Marks a single label as printed, swallowing the case where the shipment is gone.
	 *
	 * @param string $reference Shipment reference.
	 * @param string $link Label link.
	 */
	private function mark_printed( $reference, $link ) {
		try {
			$this->order_shipment_details_service->markLabelPrinted( $reference, $link );
		} catch ( Exception $e ) {
			Logger::logWarning(
				'Failed to mark label printed for shipment [' . $reference . ']: ' . $e->getMessage(),
				'Integration'
			);
		}
	}
}
