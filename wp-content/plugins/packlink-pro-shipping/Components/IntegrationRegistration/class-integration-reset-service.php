<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\IntegrationRegistration;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;

/**
 * Class Config_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Integration_Reset_Service implements ModuleResetServiceInterface {

	/** @var IntegrationRegistrationDataProviderInterface */
	private $dataProvider;

	/**
	 * Integration_Reset_Service constructor.
	 */
	public function __construct($dataProvider) {
		$this->dataProvider = $dataProvider;
	}

	public function resetModule() {

		try {
			$this->dataProvider->deleteIntegrationData();
			$this->dataProvider->deleteToken();

			return true;
		} catch (\Exception $e) {
			Logger::logError(
				'Failed to reset module integration data: ' . $e->getMessage(),
				'Woocommerce_module',
				array('trace' => $e->getTraceAsString())
			);

			return false;
		}
	}
}
