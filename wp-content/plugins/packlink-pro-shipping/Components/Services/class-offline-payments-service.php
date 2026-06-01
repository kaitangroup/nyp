<?php

namespace Packlink\WooCommerce\Components\Services;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController as CoreController;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;

/**
 * Class Offline_Payments_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Offline_Payments_Service extends OfflinePaymentsServices {
	/**
	 * @var CoreController $controller
	 */
	protected $controller;

	/**
	 * @var ShippingMethodController $shippingMethodController
	 */
	protected $shippingMethodController;

	/**
	 * Configuration service.
	 *
	 * @var Config_Service
	 */
	private $configuration;

	/**
	 * @var CashOnDeliveryServiceInterface
	 */
	protected $cashOnDeliveryService;

	/**
	 * Offline payment methods in WooCommerce.
	 *
	 * @var string[]
	 */
	protected $knownOffline = array(
		'cod',
		'bacs',
		'cheque',
	);

	public function __construct() {
		$this->controller = new CoreController();

		$this->shippingMethodController = new ShippingMethodController();

		$this->configuration = ServiceRegister::getService( Config_Service::CLASS_NAME );

		$this->cashOnDeliveryService = ServiceRegister::getService( CashOnDeliveryServiceInterface::CLASS_NAME );
	}

	/**
	 * Gets active offline payment methods.
	 *
	 * @return array
	 */
	public function getOfflinePayments() {
		$offlinePayments = array();

		$available = WC()->payment_gateways()->payment_gateways;

		foreach ( $available as $gateway ) {
			if ( in_array( $gateway->id, $this->knownOffline, true ) &&
			     isset( $gateway->enabled ) && $gateway->enabled === 'yes' ) {

				$offlinePayments[] = array(
					'name'        => $gateway->id,
					'displayName' => $gateway->get_title(),
				);
			}
		}

		return $offlinePayments;
	}

	/**
	 * @param ShippingService[] $services
	 *
	 * @return ShippingService|null
	 */
	public function getMatchingService( array $services ) {

		$warehouse = $this->configuration->getDefaultWarehouse();

		$from_country = $warehouse ? $warehouse->country : null;
		$to_country   = $warehouse ? $warehouse->country : null;

		if ( WC()->customer && WC()->customer->get_shipping_country() ) {
			$to_country = WC()->customer->get_shipping_country();
		}

		return $this->getCheapestMatchingService( $services, $from_country, $to_country );
	}

	/**
	 * Determines which payment methods should be shown based on chosen shipping method.
	 *
	 * @param int                 $chosenShippingMethod
	 * @param array               $availableGateways
	 * @param CashOnDelivery|null $accountConfig
	 *
	 * @return array List of payment method IDs/names to hide
	 *
	 */
	public function getAvailablePayments( $chosenShippingMethod, $availableGateways, $accountConfig ) {
		$services = $this->shippingMethodController->getShippingServicesForMethod( $chosenShippingMethod );
		$service  = $this->getMatchingService( $services );

		if ( ! $service ) {
			return $availableGateways;
		}

		$config = $service->cashOnDeliveryConfig;

		if ( $this->shouldBeHiddenPaymentMethod( $accountConfig, $config ) ) {

			$offline_payment_id = $accountConfig->account->getOfflinePaymentMethod();

			if ( isset( $availableGateways[ $offline_payment_id ] ) ) {
				unset( $availableGateways[ $offline_payment_id ] );
			}
		}

		return $availableGateways;
	}

	/**
	 * @param $paymentMethod
	 *
	 * @return bool
	 */
	public function shouldSurchargeApply( $paymentMethod ) {
		try {
			$acc = $this->controller->getCashOnDeliveryConfiguration();
		} catch ( QueryFilterInvalidParamException $e ) {
			return false;
		}

		return $this->surchargeCondition( $acc, $paymentMethod );
	}

	/**
	 * Calculate COD surcharge fee based on Packlink rules.
	 *
	 * @param $shippingMethodId
	 * @param $amount
	 *
	 * @return float COD surcharge
	 * @throws QueryFilterInvalidParamException
	 */
	public function calculateFee( $shippingMethodId, $amount ) {
		try {
			$cod = $this->controller->getCashOnDeliveryConfiguration();
		} catch ( QueryFilterInvalidParamException $e ) {
			$cod = null;
		}

		if ( $cod && $cod->account && $cod->account->getCashOnDeliveryFee() !== null) {
			return $cod->account->getCashOnDeliveryFee();
		}

		$services = $this->shippingMethodController->getShippingServicesForMethod( $shippingMethodId );
		$service  = $this->getMatchingService( $services );

		if ( $service && $service->cashOnDeliveryConfig ) {
			return $this->cashOnDeliveryService->calculateFee( $amount,
				$service->cashOnDeliveryConfig->applyPercentageCashOnDelivery,
				$service->cashOnDeliveryConfig->maxCashOnDelivery );
		}

		return null;
	}

    /**
     * Retrieves Packlink account configuration and checks if an account exists.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getAccountConfiguration()
    {
        return $this->controller->getCashOnDeliveryConfiguration();
    }
}