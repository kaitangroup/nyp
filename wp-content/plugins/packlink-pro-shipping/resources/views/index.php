<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\WooCommerce\Components\Services\System_Info_Service;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = $this->resolve_view_arguments();

?>

<div id="pl-page">
	<header id="pl-main-header">
		<div class="pl-main-logo" style="width: 200px">
			<img src="https://cdn.packlink.com/apps/giger/logos/packlink-pro.svg" alt="logo">
		</div>
		<div class="pl-header-holder" id="pl-header-section"></div>
	</header>

	<main id="pl-main-page-holder"></main>

	<div class="pl-spinner pl-hidden" id="pl-spinner">
		<div></div>
	</div>

	<template id="pl-alert">
		<div class="pl-alert-wrapper">
			<div class="pl-alert">
				<span class="pl-alert-text"></span>
				<i class="material-icons">close</i>
			</div>
		</div>
	</template>

	<template id="pl-modal">
		<div id="pl-modal-mask" class="pl-modal-mask pl-hidden">
			<div class="pl-modal">
				<div class="pl-modal-close-button">
					<i class="material-icons">close</i>
				</div>
				<div class="pl-modal-title">

				</div>
				<div class="pl-modal-body">

				</div>
				<div class="pl-modal-footer">
				</div>
			</div>
		</div>
	</template>

	<template id="pl-error-template">
		<div class="pl-error-message" data-pl-element="error">
		</div>
	</template>
</div>

