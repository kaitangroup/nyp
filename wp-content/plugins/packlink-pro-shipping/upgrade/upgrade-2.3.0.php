<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingPricePolicy;
use Packlink\WooCommerce\Components\Utility\Database;

//@codingStandardsIgnoreStart

/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlResolve */

// ***********************************************************************************
// Method definitions ****************************************************************
// Methods used during the migration process.                                        *
// ***********************************************************************************

function get_current_shipping_methods( $db, $table_name ) {
	$query = "SELECT * FROM {$table_name} WHERE type = 'ShippingService' ";

	$results = $db->get_results( $query, ARRAY_A );

	return array_values( array_map( static function ( $item ) {
		return json_decode( $item['data'], true );
	}, $results ) );
}

function get_transformed_pricing_policy( array $method ) {
	$result = array();

	switch ( $method['pricingPolicy'] ) {
		case 1:
			// Packlink prices.
			break;
		case 2:
			// Percent prices.
			$pricing_policy                = new ShippingPricePolicy();
			$pricing_policy->rangeType     = ShippingPricePolicy::RANGE_PRICE_AND_WEIGHT;
			$pricing_policy->fromWeight    = 0;
			$pricing_policy->fromPrice     = 0;
			$pricing_policy->pricingPolicy = ShippingPricePolicy::POLICY_PACKLINK_ADJUST;
			$pricing_policy->increase      = $method['percentPricePolicy']['increase'];
			$pricing_policy->changePercent = $method['percentPricePolicy']['amount'];
			$result[]                      = $pricing_policy->toArray();
			break;
		case 3:
			// Fixed price by weight.
			foreach ( $method['fixedPriceByWeightPolicy'] as $policy ) {
				$pricing_policy                = new ShippingPricePolicy();
				$pricing_policy->rangeType     = ShippingPricePolicy::RANGE_WEIGHT;
				$pricing_policy->fromWeight    = $policy['from'];
				$pricing_policy->toWeight      = !empty( $policy['to'] ) ? $policy['to'] : null;
				$pricing_policy->pricingPolicy = ShippingPricePolicy::POLICY_FIXED_PRICE;
				$pricing_policy->fixedPrice    = $policy['amount'];
				$result[]                      = $pricing_policy->toArray();
			}
			break;
		case 4:
			// Fixed price by price.
			foreach ( $method['fixedPriceByValuePolicy'] as $policy ) {
				$pricing_policy                = new ShippingPricePolicy();
				$pricing_policy->rangeType     = ShippingPricePolicy::RANGE_PRICE;
				$pricing_policy->fromPrice     = $policy['from'];
				$pricing_policy->toPrice       = !empty( $policy['to'] ) ? $policy['to'] : null;
				$pricing_policy->pricingPolicy = ShippingPricePolicy::POLICY_FIXED_PRICE;
				$pricing_policy->fixedPrice    = $policy['amount'];
				$result[]                      = $pricing_policy->toArray();
			}
			break;
	}

	return $result;
}

function get_logo_url($method) {
	return str_replace('/resources/', '/resources/packlink/', $method['logoUrl']);
}


// ***********************************************************************************
// STEP 1. ***************************************************************************
// Read the current shipping methods.                                                *
// ***********************************************************************************

global $wpdb;
$db = $wpdb;

$table_name   = $db->prefix . Database::BASE_TABLE;
$raw_services = get_current_shipping_methods( $db, $table_name );

// ***********************************************************************************
// STEP 2. ***************************************************************************
// Transform the pricing policies.                                                   *
// ***********************************************************************************

foreach ( $raw_services as $index => $service ) {
	$raw_services[ $index ]['pricingPolicies'] = get_transformed_pricing_policy( $service );
}

// ***********************************************************************************
// STEP 3. ***************************************************************************
// Update logo url.                                                                  *
// ***********************************************************************************

foreach ( $raw_services as $index => $service ) {
	$raw_services[ $index ]['logoUrl'] = get_logo_url( $service );
}

// ***********************************************************************************
// STEP 4. ***************************************************************************
// Instantiate new shipping methods with the transformed data                        *
// ***********************************************************************************

$shipping_methods = array_map( static function ( array $raw_method ) {
	return ShippingMethod::fromArray( $raw_method );
}, $raw_services );

// ***********************************************************************************
// STEP 5. ***************************************************************************
// Save the updated shipping methods.                                                *
// ***********************************************************************************

/** @noinspection PhpUnhandledExceptionInspection */
$repository = RepositoryRegistry::getRepository( ShippingMethod::getClassName() );
foreach ( $shipping_methods as $method ) {
	$repository->save( $method );
}

//@codingStandardsIgnoreEnd