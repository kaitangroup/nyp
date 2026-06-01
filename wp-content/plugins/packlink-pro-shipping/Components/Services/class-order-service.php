<?php

namespace Packlink\WooCommerce\Components\Services;

use Packlink\BusinessLogic\Order\OrderService;

class Order_Service extends OrderService {

	/**
	 * Get store unit from woocommerce
	 *
	 * @return string
	 */
	protected function getStoreUnit() {
		return \get_option('woocommerce_weight_unit', 'kg');
	}
}