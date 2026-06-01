<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\Logger\LogData;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Scheduler\Models\Schedule;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecutor\Interfaces\TaskStatusProviderInterface;
use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\CashOnDelivery\Model\CashOnDelivery;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\FileResolver\FileResolverService;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\ShipmentDraft\Interfaces\ShipmentDraftServiceInterface;
use Packlink\BusinessLogic\Tasks\Interfaces\TaskMetadataProviderInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;
use Packlink\BusinessLogic\UpdateShippingServices\UpdateShippingServiceTaskStatusService;
use Packlink\BusinessLogic\Scheduler\Interfaces\SchedulerInterface;
use Packlink\WooCommerce\Components\IntegrationRegistration\Integration_Registration_Data_Provider;
use Packlink\WooCommerce\Components\IntegrationRegistration\Integration_Reset_Service;
use Packlink\WooCommerce\Components\Services\Offline_Payments_Service;
use Packlink\WooCommerce\Components\Services\Packlink_WordPress_Scheduler;
use Packlink\WooCommerce\Components\Services\Order_Service;
use Packlink\WooCommerce\Components\Services\Shipment_Draft_Service;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService;
use Packlink\WooCommerce\Components\Order\Order_Drop_Off_Map;
use Packlink\WooCommerce\Components\Order\Shop_Order_Service;
use Packlink\WooCommerce\Components\Repositories\Base_Repository;
use Packlink\WooCommerce\Components\Services\Config_Service;
use Packlink\WooCommerce\Components\Services\Logger_Service;
use Packlink\WooCommerce\Components\Services\Registration_Info_Service;
use Packlink\WooCommerce\Components\Services\System_Info_Service;
use Packlink\WooCommerce\Components\Services\Warehouse_Country_Service;
use Packlink\WooCommerce\Components\Services\WordPress_Task_Executor;
use Packlink\WooCommerce\Components\Services\WordPress_Task_Metadata_Provider;
use Packlink\WooCommerce\Components\Services\WordPress_Task_Status_Provider;
use Packlink\WooCommerce\Components\ShippingMethod\Shipping_Method_Map;
use Packlink\WooCommerce\Components\ShippingMethod\Shop_Shipping_Method_Service;

/**
 * Class Bootstrap_Component
 *
 * @package Packlink\WooCommerce\Components
 */
class Bootstrap_Component extends BootstrapComponent {
	/**
	 * Initializes services and utilities.
	 */
	protected static function initServices() {

		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			static function () {
				return Config_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			IntegrationRegistrationDataProviderInterface::CLASS_NAME,
			static function () {
				return new Integration_Registration_Data_Provider(
					ServiceRegister::getService( \Packlink\BusinessLogic\Configuration::CLASS_NAME)
				);
			}
		);

		ServiceRegister::registerService(
			ModuleResetServiceInterface::CLASS_NAME,
			function () {
				return new Integration_Reset_Service(
					ServiceRegister::getService(IntegrationRegistrationDataProviderInterface::CLASS_NAME)
				);
			}
		);

		parent::initServices();

		ServiceRegister::registerService(
			OrderService::CLASS_NAME,
			function () {
				return Order_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			Serializer::CLASS_NAME,
			function () {
				return new NativeSerializer();
			}
		);

		ServiceRegister::registerService(
			TaskMetadataProviderInterface::CLASS_NAME,
			static function () {
				return new WordPress_Task_Metadata_Provider();
			}
		);

		ServiceRegister::registerService(
			BrandConfigurationService::CLASS_NAME,
			static function () {
				return new PacklinkConfigurationService();
			}
		);

		ServiceRegister::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				return Logger_Service::getInstance();
			}
		);

        ServiceRegister::registerService(
            OfflinePaymentsServices::CLASS_NAME,
            static function () {
                return new Offline_Payments_Service;
            }
        );

		ServiceRegister::registerService(
			ShopShippingMethodService::CLASS_NAME,
			static function () {
				return Shop_Shipping_Method_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			ShopOrderService::CLASS_NAME,
			static function () {
				return Shop_Order_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			HttpClient::CLASS_NAME,
			static function () {
				return new CurlHttpClient();
			}
		);

		ServiceRegister::registerService(
			RegistrationInfoService::CLASS_NAME,
			static function () {
				return new Registration_Info_Service();
			}
		);

		ServiceRegister::registerService(
			SystemInfoService::CLASS_NAME,
			static function () {
				return new System_Info_Service();
			}
		);

		ServiceRegister::registerService(
			FileResolverService::CLASS_NAME,
			function () {
				return new FileResolverService(array(
					__DIR__ . '/../resources/packlink/brand/countries',
					__DIR__ . '/../resources/packlink/countries',
					__DIR__ . '/../resources/countries',
				));
			}
		);

		ServiceRegister::registerService(
			ShipmentDraftServiceInterface::CLASS_NAME,
			static function () {
				/** @var TaskExecutorInterface $taskExecutor */
				$taskExecutor = ServiceRegister::getService( TaskExecutorInterface::CLASS_NAME );

				return new Shipment_Draft_Service( $taskExecutor );
			}
		);

		ServiceRegister::registerService(
			WarehouseCountryService::CLASS_NAME,
			static function () {
				return Warehouse_Country_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			UpdateShippingServiceTaskStatusServiceInterface::class,
			function () {
				/** @var RepositoryInterface $repository */
				$repository = RepositoryRegistry::getRepository(UpdateShippingServiceTaskStatus::CLASS_NAME);

				return new UpdateShippingServiceTaskStatusService($repository);
			}
		);

		ServiceRegister::registerService(
			TaskExecutorInterface::CLASS_NAME,
			function () {
				return new WordPress_Task_Executor();
			}
		);

		ServiceRegister::registerService(
			TaskStatusProviderInterface::CLASS_NAME,
			function () {
				return new WordPress_Task_Status_Provider();
			}
		);

		ServiceRegister::registerService(
			SchedulerInterface::CLASS_NAME,
			function () {
				return new Packlink_WordPress_Scheduler();
			}
		);
	}

	/**
	 * Initializes repositories.
	 *
	 * @throws RepositoryClassException If repository class is not instance of repository interface.
	 */
	protected static function initRepositories() {
		parent::initRepositories();

		RepositoryRegistry::registerRepository( ConfigEntity::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( ShippingMethod::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Shipping_Method_Map::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( OrderShipmentDetails::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Schedule::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( LogData::CLASS_NAME, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Order_Drop_Off_Map::CLASS_NAME, Base_Repository::getClassName() );
        RepositoryRegistry::registerRepository(CashOnDelivery::getClassName(), Base_Repository::getClassName());
		RepositoryRegistry::registerRepository( UpdateShippingServiceTaskStatus::CLASS_NAME,
			Base_Repository::getClassName()
		);
	}
}
