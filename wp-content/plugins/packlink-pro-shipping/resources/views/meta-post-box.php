<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
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
 * @var bool 				 $integration_active
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
				<a href="<?php echo $order_details->getShipmentUrl(); ?>" target="_blank">
					<button type="button" class="button pl-button-view" name="view on packlink" value="View">
						<?php echo __( 'View on Packlink PRO', 'packlink-pro-shipping' ); ?>
					</button>
				</a>

				<?php if ( $order_details->getShipmentLabels() ) : ?>
					<a href="<?php echo $order_details->getShipmentLabels()[0]->getLink(); ?>" target="_blank">
						<button type="button" class="button button-primary" name="print label" value="Print">
							<?php echo __( 'Print label', 'packlink-pro-shipping' ); ?>
						</button>
					</a>
				<?php endif; ?>
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
