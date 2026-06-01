<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Http\DTO\CashOnDelivery;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController as CoreController;
use Packlink\WooCommerce\Components\Services\Offline_Payments_Service;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Cash_On_Delivery_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Cash_On_Delivery_Controller extends Packlink_Base_Controller
{

    /**
     * @var CoreController $controller
     */
    protected $controller;

    /**
     * @var ShippingMethodController $shippingMethodController
     */
    protected $shippingMethodController;

    /**
     * @var Offline_Payments_Service
     */
    private $offline_payments_service;

    public function __construct()
    {
        $this->controller = new CoreController();

        $this->offline_payments_service = ServiceRegister::getService(
            OfflinePaymentsServices::CLASS_NAME);

        $this->shippingMethodController = new ShippingMethodController();
    }

	/**
	 * Saving configuration data
	 *
	 * @return void
	 */
    public function save_data()
    {
        $this->validate('yes', true);
        $raw = $this->get_raw_input();

        try {
            $payload = json_decode($raw, true);
            $result = $this->controller->saveConfig($payload);

            if (!$result) {
                $this->return_json(array('success' => false));
            }

            $this->return_json(array('success' => true));

        } catch (\Exception $exception) {
            $this->return_json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    /**
     * Getting Cash on Delivery configuration data
     *
     * @throws QueryFilterInvalidParamException
     */
    public function get_data()
    {
        $configuration = $this->get_account_configuration();
        $configArray = array();

        if ($configuration !== null) {
            $configArray = $configuration->toArray();
        }

        $this->return_json(['paymentMethods' => $this->offline_payments_service->getOfflinePayments(),
            'configuration' => $configArray,
        ]);
    }

    /**
     * Determines which payment methods should be shown based on chosen shipping method.
     *
     * @param int $chosen_shipping_method
     * @param array $available_gateways
     *
     * @return array List of payment method IDs/names to hide
     *
     * @throws QueryFilterInvalidParamException
     */
    public function get_available_payments($chosen_shipping_method, $available_gateways)
    {

        $accountConfig = $this->get_account_configuration();

        return $this->offline_payments_service->getAvailablePayments(
            $chosen_shipping_method, $available_gateways, $accountConfig);
    }

    /**
     * Retrieves Packlink account configuration and checks if an account exists.
     *
     * @return CashOnDelivery|null
     *
     * @throws QueryFilterInvalidParamException
     */
    private function get_account_configuration()
    {
        return $this->controller->getCashOnDeliveryConfiguration();
    }
}