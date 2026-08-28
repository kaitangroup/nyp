<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShipmentDocument\DTO\ShipmentDocument;
use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;
use Packlink\BusinessLogic\ShipmentDraft\Utility\DraftStatus;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Order details.
 *
 * @var WC_Order             $wc_order
 * @var OrderShipmentDetails $order_details
 * @var bool                 $shipment_deleted
 * @var ShipmentDraftStatus  $draft_status
 * @var ShippingMethod       $shipping_method
 * @var string               $last_status_update
 * @var bool                 $integration_active
 * @var ShipmentDocument[]   $shipping_label_documents
 * @var ShipmentDocument[]   $customs_invoice_documents
 * @var string               $label_proxy_url
 * @var string               $label_download_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$draft_in_progress_statuses = array(
	DraftStatus::PROCESSING,
	DraftStatus::DELAYED,
);
?>

<ul class="order_actions submitbox" xmlns="http://www.w3.org/1999/html">
	<?php if ( $order_details && $order_details->getReference() ) : ?>
		<li class="wide">
			<?php if ( $shipping_method ) : ?>
				<div class="pl-order-detail-section">
					<h4><?php echo __( 'Carrier', 'packlink-pro-shipping' ); ?></h4>
					<div>
						<img
								class="pl-carrier-image"
								src="<?php echo $shipping_method->getLogoUrl() ?>"
								alt="carrier image"
						/>
						<span><?php echo $shipping_method->getTitle(); ?></span>
					</div>

					<?php if ( ! empty( $order_details->getCarrierTrackingNumbers() ) ) : ?>
						<dl>
							<dt><?php echo __( 'Carrier tracking codes:', 'packlink-pro-shipping' ); ?></dt>
							<?php foreach ( $order_details->getCarrierTrackingNumbers() as $carrier_code ) : ?>
								<dd><?php echo $carrier_code; ?></dd>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>

					<?php if ( $order_details->getCarrierTrackingUrl() ) : ?>
						<a href="<?php echo $order_details->getCarrierTrackingUrl(); ?>" target="_blank">
							<button type="button" class="button pl-button-view pl-carrier-button"
									name="view on packlink" value="View">
								<?php echo __( 'Track it!', 'packlink-pro-shipping' ); ?>
							</button>
						</a>
						<br/>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="pl-order-detail-section">
				<h4><?php echo __( 'Status', 'packlink-pro-shipping' ); ?></h4>
				<span class="pl-timestamp">
					<?php echo $last_status_update; ?>
					<b><?php echo ucfirst( $order_details->getShippingStatus() ); ?></b>
				</span>
			</div>

			<div class="pl-order-detail-section">
				<h4><?php echo __( 'Reference number', 'packlink-pro-shipping' ); ?></h4>
				<span><?php echo $order_details->getReference(); ?></span>
			</div>

			<?php if ( $order_details->getShippingCost() > 0 ) : ?>
				<div class="pl-order-detail-section">
					<h4><?php echo __( 'Packlink shipping price', 'packlink-pro-shipping' ); ?></h4>
					<?php echo wc_price(
							$order_details->getShippingCost(),
							array('currency' => $order_details->getCurrency())
					); ?>
				</div>
			<?php endif; ?>
		</li>

		<?php if ( ! $shipment_deleted ) : ?>
			<li class="wide">
				<?php if ( ! empty( $shipping_label_documents ) ) : ?>
					<div class="pl-document-section">
						<h4><?php echo esc_html__( 'Shipping label', 'packlink-pro-shipping' ); ?></h4>
						<a href="<?php echo esc_url( $label_download_url ); ?>" target="_blank"
						   class="button button-primary pl-doc-button">
							<?php echo esc_html__( 'Download', 'packlink-pro-shipping' ); ?>
						</a>
						<a href="#" class="button pl-doc-button pl-print-action"
						   data-print-url="<?php echo esc_url( $label_proxy_url ); ?>">
							<?php echo esc_html__( 'Print', 'packlink-pro-shipping' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $customs_invoice_documents ) ) :
					$customs_invoice_document = $customs_invoice_documents[0];
					?>
					<div class="pl-document-section">
						<h4><?php echo esc_html__( 'Customs label', 'packlink-pro-shipping' ); ?></h4>
						<a href="<?php echo esc_url( $customs_invoice_document->getLink() ); ?>" target="_blank"
						   class="button button-primary pl-doc-button">
							<?php echo esc_html__( 'Download', 'packlink-pro-shipping' ); ?>
						</a>
						<a href="#" class="button pl-doc-button pl-print-action"
						   data-print-url="<?php echo esc_url( $customs_invoice_document->getLink() ); ?>">
							<?php echo esc_html__( 'Print', 'packlink-pro-shipping' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<div class="pl-document-section pl-view-actions">
					<a href="<?php echo $order_details->getShipmentUrl(); ?>" target="_blank">
						<button type="button" class="button pl-button-view" name="view on packlink" value="View">
							<?php echo __( 'View on Packlink PRO', 'packlink-pro-shipping' ); ?>
						</button>
					</a>
				</div>
			</li>
		<?php endif; ?>
	<?php elseif ( ! in_array( $draft_status->status, $draft_in_progress_statuses, true ) ) : ?>
		<li class="wide">
			<div class="pl-order-detail-section pl-create-draft">

				<?php if ( DraftStatus::FAILED === $draft_status->status ) : ?>
					<span><?php echo sprintf( __( 'Previous attempt to create a draft failed. Error: %s', 'packlink-pro-shipping' ), $draft_status->message ); ?></span>
					<br/>
				<?php endif; ?>

				<input type="hidden" id="pl-create-endpoint"
					   value="<?php echo Shop_Helper::get_controller_url( 'Order_Details', 'create_draft' ); ?>"/>
				<?php if ( $integration_active ) : ?>
					<button type="button" class="button button-primary" id="pl-create-draft"
							value="<?php echo $wc_order->get_id(); ?>">
						<?php echo __( 'Create draft', 'packlink-pro-shipping' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="button button-primary" id="pl-create-draft"
							disabled
							style="opacity:0.5;cursor:not-allowed;pointer-events:none;"
							title="<?php echo __( 'Integration is disabled', 'packlink-pro-shipping' ); ?>">
						<?php echo __( 'Create draft', 'packlink-pro-shipping' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</li>
	<?php else : ?>
		<li class="wide">
			<div class="pl-order-detail-section pl-create-draft">
				<span><?php echo __( 'Draft is currently being created in Packlink', 'packlink-pro-shipping' ); ?></span>
			</div>
		</li>
	<?php endif; ?>
</ul>