<script>
	document.addEventListener(
		'DOMContentLoaded',
		() => {
			hideNotifications();

			Packlink.translations = {
				default: <?php echo $data['lang']['default']; //phpcs:ignore ?>,
				current: <?php echo $data['lang']['current']; //phpcs:ignore ?>
			};

			const pageConfiguration = {
				'login': {
					submit: "<?php echo Shop_Helper::get_controller_url( 'Login', 'login' ); //phpcs:ignore ?>",
					listOfCountriesUrl: "<?php echo Shop_Helper::get_controller_url( 'Regions', 'get_regions' ); //phpcs:ignore ?>",
					logoPath: "" // Not used. Logos are retrieved based on the base resource url.
				},
				'register': {
					getRegistrationData: "<?php echo Shop_Helper::get_controller_url( 'Register', 'get' ); //phpcs:ignore ?>",
					submit: "<?php echo Shop_Helper::get_controller_url( 'Register', 'submit' ); //phpcs:ignore ?>"
				},
				'onboarding-state': {
					getState: "<?php echo Shop_Helper::get_controller_url( 'Onboarding_State', 'get_current_state' ); //phpcs:ignore ?>"
				},
				'onboarding-welcome': {},
				'onboarding-overview': {
					defaultParcelGet: "<?php echo Shop_Helper::get_controller_url( 'Parcel', 'get' ); //phpcs:ignore ?>",
					defaultWarehouseGet: "<?php echo Shop_Helper::get_controller_url( 'Warehouse', 'get' ); //phpcs:ignore ?>"
				},
				'default-parcel': {
					getUrl: "<?php echo Shop_Helper::get_controller_url( 'Parcel', 'get' ); //phpcs:ignore ?>",
					submitUrl: "<?php echo Shop_Helper::get_controller_url( 'Parcel', 'submit' ); //phpcs:ignore ?>"
				},
				'default-warehouse': {
					getUrl: "<?php echo Shop_Helper::get_controller_url( 'Warehouse', 'get' ); //phpcs:ignore ?>",
					getSupportedCountriesUrl: "<?php echo Shop_Helper::get_controller_url( 'Warehouse', 'get_countries' ); //phpcs:ignore ?>",
					submitUrl: "<?php echo Shop_Helper::get_controller_url( 'Warehouse', 'submit' ); //phpcs:ignore ?>",
					searchPostalCodesUrl: "<?php echo Shop_Helper::get_controller_url( 'Warehouse', 'search_postal_codes' ); //phpcs:ignore ?>"
				},
                'cash-on-delivery': {
                    getDataUrl: "<?php echo Shop_Helper::get_controller_url( 'Cash_On_Delivery', 'get_data' ); //phpcs:ignore ?>",
                    submitDataUrl: "<?php echo Shop_Helper::get_controller_url( 'Cash_On_Delivery', 'save_data' );//phpcs:ignore ?>",
                },
				'manual-sync': {
					getUrl: "<?php echo Shop_Helper::get_controller_url( 'Manual_Sync', 'is_manual_sync_enabled' ); //phpcs:ignore ?>",
					submitUrl: "<?php echo Shop_Helper::get_controller_url( 'Manual_Sync', 'set_manual_sync_enabled' ); //phpcs:ignore ?>",
				},
				'configuration': {
					getDataUrl: "<?php echo Shop_Helper::get_controller_url( 'Configuration', 'get' ); //phpcs:ignore ?>"
				},
				'system-info': {
					getStatusUrl: "<?php echo Shop_Helper::get_controller_url( 'Debug', 'get_status' ); //phpcs:ignore ?>",
					setStatusUrl: "<?php echo Shop_Helper::get_controller_url( 'Debug', 'set_status' ); //phpcs:ignore ?>"
				},
				'order-status-mapping': {
					getMappingAndStatusesUrl: "<?php echo Shop_Helper::get_controller_url( 'Order_Status', 'get' ); //phpcs:ignore ?>",
					setUrl: "<?php echo Shop_Helper::get_controller_url( 'Order_Status', 'submit' ); //phpcs:ignore ?>"
				},
				'my-shipping-services': {
					getServicesUrl: "<?php echo Shop_Helper::get_controller_url( 'My_Shipping_Services', 'get' ); //phpcs:ignore ?>",
					deleteServiceUrl: "<?php echo Shop_Helper::get_controller_url( 'My_Shipping_Services', 'deactivate' ); //phpcs:ignore ?>",
					getCurrencyDetailsUrl: "<?php echo Shop_Helper::get_controller_url( 'System_Info', 'get' ); //phpcs:ignore ?>",
					systemId: "<?php echo System_Info_Service::SYSTEM_ID; ?>"
				},
				'pick-shipping-service': {
					getActiveServicesUrl: "<?php echo Shop_Helper::get_controller_url( 'Shipping_Service', 'get_active' ); //phpcs:ignore ?>",
					getServicesUrl: "<?php echo Shop_Helper::get_controller_url( 'Shipping_Service', 'get' ); //phpcs:ignore ?>",
					getTaskStatusUrl: "<?php echo Shop_Helper::get_controller_url( 'Shipping_Service', 'get_task_status' ); //phpcs:ignore ?>",
					startAutoConfigureUrl: "<?php echo Shop_Helper::get_controller_url( 'Auto_Configure', 'start' ); //phpcs:ignore ?>",
					disableCarriersUrl: "<?php echo Shop_Helper::get_controller_url( 'Shop_Shipping_Methods', 'disable_shop_shipping_methods' ); //phpcs:ignore ?>",
					getCurrencyDetailsUrl: "<?php echo Shop_Helper::get_controller_url( 'System_Info', 'get' ); //phpcs:ignore ?>",
					systemId: "<?php echo System_Info_Service::SYSTEM_ID; ?>",
					enqueue: "<?php echo Shop_Helper::get_controller_url('Manual_Refresh_Service', 'refresh'); //phpcs:ignore ?>",
					getTaskStatus: "<?php echo Shop_Helper::get_controller_url('Manual_Refresh_Service', 'get_task_status'); //phpcs:ignore ?>"
				},
				'edit-service': {
					getServiceUrl: "<?php echo Shop_Helper::get_controller_url( 'Edit_Service', 'get_service' ); //phpcs:ignore ?>",
					saveServiceUrl: "<?php echo Shop_Helper::get_controller_url( 'Edit_Service', 'update_service' ); //phpcs:ignore ?>",
					getTaxClassesUrl: "",
					getCountriesListUrl: "<?php echo Shop_Helper::get_controller_url( 'Shipping_Zones', 'get_shipping_zones' ); //phpcs:ignore ?>",
					getCurrencyDetailsUrl: "<?php echo Shop_Helper::get_controller_url( 'System_Info', 'get' ); //phpcs:ignore ?>",
					hasTaxConfiguration: false,
					hasCountryConfiguration: true,
					canDisplayCarrierLogos: true
				}
			};

			Packlink.state = new Packlink.StateController(
				{
					baseResourcesUrl: "<?php echo Shop_Helper::get_plugin_base_url() . '/resources/packlink/'; //phpcs:ignore ?>",
					stateUrl: "<?php echo Shop_Helper::get_controller_url( 'Module_State', 'get_state' ); //phpcs:ignore ?>",
					integrationStatusUrl: "<?php echo Shop_Helper::get_controller_url( 'Integration_Status', 'get_status' ); //phpcs:ignore ?>",
					platformDomain: "<?php echo $data['platformDomain']; //phpcs:ignore ?>",
					pageConfiguration: pageConfiguration,
					templates: {
						'pl-login-page': {
							'pl-main-page-holder': <?php echo $data['templates']['login']; //phpcs:ignore ?>
						},
						'pl-register-page': {
							'pl-main-page-holder': <?php echo $data['templates']['register']; //phpcs:ignore ?>
						},
						'pl-register-modal': <?php echo $data['templates']['register-modal']; //phpcs:ignore ?>
						,
						'pl-onboarding-welcome-page': {
							'pl-main-page-holder': <?php echo $data['templates']['onboarding-welcome']; //phpcs:ignore ?>
						},
						'pl-onboarding-overview-page': {
							'pl-main-page-holder': <?php echo $data['templates']['onboarding-overview']; //phpcs:ignore ?>
						},
						'pl-default-parcel-page': {
							'pl-main-page-holder': <?php echo $data['templates']['default-parcel']; //phpcs:ignore ?>
						},
						'pl-default-warehouse-page': {
							'pl-main-page-holder': <?php echo $data['templates']['default-warehouse']; //phpcs:ignore ?>
						},
						'pl-manual-sync-page': {
							'pl-main-page-holder': <?php echo $data['templates']['manual-sync']; //phpcs:ignore ?>
						},
						'pl-configuration-page': {
							'pl-main-page-holder': <?php echo $data['templates']['configuration']; //phpcs:ignore ?>,
							'pl-header-section': ''
						},
						'pl-order-status-mapping-page': {
							'pl-main-page-holder': <?php echo $data['templates']['order-status-mapping']; //phpcs:ignore ?>,
							'pl-header-section': ''
						},
						'pl-system-info-modal': <?php echo $data['templates']['system-info-modal']; //phpcs:ignore ?>,
						'pl-my-shipping-services-page': {
							'pl-main-page-holder': <?php echo $data['templates']['my-shipping-services']; //phpcs:ignore ?>,
							'pl-header-section': <?php echo $data['templates']['shipping-services-header']; //phpcs:ignore ?>,
							'pl-shipping-services-table': <?php echo $data['templates']['shipping-services-table']; //phpcs:ignore ?>,
							'pl-shipping-services-list': <?php echo $data['templates']['shipping-services-list']; //phpcs:ignore ?>
						},
						'pl-disable-carriers-modal': <?php echo $data['templates']['disable-carriers-modal']; //phpcs:ignore ?>,
						'pl-pick-service-page': {
							'pl-header-section': '',
							'pl-main-page-holder': <?php echo $data['templates']['pick-shipping-services']; //phpcs:ignore ?>,
							'pl-shipping-services-table': <?php echo $data['templates']['shipping-services-table']; //phpcs:ignore ?>,
							'pl-shipping-services-list': <?php echo $data['templates']['shipping-services-list']; //phpcs:ignore ?>
						},
						'pl-edit-service-page': {
							'pl-header-section': '',
							'pl-main-page-holder': <?php echo $data['templates']['edit-shipping-service']; //phpcs:ignore ?>,
							'pl-pricing-policies': <?php echo $data['templates']['pricing-policies-list']; //phpcs:ignore ?>
						},
						'pl-pricing-policy-modal': <?php echo $data['templates']['pricing-policy-modal']; //phpcs:ignore ?>,
						'pl-countries-selection-modal': <?php echo $data['templates']['countries-selection-modal']; //phpcs:ignore ?>,
                        'pl-cod-page': {
                            'pl-main-page-holder': <?php echo $data['templates']['cash-on-delivery']; //phpcs:ignore ?>
                        },
					}
				}
			);

			Packlink.state.display();

			calculateContentHeight(5);

			/**
			 * Calculates content height.
			 *
			 * Footer can be dynamically hidden or displayed by WooCommerce,
			 * so we have to periodically recalculate content height.
			 *
			 * @param {number} offset
			 */
			function calculateContentHeight(offset) {
				if (typeof offset === 'undefined') {
					offset = 0;
				}

				let content = document.getElementById('pl-page');
				let localOffset = offset + content.offsetTop + 20;

				localOffset += <?php echo Shop_Helper::get_footer_height(); ?>;

				content.style.height = `calc(100% - ${localOffset}px`;

				setTimeout(calculateContentHeight, 250, offset);
			}

			/**
			 * Hides notifications on the Packlink configuration page.
			 */
			function hideNotifications() {
				let notices = document.querySelectorAll('.notice');
				for (let element of notices) {
					element.remove();
				}

				let updates = document.querySelectorAll('.updated');
				for (let element of updates) {
					element.remove();
				}
			}
		},
		false
	);
</script>
