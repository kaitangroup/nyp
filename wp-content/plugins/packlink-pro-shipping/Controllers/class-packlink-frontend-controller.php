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

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\WooCommerce\Components\Utility\Script_Loader;
use Packlink\WooCommerce\Components\Utility\Shop_Helper;

/**
 * Class Packlink_Frontend_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Frontend_Controller extends Packlink_Base_Controller {

	/** @var Configuration */
	private $configService;

	/**
	 * Packlink_Frontend_Controller constructor.
	 */
	public function __construct($configService) {
		$this->configService = $configService;
	}

	/**
	 * Renders appropriate view.
	 */
	public function render() {

		$this->load_static_content();

		include dirname( __DIR__ ) . '/resources/views/index.php';
	}

	/**
	 * Resolves dashboard view arguments.
	 *
	 * @return array Dashboard view arguments.
	 */
	protected function resolve_view_arguments() {
		$userInfo = $this->configService->getUserInfo();
		$platformDomain = 'com';

		if ($userInfo && !empty($userInfo->country)) {
			$country = strtolower($userInfo->country);

			if ($country !== '' && $country !== 'un') {
				$platformDomain = $country;
			}
		}

		return array(
			'lang'      => $this->get_lang(),
			'templates' => $this->get_templates(),
			'urls'      => $this->get_urls(),
			'platformDomain' => $platformDomain,
		);
	}


	/**
	 * Loads JS and CSS files for the current page.
	 */
	private function load_static_content() {
		wp_enqueue_style( 'material', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined' ); // phpcs:ignore

		Script_Loader::load_css(
			array(
				'packlink/css/app.css',
				'css/packlink-wp-override.css',
				'css/packlink-core-override.css',
			)
		);

		Script_Loader::load_js(
			array(
				'packlink/js/StateUUIDService.js',
				'packlink/js/AjaxService.js',
				'packlink/js/UtilityService.js',
				'packlink/js/TemplateService.js',
				'packlink/js/TranslationService.js',
				'packlink/js/ValidationService.js',
				'packlink/js/GridResizerService.js',
				'packlink/js/ShippingServicesRenderer.js',
				'packlink/js/AutoTestController.js',
				'js/override/ConfigurationController.js',
				'packlink/js/DefaultParcelController.js',
				'packlink/js/DefaultWarehouseController.js',
				'packlink/js/EditServiceController.js',
				'packlink/js/SingleStorePricePolicyController.js',
				'packlink/js/LoginController.js',
				'packlink/js/ModalService.js',
				'packlink/js/MyShippingServicesController.js',
				'packlink/js/OnboardingOverviewController.js',
				'packlink/js/OnboardingStateController.js',
				'packlink/js/OnboardingWelcomeController.js',
				'packlink/js/OrderStatusMappingController.js',
				'packlink/js/PageControllerFactory.js',
				'packlink/js/PickShippingServiceController.js',
				'packlink/js/PricePolicyController.js',
				'packlink/js/RegisterController.js',
				'packlink/js/RegisterModalController.js',
				'packlink/js/ResponseService.js',
				'packlink/js/ServiceCountriesModalController.js',
				'packlink/js/StateController.js',
				'packlink/js/SystemInfoController.js',
				'packlink/js/SettingsButtonService.js',
				'js/custom/ManualSyncController.js',
                'packlink/js/CashOnDeliveryController.js',
			)
		);
	}

	/**
	 * Retrieves current language.
	 *
	 * @return array
	 */
	private function get_lang() {
		$locale = Shop_Helper::get_user_locale();

		/** @var CountryService $country_service */
		$country_service = ServiceRegister::getService( CountryService::class );
		$default         = $country_service->getAllLabels( 'en' );
		$current_lang    = $country_service->getAllLabels( $locale );

		return array(
			'default' => json_encode($default['en']),
			'current' => json_encode($current_lang[$locale]),
		);
	}

	/**
	 * Retrieves templates.
	 *
	 * @return array
	 */
	private function get_templates() {
		$base_dir = __DIR__ . '/../resources/packlink/templates/';
		$override_dir = __DIR__ . '/../resources/templates/override/';
		$custom_dir = __DIR__ . '/../resources/templates/custom/';

		//@codingStandardsIgnoreStart
		return array(
			'configuration'             => json_encode( file_get_contents( $override_dir . 'configuration.html' ) ),
			'countries-selection-modal' => json_encode( file_get_contents( $base_dir . 'countries-selection-modal.html' ) ),
			'default-parcel'            => json_encode( file_get_contents( $base_dir . 'default-parcel.html' ) ),
			'default-warehouse'         => json_encode( file_get_contents( $base_dir . 'default-warehouse.html' ) ),
			'disable-carriers-modal'    => json_encode( file_get_contents( $base_dir . 'disable-carriers-modal.html' ) ),
			'edit-shipping-service'     => json_encode( file_get_contents( $base_dir . 'edit-shipping-service.html' ) ),
			'login'                     => json_encode( file_get_contents( $base_dir . 'login.html' ) ),
			'my-shipping-services'      => json_encode( file_get_contents( $base_dir . 'my-shipping-services.html' ) ),
			'onboarding-overview'       => json_encode( file_get_contents( $base_dir . 'onboarding-overview.html' ) ),
			'onboarding-welcome'        => json_encode( file_get_contents( $base_dir . 'onboarding-welcome.html' ) ),
			'order-status-mapping'      => json_encode( file_get_contents( $base_dir . 'order-status-mapping.html' ) ),
			'pick-shipping-services'    => json_encode( file_get_contents( $base_dir . 'pick-shipping-services.html' ) ),
			'pricing-policies-list'     => json_encode( file_get_contents( $base_dir . 'pricing-policies-list.html' ) ),
			'pricing-policy-modal'      => json_encode( file_get_contents( $base_dir . 'pricing-policy-modal.html' ) ),
			'register'                  => json_encode( file_get_contents( $base_dir . 'register.html' ) ),
			'register-modal'            => json_encode( file_get_contents( $base_dir . 'register-modal.html' ) ),
			'shipping-services-header'  => json_encode( file_get_contents( $base_dir . 'shipping-services-header.html' ) ),
			'shipping-services-list'    => json_encode( file_get_contents( $base_dir . 'shipping-services-list.html' ) ),
			'shipping-services-table'   => json_encode( file_get_contents( $base_dir . 'shipping-services-table.html' ) ),
			'system-info-modal'         => json_encode( file_get_contents( $base_dir . 'system-info-modal.html' ) ),
			'manual-sync'               => json_encode( file_get_contents( $custom_dir . 'manual-sync.html' ) ),
			'location-picker'           => json_encode( file_get_contents( $custom_dir . 'location-picker.html' ) ),
            'cash-on-delivery'          => json_encode( file_get_contents( $base_dir . 'cash-on-delivery.html' ) ),
        );
		//@codingStandardsIgnoreEnd
	}

	/**
	 * Retrieves urls.
	 *
	 * @return array
	 */
	private function get_urls() {
		return array();
	}
}
