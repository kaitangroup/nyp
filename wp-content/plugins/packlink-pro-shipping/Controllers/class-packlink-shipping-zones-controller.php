<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Controllers;

use WC_Shipping_Zones;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Packlink_Shipping_Zones_Controller
 *
 * @package Packlink\WooCommerce\Controllers
 */
class Packlink_Shipping_Zones_Controller extends Packlink_Base_Controller {

	/**
	 * Provides available shipping zones.
	 */
	public function get_shipping_zones() {
		$zones  = WC_Shipping_Zones::get_zones();
		$result = array_map(
			static function ( $zone ) {
				return array(
					'value' => (string) $zone['zone_id'],
					'label' => $zone['formatted_zone_location'],
				);
			},
			$zones
		);

		$result = array_values( $result );

		$this->return_json( $result );
	}
}
