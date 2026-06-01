<?php

/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Controllers\ManualRefreshController;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\WooCommerce\Components\Services\System_Info_Service;
use Packlink\WooCommerce\Components\Utility\Database;

//@codingStandardsIgnoreStart

/** @noinspection SqlNoDataSourceInspection */

// ***********************************************************************************
// Method definitions ****************************************************************
// Methods used during the migration process.                                        *
// ***********************************************************************************

/**
 * Returns current shipping methods in the raw format.
 *
 * @param wpdb   $db
 * @param string $table_name
 *
 * @return array
 */
function pl_v320_get_current_shipping_methods( $db, $table_name ) {
	$query = "SELECT * FROM {$table_name} WHERE type = 'ShippingService' ";

	$results = $db->get_results( $query, ARRAY_A );

	return array_values( array_map( static function ( $item ) {
		return json_decode( $item['data'], true );
	}, $results ) );
}

/**
 * Transforms existing pricing policies for a given shipping method.
 *
 * @param array $service
 *
 * @return array
 */
function pl_v320_get_transformed_pricing_policies( array $service ) {
	$policies = array();

	if ( ! empty( $service['pricingPolicies'] ) ) {
		foreach ( $service['pricingPolicies'] as $pricing_policy ) {
			$pricing_policy['system_id'] = System_Info_Service::SYSTEM_ID;
			$policies[]                  = $pricing_policy;
		}
	}

	return $policies;
}

/**
 * Updates a shipping service.
 *
 * @param array $service
 *
 * @return array
 */
function pl_v320_update_shipping_service( array $service ) {
	$service['currency']        = 'EUR';
	$service['fixedPrices']     = null;
	$service['systemDefaults']  = null;
	$service['pricingPolicies'] = pl_v320_get_transformed_pricing_policies( $service );

	return $service;
}

// ***********************************************************************************
// STEP 1. ***************************************************************************
// Read the current shipping methods.                                                *
// ***********************************************************************************

global $wpdb;
$db = $wpdb;

$table_name   = $db->prefix . Database::BASE_TABLE;
$raw_services = pl_v320_get_current_shipping_methods( $db, $table_name );

// ***********************************************************************************
// STEP 2. ***************************************************************************
// Transform the pricing policies.                                                   *
// ***********************************************************************************

foreach ( $raw_services as $index => $service ) {
	$raw_services[ $index ] = pl_v320_update_shipping_service( $service );
}

// ***********************************************************************************
// STEP 3. ***************************************************************************
// Instantiate new shipping methods with the transformed data                        *
// ***********************************************************************************

$shipping_methods = array_map( static function ( array $raw_method ) {
	return ShippingMethod::fromArray( $raw_method );
}, $raw_services );

// ***********************************************************************************
// STEP 4. ***************************************************************************
// Save the updated shipping methods.                                                *
// ***********************************************************************************

/** @noinspection PhpUnhandledExceptionInspection */
$repository = RepositoryRegistry::getRepository( ShippingMethod::getClassName() );
foreach ( $shipping_methods as $method ) {
	$repository->save( $method );
}

// ***********************************************************************************
// STEP 5. ***************************************************************************
// Enqueue task for updating shipping services.                                      *
// ***********************************************************************************

/** @var UpdateShippingServiceTaskStatusServiceInterface $status_service */
$status_service = ServiceRegister::getService( UpdateShippingServiceTaskStatusServiceInterface::class );
/** @var UpdateShippingServicesOrchestratorInterface $orchestrator */
$orchestrator = ServiceRegister::getService( UpdateShippingServicesOrchestratorInterface::class );
$manual_refresh_controller = new ManualRefreshController( $status_service, $orchestrator );
$manual_refresh_controller->enqueueUpdateTask();

//@codingStandardsIgnoreEnd
