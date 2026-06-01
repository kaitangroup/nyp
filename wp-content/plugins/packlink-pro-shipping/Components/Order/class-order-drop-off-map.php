<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Order;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

/**
 * Class Order_Drop_Off_Map
 *
 * @package Packlink\WooCommerce\Components\Order
 */
class Order_Drop_Off_Map extends Entity {
	/**
	 * Fully qualified name of this class.
	 */
	const CLASS_NAME = __CLASS__;
	/**
	 * WooCommerce order ID.
	 *
	 * @var int
	 */
	protected $order_id;
	/**
	 * Packlink drop-off point ID.
	 *
	 * @var int
	 */
	protected $drop_off_point_id;
	/**
	 * Array of field names.
	 *
	 * @var array
	 */
	protected $fields = array(
		'id',
		'order_id',
		'drop_off_point_id',
	);

	/**
	 * @inheritDoc
	 */
	public function getConfig() {
		$map = new IndexMap();
		$map->addIntegerIndex('order_id')
		    ->addIntegerIndex('drop_off_point_id');

		return new EntityConfiguration($map, 'Order_Drop_Off_Map');
	}

	/**
	 * Returns order ID.
	 *
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Sets order ID.
	 *
	 * @param int $order_id
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * Returns drop-off point ID.
	 *
	 * @return int
	 */
	public function get_drop_off_point_id() {
		return $this->drop_off_point_id;
	}

	/**
	 * Sets drop-off point ID.
	 *
	 * @param int $drop_off_point_id
	 */
	public function set_drop_off_point_id( $drop_off_point_id ) {
		$this->drop_off_point_id = $drop_off_point_id;
	}
}
