<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Utility;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\WooCommerce\Components\Bootstrap_Component;
use wpdb;

/**
 * Class Database
 *
 * @package Packlink\WooCommerce\Components\Utility
 */
class Database {
	const BASE_TABLE = 'packlink_entity';

	/**
	 * WordPress database session.
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Database constructor.
	 *
	 * @param wpdb $db Database session.
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Checks if plugin was already installed and initialized.
	 *
	 * @return bool
	 */
	public function plugin_already_initialized() {
		$table_name = $this->db->prefix . self::BASE_TABLE;

		return $this->db->get_var( "SHOW TABLES LIKE '" . $table_name . "'" ) === $table_name;
	}

	/**
	 * Executes installation scripts.
	 */
	public function install() {
		$queries = $this->prepare_queries_for_install();
		foreach ( $queries as $query ) {
			$this->db->query( $query );
		}
	}

	/**
	 * Executes uninstall script.
	 */
	public function uninstall() {
		$table_name = $this->db->prefix . self::BASE_TABLE;

		$query = 'DROP TABLE IF EXISTS ' . $table_name;

		$this->db->query( $query );
	}

	/**
	 * Removes all Packlink related data from post meta table.
	 */
	public function remove_packlink_meta_data() {
		$query = "DELETE FROM `{$this->db->postmeta}` WHERE `meta_key` LIKE \"%_packlink_%\"";

		$this->db->query( $query );
	}

	/**
	 * Executes update database functions.
	 *
	 * @param Version_File_Reader $version_file_reader Version file reader.
	 *
	 * @return bool
	 */
	public function update( $version_file_reader ) {
		while ( $version_file_reader->has_next() ) {
			$version_file_reader->execute();
		}

		return true;
	}

	/**
	 * Returns IDs of the orders in WooCommerce shipped by Packlink.
	 *
	 * @return array
	 */
	public function get_packlink_order_ids() {
		$query = "SELECT p.ID AS id
			FROM `{$this->db->posts}` AS p
			INNER JOIN `{$this->db->postmeta}` AS m
			ON p.ID = m.post_id
			WHERE m.meta_key = '_is_packlink_shipment'
            AND m.meta_value = 'yes'
		";

		$results = $this->db->get_results( $query, ARRAY_A );

		if ( empty( $results ) ) {
			return array();
		}

		return array_map( function ( $order ) {
			return $order['id'];
		}, $results );
	}

	/**
	 * Adds additional index column to the Packlink entity table.
	 */
	public function add_additional_index() {
		$table_name = $this->db->prefix . self::BASE_TABLE;

		$sql = 'ALTER TABLE ' . $table_name . ' ADD `index_8` VARCHAR(127)';

		$this->db->query( $sql );
	}

	/**
	 * Prepares database queries for inserting tables.
	 *
	 * @return array
	 */
	private function prepare_queries_for_install() {
		$table_name = $this->db->prefix . self::BASE_TABLE;

		$queries   = array();
		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(127),
            `index_1` VARCHAR(127),
            `index_2` VARCHAR(127),
            `index_3` VARCHAR(127),
            `index_4` VARCHAR(127),
            `index_5` VARCHAR(127),
            `index_6` VARCHAR(127),
            `index_7` VARCHAR(127),
            `index_8` VARCHAR(127),
            `data` LONGTEXT,
            PRIMARY KEY (`id`)
        )';

		return $queries;
	}
}
