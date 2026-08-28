<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Services;

use Packlink\BusinessLogic\Customs\CustomsMappingService as Base_Customs_Mapping_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Customs_Mapping_Service
 *
 * WooCommerce implementation of the core abstract customs mapping service. It supplies the
 * platform-driven field definitions rendered by the core customs settings page: for each mapping
 * select, the label and the list of WooCommerce fields that can hold that customs value.
 *
 * The returned option `value`s are namespaced source descriptors (`attr:`, `meta:`, `order:`,
 * `user:`) resolved back into the core order objects at order-synchronization time
 * (see Shop_Order_Service).
 *
 * @package Packlink\WooCommerce\Components\Services
 */
class Customs_Mapping_Service extends Base_Customs_Mapping_Service {

	/**
	 * Product meta key holding the Packlink HS (tariff) code.
	 */
	const PRODUCT_HS_CODE_META = '_packlink_hs_code';

	/**
	 * Product meta key holding the country of origin (ISO-3166-1 alpha-2).
	 */
	const PRODUCT_COUNTRY_OF_ORIGIN_META = '_packlink_country_of_origin';

	/**
	 * Customer user meta key holding the customer tax ID / VAT number. A single value serves both
	 * customs attributes: the core routes it to `tax_id` for private-person receivers and to
	 * `vat_number` for company receivers, based on the configured receiver user type.
	 */
	const USER_TAX_ID_META = 'packlink_tax_id';

	/**
	 * Mapping-value namespace prefix for product attributes (global `pa_*` taxonomies or
	 * per-product custom attribute names).
	 */
	const PREFIX_ATTRIBUTE = 'attr:';

	/**
	 * Mapping-value namespace prefix for product meta keys.
	 */
	const PREFIX_PRODUCT_META = 'meta:';

	/**
	 * Mapping-value namespace prefix for order meta keys.
	 */
	const PREFIX_ORDER_META = 'order:';

	/**
	 * Mapping-value namespace prefix for customer user meta keys.
	 */
	const PREFIX_USER_META = 'user:';

	/**
	 * Transient key caching the distinct per-product custom attribute names.
	 */
	const ATTRIBUTE_NAMES_TRANSIENT = 'packlink_product_attribute_names';

	/**
	 * Curated order meta keys commonly used by VAT/tax-number plugins to store the customer
	 * tax or VAT number on the order.
	 *
	 * @var string[]
	 */
	private static $customer_order_meta_keys = array(
		'_vat_number',
		'_billing_vat_number',
		'_billing_eu_vat_number',
		'_billing_nif',
	);

	/**
	 * Returns the data-mapping field definitions rendered by the core customs settings page.
	 *
	 * One entry per mapping select, in the raw array shape the core CustomsController.js consumes:
	 * `{ field, label, options: [ { value, name } ] }`. The `field` values match the CustomsMapping
	 * DTO keys. Every select lists the dedicated Packlink field first, followed by the shared
	 * platform sources (product attributes or curated order meta keys).
	 *
	 * @return array
	 */
	public function getMappingFieldsOptions() {
		$attribute_options  = $this->get_product_attribute_options();
		$order_meta_options = $this->get_customer_order_meta_options();

		return array(
			array(
				'field'   => 'mapping_receiver_tax_id',
				'label'   => __( 'Customer tax ID / VAT number field', 'packlink-pro-shipping' ),
				'options' => array_merge(
					array(
						array(
							'value' => self::PREFIX_USER_META . self::USER_TAX_ID_META,
							'name'  => __( 'Packlink customer tax ID / VAT number', 'packlink-pro-shipping' ),
						),
					),
					$order_meta_options
				),
			),
			array(
				'field'   => 'mapping_tariff_number',
				'label'   => __( 'Product HS code field', 'packlink-pro-shipping' ),
				'options' => array_merge(
					array(
						array(
							'value' => self::PREFIX_PRODUCT_META . self::PRODUCT_HS_CODE_META,
							'name'  => __( 'Packlink product HS code', 'packlink-pro-shipping' ),
						),
					),
					$attribute_options
				),
			),
			array(
				'field'   => 'mapping_country_of_origin',
				'label'   => __( 'Product country of origin field', 'packlink-pro-shipping' ),
				'options' => array_merge(
					array(
						array(
							'value' => self::PREFIX_PRODUCT_META . self::PRODUCT_COUNTRY_OF_ORIGIN_META,
							'name'  => __( 'Packlink product country of origin', 'packlink-pro-shipping' ),
						),
					),
					$attribute_options
				),
			),
		);
	}

	/**
	 * Builds the shared product-attribute mapping options: every global WooCommerce attribute
	 * taxonomy plus the distinct per-product custom attribute names found in the catalog.
	 *
	 * @return array
	 */
	private function get_product_attribute_options() {
		$options = array();
		$covered = array();

		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $attribute ) {
				$covered[ $attribute->attribute_name ]         = true;
				$covered[ 'pa_' . $attribute->attribute_name ] = true;

				$options[] = array(
					'value' => self::PREFIX_ATTRIBUTE . 'pa_' . $attribute->attribute_name,
					'name'  => sprintf(
					/* translators: %s: product attribute label. */
						__( 'Product attribute: %s', 'packlink-pro-shipping' ),
						$attribute->attribute_label
					),
				);
			}
		}

		foreach ( $this->get_custom_attribute_names() as $name ) {
			if ( isset( $covered[ $name ] ) ) {
				continue;
			}

			$options[] = array(
				'value' => self::PREFIX_ATTRIBUTE . $name,
				'name'  => sprintf(
				/* translators: %s: product attribute name. */
					__( 'Product attribute: %s', 'packlink-pro-shipping' ),
					$name
				),
			);
		}

		return $options;
	}

	/**
	 * Returns the distinct per-product custom attribute names (non-taxonomy entries of the
	 * `_product_attributes` meta), scanned with a single bounded query over the postmeta table
	 * and cached in a short-lived transient.
	 *
	 * @return string[]
	 */
	private function get_custom_attribute_names() {
		$names = get_transient( self::ATTRIBUTE_NAMES_TRANSIENT );
		if ( is_array( $names ) ) {
			return $names;
		}

		global $wpdb;

		$found = array();
		$rows  = $wpdb->get_col(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_attributes' LIMIT 1000" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		);

		foreach ( (array) $rows as $row ) {
			$attributes = maybe_unserialize( $row );
			if ( ! is_array( $attributes ) ) {
				continue;
			}

			foreach ( $attributes as $attribute ) {
				if ( ! is_array( $attribute ) || ! empty( $attribute['is_taxonomy'] ) || empty( $attribute['name'] ) ) {
					continue;
				}

				$found[ (string) $attribute['name'] ] = true;
			}
		}

		$names = array_keys( $found );
		set_transient( self::ATTRIBUTE_NAMES_TRANSIENT, $names, 300 );

		return $names;
	}

	/**
	 * Builds the shared customer mapping options from the curated order meta keys commonly
	 * written by VAT/tax-number plugins.
	 *
	 * @return array
	 */
	private function get_customer_order_meta_options() {
		$options = array();

		foreach ( self::$customer_order_meta_keys as $key ) {
			$options[] = array(
				'value' => self::PREFIX_ORDER_META . $key,
				'name'  => sprintf(
				/* translators: %s: order meta key. */
					__( 'Order meta: %s', 'packlink-pro-shipping' ),
					$key
				),
			);
		}

		return $options;
	}
}
