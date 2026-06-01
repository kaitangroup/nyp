<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

$url = wp_nonce_url( add_query_arg( 'packlink-hide-success-notice', '1' ), 'packlink_hide_success_notices_nonce', '_packlink_success_notice_nonce' )

?>
<div class="notice notice-success" style="position: relative;">
	<p><strong><?php esc_html_e( 'Packlink PRO Shipping', 'packlink-pro-shipping' ); ?>:</strong>
		<?php echo get_transient( 'packlink-pro-success-messages' ); // phpcs:ignore ?>
	</p>
	<a style="text-decoration: none;display:flex;" class="notice-dismiss" href="<?php echo esc_url( $url ); ?>">
		<?php esc_html_e( 'Dismiss' ); ?>
	</a>
</div>
