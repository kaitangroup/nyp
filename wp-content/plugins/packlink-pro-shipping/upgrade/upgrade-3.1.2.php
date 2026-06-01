<?php

/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

use Packlink\WooCommerce\Components\Utility\Database;

//@codingStandardsIgnoreStart
/**
 * @param wpdb   $db
 * @param string $table_name
 */
function update_saved_parcel( $db, $table_name ) {
	$query   = "SELECT * FROM {$table_name} WHERE index_1 = 'defaultParcel' ";
	$results = $db->get_results( $query, ARRAY_A );
	foreach ( $results as $parcel ) {
		if ( empty( $parcel['data'] ) ) {
			continue;
		}

		$updatePayload = json_decode( $parcel['data'], true );
		if ( ! empty( $updatePayload['value']['weight'] ) ) {
			$weight                           = ( float ) $updatePayload['value']['weight'];
			$updatePayload['value']['weight'] = ! empty( $weight ) ? $weight : 1;
		}

		foreach ( array( 'length', 'height', 'width' ) as $field ) {
			if ( ! empty( $updatePayload['value'][ $field ] ) ) {
				$fieldValue                       = ( int ) $updatePayload['value'][ $field ];
				$updatePayload['value'][ $field ] = ! empty( $fieldValue ) ? $fieldValue : 10;
			}
		}

		if ( ! empty( $parcel['id'] ) ) {
			$db->update( $table_name, array( 'data' => json_encode( $updatePayload ) ), array( 'id' => $parcel['id'] ) );
		}
	}
}

global $wpdb;
$db = $wpdb;

$table_name = $db->prefix . Database::BASE_TABLE;
update_saved_parcel( $db, $table_name );

//@codingStandardsIgnoreEnd