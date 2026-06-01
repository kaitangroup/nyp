<?php

namespace Packlink\WooCommerce\Components\Services;

use Packlink\BusinessLogic\Country\WarehouseCountryService;

/**
 * Class Warehouse_Country_Service
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Warehouse_Country_Service extends WarehouseCountryService {
	/**
	 * @param $associative
	 *
	 * @return \Packlink\BusinessLogic\Country\Country[]
	 * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
	 * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
	 */
	public function getSupportedCountries( $associative = true ) {
		$countries             = $this->getBrandConfigurationService()->get()->warehouseCountries;
		$allowed_countries     = WC()->countries->get_shipping_countries();
		$intersected_countries = array();

		foreach ( $allowed_countries as $key => $allowed_country ) {
			if ( array_key_exists( $key, $countries ) ) {
				$intersected_countries[] = $countries[ $key ];
			}
		}

		$result = $this->formatCountries( $intersected_countries );

		return $associative ? $result : array_values( $result );
	}
}