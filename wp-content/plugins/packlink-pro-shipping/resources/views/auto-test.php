<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\WooCommerce\Controllers\Packlink_Auto_Test_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page controller.
 *
 * @var Packlink_Auto_Test_Controller $this
 */
$data = $this->resolve_view_arguments();

// @codingStandardsIgnoreStart
?>

<div class="container-fluid pl-main-wrapper" id="pl-main-page-holder">
	<div class="pl-logo-wrapper">
		<img src="<?php echo $data['dashboard_logo']; ?>" class="pl-dashboard-logo"
			alt="<?php echo __( 'Packlink PRO Shipping', 'packlink-pro-shipping' ); ?>">
	</div>
	<div class="pl-auto-test-panel">
		<div class="pl-auto-test-header">
			<div class="pl-auto-test-title">
				<?php echo __( 'PacklinkPRO module auto-test', 'packlink-pro-shipping' ); ?>
			</div>
			<div class="pl-auto-test-subtitle">
				<?php echo __( 'Use this page to test the system configuration and PacklinkPRO module services.', 'packlink-pro-shipping' ); ?>
			</div>
		</div>

		<div class="pl-auto-test-content col-10" id="pl-auto-test-progress">
			<button type="button" name="start-test" id="pl-auto-test-start"
					class="button button-primary btn-lg"><?php echo __( 'Start', 'packlink-pro-shipping' ); ?></button>
			<div class="pl-auto-test-log-panel" id="pl-auto-test-log-panel">
				...
			</div>
		</div>
		<div class="pl-auto-test-content" id="pl-spinner-box">
			<div class="pl-spinner" id="pl-spinner">
				<div></div>
			</div>
		</div>

		<div class="pl-auto-test-content" id="pl-auto-test-done">
			<div class="pl-auto-test-content col-10">
				<div class="pl-flash-msg success" id="pl-flash-message-success">
					<div class="pl-flash-msg-text-section">
						<i class="material-icons success">
							check_circle
						</i>
						<span id="pl-flash-message-text">
							<?php echo __( 'Auto-test passed successfully!', 'packlink-pro-shipping' ); ?>
						</span>
					</div>
				</div>
				<div class="pl-flash-msg danger" id="pl-flash-message-fail">
					<div class="pl-flash-msg-text-section">
						<i class="material-icons danger">
							error
						</i>
						<span id="pl-flash-message-text">
							<?php echo __( 'The test did not complete successfully.', 'packlink-pro-shipping' ); ?>
						</span>
					</div>
				</div>
			</div>

			<a href="<?php echo $data['download_log_url']; ?>" value="auto-test-log.json" download>
				<button type="button" name="download-log"
						class="button btn-info btn-lg"><?php echo __( 'Download test log', 'packlink-pro-shipping' ); ?></button>
			</a>
			<a href="<?php echo $data['debug_url']; ?>" value="packlink-debug-data.zip" download>
				<button type="button" name="download-system-info-file"
						class="button btn-info btn-lg"><?php echo __( 'Download system info file', 'packlink-pro-shipping' ); ?></button>
			</a>
			<a href="<?php echo $data['module_url']; ?>">
				<button type="button" name="open-module" class="button btn-success btn-lg">
					<?php echo __( 'Open PacklinkPRO module', 'packlink-pro-shipping' ); ?>
				</button>
			</a>

		</div>
	</div>
</div>

<script type="application/javascript">
	document.addEventListener('DOMContentLoaded', function () {
		Packlink.AutoTestController("<?php echo $data['start_test_url']; ?>", "<?php echo $data['check_status_url']; ?>");
	}, false);
</script>
