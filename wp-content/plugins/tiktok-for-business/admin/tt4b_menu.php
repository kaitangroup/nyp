<?php
/**
 * Copyright (c) Bytedance, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package TikTok
 */
require_once __DIR__ . '/../utils/utilities.php';
require_once 'Tt4b_Menu_Class.php';
add_action( 'admin_menu', array( 'tt4b_menu_class', 'tt4b_admin_menu' ) );
add_action( 'wp_loaded', array( 'tt4b_menu_class', 'tt4b_store_access_token' ) );
add_action( 'before_delete_post', 'tt4b_product_delete', 10, 2 );
add_action( 'wp_trash_post', 'tt4b_product_trashed' );
add_action( 'woocommerce_before_delete_product_variation', 'tt4b_variation_delete' );
add_action( 'untrashed_post', 'tt4b_product_untrashed' );

/**
 * Trash a product
 *
 * @param string $post_id The product_id.
 *
 * @return void
 */
function tt4b_product_trashed( $post_id ) {
	$post = get_post( $post_id );
	tt4b_product_delete( $post_id, $post );
}

/**
 * Delete or trash a product variation
 *
 * @param string $post_id The product/variation id to be deleted.
 *
 * @return void
 */
function tt4b_variation_delete( $post_id ) {
	$post = get_post( $post_id );
	tt4b_product_delete( $post_id, $post );
}

/**
 * Add restore products back to MAPI via upload API vs update
 * TODO: Use this during catalog and variation syncing for MAPI - move parent + children over from update to upload
 *
 * @param $post_id - The product/variation to be restored
 *
 * @return void
 */
function tt4b_product_untrashed( $post_id ) {
	$post = get_post( $post_id );
	if ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) {
		return;
	}
	$product = wc_get_product( $post_id );
	if ( is_null( $product ) ) {
		return;
	}
	$logger = new Logger();

	$access_token = get_option( 'tt4b_access_token' );
	$catalog_id   = get_option( 'tt4b_catalog_id' );
	$bc_id        = get_option( 'tt4b_bc_id' );
	if ( false === $access_token ) {
		$logger->log( __METHOD__, 'missing access token for tt4b_product_sync' );
		return;
	}
	if ( '' === $catalog_id ) {
		$logger->log( __METHOD__, 'missing catalog_id for tt4b_product_sync' );
		return;
	}
	if ( '' === $bc_id ) {
		$logger->log( __METHOD__, 'missing bc_id for tt4b_product_sync' );
		return;
	}

	$product_id = $product->get_id();

	$to_restore                = (array) get_option( 'tt4b_product_restore_queue', array() );
	$to_restore[ $product_id ] = $product_id;
	$logger->log( __METHOD__, json_encode( $to_restore ) . ' to restore' );
	update_option( 'tt4b_product_restore_queue', $to_restore );
}

/**
 * Delete a product or product_variation
 *
 * @param string  $post_id The product_id.
 * @param WP_Post $post    The post.
 *
 * @return void
 */
function tt4b_product_delete( $post_id, $post ) {
	if ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) {
		return;
	}
	$product = wc_get_product( $post_id );
	if ( is_null( $product ) ) {
		return;
	}
	$logger = new Logger();

	$access_token = get_option( 'tt4b_access_token' );
	$catalog_id   = get_option( 'tt4b_catalog_id' );
	$bc_id        = get_option( 'tt4b_bc_id' );
	if ( false === $access_token ) {
		$logger->log( __METHOD__, 'missing access token for tt4b_product_sync' );
		return;
	}
	if ( '' === $catalog_id ) {
		$logger->log( __METHOD__, 'missing catalog_id for tt4b_product_sync' );
		return;
	}
	if ( '' === $bc_id ) {
		$logger->log( __METHOD__, 'missing bc_id for tt4b_product_sync' );
		return;
	}

	$sku_id  = $product->get_sku();
	$id      = (string) $product->get_id();
	$sku_key = $sku_id;
	if ( '' === $sku_key ) {
		$sku_key = $id;
	}

	$product_to_delete = array(
		'id'    => $id,
		'data'  => json_encode(
			array(
				'id'  => $id,
				'sku' => $sku_id,
			)
		),
		'topic' => 'partner_gw_product_sync',
		'tag'   => 'delete',
	);

	// if it's a child product, delete the child product only
	if ( $product->get_parent_id() > 0 ) {
		$parent_id         = $product->get_parent_id();
		$product_to_delete = array(
			'id'        => $id,
			'data'      => json_encode(
				array(
					'id'        => $id,
					'sku'       => $sku_id,
					'parent_id' => (string) $parent_id,
				)
			),
			'parent_id' => (string) $parent_id,
			'topic'     => 'partner_gw_product_variation_sync',
			'tag'       => 'delete',
		);
		$parent_product    = wc_get_product( $parent_id );
		$parent_sku        = $parent_product->get_sku() ? $parent_product->get_sku() : (string) $parent_product->get_id();
		// if the child product sku is the same as the parent product, make sure to use the same concatenation logic as in catalog sync
		// otherwise use the unique child product sku for deletion
		$sku_key = variation_content_id_helper( Method::DELETE, $parent_sku, $sku_id, $id );
	}

	// add the sku to array of skus to be deleted - stored as an option to be processed during scheduled syncs & management page syncs
	$addToDelete = array(
		$sku_key => $product_to_delete,
	);
	$logger->log(
		__METHOD__,
		sprintf(
			'adding SKU_ID to delete: %s',
			$sku_id
		)
	);
	$toDelete = (array) get_option( 'tt4b_product_delete_queue', array() );
	$toDelete = array_merge( $addToDelete, $toDelete );
	update_option( 'tt4b_product_delete_queue', $toDelete );
}
