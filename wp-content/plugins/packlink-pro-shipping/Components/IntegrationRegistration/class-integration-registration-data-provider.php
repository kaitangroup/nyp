<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\IntegrationRegistration;

use Packlink\BusinessLogic\IntegrationRegistration\AbstractIntegrationDataProvider;

/**
 * Class Config_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Integration_Registration_Data_Provider extends AbstractIntegrationDataProvider {

	const INTEGRATION_TYPE = 'woocommerce_module';

	const DEFAULT_INTEGRATION_NAME = 'Packlink WooCommerce store';

	/**
	 * Integration_Registration_Data_Provider constructor.
	 */
	public function __construct($configService) {
		parent::__construct($configService);
	}

	/**
	 * Returns the integration type (e.g. Prestashop, WooCommerce...).
	 *
	 * @return string Integration type.
	 */
	public function getIntegrationType() {
		return self::INTEGRATION_TYPE;
	}

	/**
	 * Returns the name of the integration.
	 *
	 * @return string Integration name.
	 */
	public function getIntegrationName() {
		$name = trim( (string) get_option( 'blogname', '' ) );
		if ( $name === '' ) {
			$name = trim( (string) get_bloginfo( 'name' ) );
		}
		if ( $name === '' ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			$name = $host ?: self::DEFAULT_INTEGRATION_NAME;
		}

		return $name;
	}

	/**
	 * Returns the WebhookStatusUpdateUrl.
	 *
	 * @return string webhook URL.
	 */
	public function getIntegrationWebhookStatusUpdateUrl() {
		return $this->getConfigService()->getStatusUpdateUrl();
	}

	/**
	 * Reset AuthorizationCredentials.
	 *
	 * @return void
	 */
	public function deleteToken()
	{
		$this->getConfigService()->resetAuthorizationCredentials();
	}
}
