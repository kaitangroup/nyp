<?php
/**
 * Copyright (c) Bytedance, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package TikTok
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once __DIR__ . '/../utils/TBPApi.php';
require_once __DIR__ . '/../utils/utilities.php';
require_once __DIR__ . '/TikTokProductsController.php';

class Tt4b_Catalog_Class {



	/**
	 * The TikTok Mapi Class used to make various requests to TikTok
	 *
	 * @var Tt4b_Mapi_Class
	 */
	protected $mapi;

	/**
	 * The woocommerce logger
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Custom way to pull Woo products
	 *
	 * @var TikTokProductsController
	 */
	protected $tikTokProductsController;

	/**
	 * Week in seconds - used to determine if it's been at least a week since the last full sync
	 */
	const WEEK = 604800;

	/**
	 * Constructor
	 *
	 * @param Tt4b_Mapi_Class $mapi   The Tt4b_Mapi_Class
	 * @param Logger          $logger
	 *
	 * @return void
	 */
	public function __construct( Tt4b_Mapi_Class $mapi, Logger $logger ) {
		$this->mapi                     = $mapi;
		$this->logger                   = $logger;
		$this->tikTokProductsController = new TikTokProductsController();
	}

	/**
	 * Initializes actions related to Tt4b_Catalog_Class such as catalog sync functionality used by action_scheduler
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'tt4b_catalog_sync_helper', array( $this, 'catalog_sync_helper' ), 2, 9 );
		add_action( 'tt4b_catalog_sync', array( $this, 'catalog_sync' ), 1, 4 );
		add_action( 'tt4b_delete_products_helper', array( $this, 'delete_products_helper' ), 1, 8 );
		add_action( 'tt4b_variation_sync_helper', array( $this, 'variation_sync_helper' ), 2, 5 );
	}

	/**
	 * Returns the amount of catalog items are in approved/processing/rejected.
	 *
	 * @param string $access_token The MAPI issued access token
	 * @param string $bc_id        The users business center ID
	 * @param string $catalog_id   The users catalog ID
	 *
	 * @return array(processing, approved, rejected)
	 */
	public static function get_catalog_processing_status(
		$access_token,
		$bc_id,
		$catalog_id
	) {
		// returns a counter of how many items are approved, processing, or rejected
		// from the TikTok catalog/product/get/ endpoint
		$logger = new Logger();
		$mapi   = new Tt4b_Mapi_Class( $logger );

		$url    = 'catalog/overview/';
		$params = array(
			'bc_id'      => $bc_id,
			'catalog_id' => $catalog_id,
		);
		$base   = array(
			'processing' => 0,
			'approved'   => 0,
			'rejected'   => 0,
		);

		$result = $mapi->mapi_get( $url, $access_token, $params, 'v1.2' );
		$obj    = json_decode( $result, true );

		if ( ! isset( $obj['data'] ) ) {
			$logger->log( __METHOD__, 'get_catalog_processing_status data not set' );
			return $base;
		}

		if ( 'OK' !== $obj['message'] ) {
			$logger->log( __METHOD__, 'get_catalog_processing_status not OK response' );
			return $base;
		}

		$processing = $obj['data']['processing'];
		$approved   = $obj['data']['approved'];
		$rejected   = $obj['data']['rejected'];

		return array(
			'processing' => $processing,
			'approved'   => $approved,
			'rejected'   => $rejected,
		);
	}

	/**
	 * Begins catalog sync, if there is not one currently enqueued. Schedules recurring catalog sync on an hourly basis.
	 *
	 * @param string $catalog_id   The users catalog ID
	 * @param string $bc_id        The users business center ID
	 * @param string $store_name   The users store name
	 * @param string $access_token The MAPI issued access token
	 *
	 * @return void
	 */
	public function initiate_catalog_sync( $catalog_id, $bc_id, $store_name, $access_token ) {
		// check for woo install
		if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
			return;
		}

		$tt4b_catalog_sync_payload = array(
			'catalog_id'   => $catalog_id,
			'bc_id'        => $bc_id,
			'store_name'   => $store_name,
			'access_token' => $access_token,
		);

		if ( false === as_has_scheduled_action(
			'tt4b_catalog_sync'
		)
		) {
			as_schedule_cron_action(
				'today',
				$this->generate_cron_string(),
				'tt4b_catalog_sync',
				$tt4b_catalog_sync_payload,
				'tt4b_daily_catalog_sync'
			);
		}
	}

	/**
	 * Sync merchant catalog from woocommerce store to TikTok catalog manager via creation of catalog_sync_helper functions for batches of products
	 *
	 * @param string $catalog_id   The users catalog ID
	 * @param string $bc_id        The users business center ID
	 * @param string $store_name   The users store name
	 * @param string $access_token The MAPI issued access token
	 *
	 * @return void
	 */
	public function catalog_sync( $catalog_id, $bc_id, $store_name, $access_token ) {
		// check for woo install
		if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
			return;
		}
		$this->logger->log( __METHOD__, "catalog_sync executing for $store_name" );

		if ( '' === $catalog_id ) {
			$this->logger->log( __METHOD__, 'missing catalog_id for full catalog sync' );
			return;
		}
		if ( '' === $bc_id ) {
			$this->logger->log( __METHOD__, 'missing bc_id for full catalog sync' );
			return;
		}
		if ( '' === $access_token || false === $access_token ) {
			$this->logger->log( __METHOD__, 'missing access token for full catalog sync' );
			return;
		}
		// store_name just used for brand, can default it.
		if ( '' === $store_name ) {
			$store_name = 'WOO_COMMERCE';
		}
		$timeForSync              = get_option( 'tt4b_last_product_sync_time' );
		$lastFullSync             = get_option( 'tt4b_last_full_sync_time', 1 );
		$reconciliation_sync      = false;
		$reconciliation_sync_time = $timeForSync;
		// if the time elapsed since the last full sync is over a week, initiate a full catalog sync
		if ( time() - $lastFullSync >= self::WEEK ) {
			$timeForSync         = 1;
			$reconciliation_sync = true;
			$this->logger->log( __METHOD__, 'one week since last full sync, initiating full catalog sync' );
			update_option( 'tt4b_last_full_sync_time', time() );
		}
		update_option( 'tt4b_last_product_sync_time', time() );
		$args                             = array(
			'date_modified' => '>=' . $timeForSync,
			'paginate'      => true,
			'limit'         => 100,
		);
		$result                           = wc_get_products( $args );
		$pages                            = $result->max_num_pages;
		$tt4b_catalog_sync_helper_payload = array(
			'catalog_id'               => $catalog_id,
			'bc_id'                    => $bc_id,
			'store_name'               => $store_name,
			'access_token'             => $access_token,
			'page_total'               => $pages,
			'last_catalog_sync'        => $timeForSync,
			'reconciliation_sync'      => $reconciliation_sync,
			'reconciliation_sync_time' => $reconciliation_sync_time,
		);
		$formatted_last_catalog_sync      = wp_date( 'j F Y H:i:s', $timeForSync );
		$this->logger->log( __METHOD__, "deleting, adding, and updating products from wc_get_products since $formatted_last_catalog_sync" );
		self::check_and_start_async_action( 'tt4b_delete_products_helper', $tt4b_catalog_sync_helper_payload, '' );
	}

	/**
	 * Helper function used to delete products on tiktok catalog manager that have been removed (trashed or deleted) from the woocommerce store
	 * This function should run before any products sync to avoid issues when products are trashed and then untrashed
	 *
	 * @param string  $catalog_id               The users catalog ID
	 * @param string  $bc_id                    The users business center ID
	 * @param string  $store_name               The users store name
	 * @param string  $access_token             The MAPI issued access token
	 * @param integer $page_total               The maximum number of pages of 100 products to sync for this job
	 * @param integer $last_catalog_sync        The unix timestamp of the last catalog sync, used to fetch products modified and created since that timestamp
	 * @param boolean $reconciliation_sync      Control if the current sync is reconciliation for TikTok's product webhooks, which will sync all products to the webhooks and only deltas to the MAPI endpoint, defaults to false
	 * @param integer $reconciliation_sync_time The reference time for the reconciliation sync, which will be used if reconciliation sync is enabled, to make sure to only send recent modifications to the MAPI endpoint
	 *
	 * @return void
	 */
	public function delete_products_helper( $catalog_id, $bc_id, $store_name, $access_token, $page_total, $last_catalog_sync, $reconciliation_sync, $reconciliation_sync_time ) {
		// since delete_products_helper is the first job to run after a scheduled catalog sync, shift to daily sync instead of hourly sync if
		if ( true === as_has_scheduled_action( 'tt4b_catalog_sync', null, 'tt4b_scheduled_catalog_sync' ) ) {
			as_unschedule_all_actions( 'tt4b_catalog_sync', null, 'tt4b_scheduled_catalog_sync' );
			if ( false === as_has_scheduled_action( 'tt4b_catalog_sync', null, 'tt4b_daily_catalog_sync' ) ) {
				as_schedule_cron_action(
					'tomorrow',
					$this->generate_cron_string(),
					'tt4b_catalog_sync',
					array(
						'catalog_id'   => $catalog_id,
						'bc_id'        => $bc_id,
						'store_name'   => $store_name,
						'access_token' => $access_token,
					),
					'tt4b_daily_catalog_sync'
				);
			}
		}
		$products_to_delete = (array) get_option( 'tt4b_product_delete_queue', array() );
		// check count of products to delete, only send payload if SKUs array is not empty
		if ( 0 === count( $products_to_delete ) ) {
			$this->logger->log( __METHOD__, 'no products retrieved from tt4b_product_delete_queue option, skipping product deletion' );
		} else {
			$raw_products_payload      = array();
			$raw_variations_payload    = array();
			$mapi_dpa_products_payload = array();
			foreach ( $products_to_delete as $sku_key => $product ) {
				// type check here is necessary for backwards compatibility after API change:
				// allow remaining sku IDs to be posted to MAPI for deletion
				// new associative array of product data to be posted to new raw API and MAPI
				if ( is_int( $sku_key ) ) {
					$mapi_dpa_products_payload[] = $product;
				} elseif ( $product['topic'] === 'partner_gw_product_sync' ) {
					$mapi_dpa_products_payload[] = $sku_key;
					$raw_products_payload[]      = array(
						'id'   => $product['id'],
						'data' => $product['data'],
					);
				} elseif ( $product['topic'] === 'partner_gw_product_variation_sync' ) {
					$mapi_dpa_products_payload[] = $sku_key;
					$raw_variations_payload[]    = array(
						'id'        => $product['id'],
						'data'      => $product['data'],
						'parent_id' => $product['parent_id'],
					);
				}
			}
			if ( 0 < count( $raw_products_payload ) ) {
				$raw_products_request = array(
					'products' => $raw_products_payload,
					'topic'    => 'partner_gw_product_sync',
					'tag'      => 'delete',
				);
				$this->mapi->tbp_post( get_option( 'tt4b_external_data' ), 'woocommerce/php/product/batch/', 'v1.0', $raw_products_request, TBPApi::PLUGIN );
			}
			if ( 0 < count( $raw_variations_payload ) ) {
				$raw_variations_request = array(
					'products' => $raw_variations_payload,
					'topic'    => 'partner_gw_product_variation_sync',
					'tag'      => 'delete',
				);
				$this->mapi->tbp_post( get_option( 'tt4b_external_data' ), 'woocommerce/php/product/batch/', 'v1.0', $raw_variations_request, TBPApi::PLUGIN );
			}
			if ( 0 < count( $mapi_dpa_products_payload ) ) {
				$mapi_dpa_request = array(
					'sku_ids'    => $mapi_dpa_products_payload,
					'bc_id'      => $bc_id,
					'catalog_id' => $catalog_id,
				);
				$this->mapi->mapi_post( 'catalog/product/delete/', $access_token, $mapi_dpa_request, 'v1.3' );
			}
			update_option( 'tt4b_product_delete_queue', array() );
		}
		$tt4b_catalog_sync_helper_payload = array(
			'catalog_id'               => $catalog_id,
			'bc_id'                    => $bc_id,
			'store_name'               => $store_name,
			'access_token'             => $access_token,
			'page'                     => 1,
			'page_total'               => $page_total,
			'last_catalog_sync'        => $last_catalog_sync,
			'reconciliation_sync'      => $reconciliation_sync,
			'reconciliation_sync_time' => $reconciliation_sync_time,
		);
		// after product deletion is skipped or completed, proceed to initiate catalog sync helpers if anything needs to be synced
		if ( $page_total >= 1 ) {
			self::check_and_start_async_action( 'tt4b_catalog_sync_helper', $tt4b_catalog_sync_helper_payload, '' );
		}
	}

	/**
	 * Helper function used to post batches of products from woocommerce store to tiktok catalog manager
	 *
	 * @param string  $catalog_id               The users catalog ID
	 * @param string  $bc_id                    The users business center ID
	 * @param string  $store_name               The users store name
	 * @param string  $access_token             The MAPI issued access token
	 * @param integer $page                     The page of products from the user catalog
	 * @param integer $page_total               The maximum number of pages of 100 products to sync for this job
	 * @param integer $last_catalog_sync        The unix timestamp of the last catalog sync, used to fetch products modified and created since that timestamp
	 * @param boolean $reconciliation_sync Control if the current sync is reconciliation for TikTok's product webhooks, which will sync all products to the webhooks and only deltas to the MAPI endpoint, defaults to false
	 * @param integer $reconciliation_sync_time The reference time for the reconciliation sync, which will be used if reconciliation sync is enabled, to make sure to only send recent modifications to the MAPI endpoint
	 *
	 * @return void
	 */
	public function catalog_sync_helper( string $catalog_id, string $bc_id, string $store_name, string $access_token, int $page, int $page_total, int $last_catalog_sync, bool $reconciliation_sync, int $reconciliation_sync_time ) {
		$wc_get_products_args = array(
			'date_modified' => '>=' . $last_catalog_sync,
			'limit'         => 100,
			'page'          => $page,
		);
		$parsed_time          = date( 'Y-m-d\TH:i:s', $last_catalog_sync );
		$request              = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'per_page'       => 100,
				'page'           => $page,
				'modified_after' => $parsed_time,
				'dates_are_gmt'  => true,
			)
		);
		$products = wc_get_products( $wc_get_products_args );
		if ( 0 === count( $products ) ) {
			$this->logger->log( __METHOD__, 'no products retrieved from wc_get_products' );
		}
		$failed_products_count = 0;
		$products_to_restore   = (array) get_option( 'tt4b_product_restore_queue', array() );
		$mapi_reference_time   = $last_catalog_sync;
		if ( $reconciliation_sync ) {
			$mapi_reference_time = $reconciliation_sync_time;
		}

		$raw_products_payload = array();
		$mapi_upload_payload  = array();
		$mapi_update_payload = array();
		foreach ( $products as $product ) {
			if ( is_null( $product ) ) {
				++$failed_products_count;
				continue;
			}
			$product_id  = $product->get_id();
			$product_sku = $product->get_sku();

			$dpa_product_info = $this->generate_product_info( $store_name, $product );
			if ( array() === $dpa_product_info ) {
				$this->logger->log( __METHOD__, 'unable to parse product: ' . $product_id . ' in to MAPI DPA schema' );
				++$failed_products_count;
				continue;
			}
			$restored_product = array_key_exists( $product_id, $products_to_restore );
			if ( $restored_product ) {
				unset( $products_to_restore[ $product_id ] );
			}

			$create_time = $product->get_date_created()->getTimestamp();
			if ( $create_time < $mapi_reference_time && ! $restored_product ) {
				$mapi_update_payload[] = $dpa_product_info;
			} else {
				$mapi_upload_payload[] = $dpa_product_info;
			}

			$response = $this->tikTokProductsController->prepare_object( $product, $request );
			if ( null == $response || $response->is_error() ) {
				$this->logger->log( __METHOD__, 'unable to parse product: ' . $product_id . ' in to REST API schema' );
				++$failed_products_count;
				continue;
			}

			$product_information    = array(
				'id'   => (string) $product_id,
				'data' => json_encode( $response->get_data() ),
			);
			$raw_products_payload[] = $product_information;

			if ( $product->is_type( 'variable' ) ) {
				$args            = array(
					'parent'   => $product_id,
					'type'     => 'variation',
					'paginate' => true,
					'limit'    => 100,
				);
				$result          = wc_get_products( $args );
				$variation_pages = $result->max_num_pages;

				$tt4b_variation_sync_payload = array(
					'parent_id'    => $product_id,
					'parent_sku'   => $product_sku,
					'access_token' => $access_token,
					'page_total'   => $variation_pages,
					'page'         => 1,
				);
				self::check_and_start_async_action( 'tt4b_variation_sync_helper', $tt4b_variation_sync_payload, '' );

				$variations     = $product->get_available_variations( 'objects' );
				$dpa_variations = $this->fetch_mapi_product_variations( $dpa_product_info['sku_id'], $dpa_product_info['description'] !== $dpa_product_info['title'] ? $dpa_product_info['description'] : '', $store_name, $variations, $mapi_reference_time );
				$update_variations = $dpa_variations[0];
				$upload_variations   = $dpa_variations[1];
				$mapi_upload_payload = array_merge( $mapi_upload_payload, $upload_variations );
				$mapi_update_payload = array_merge( $mapi_update_payload, $update_variations );
			}
		}

		if ( 0 < count( $raw_products_payload ) ) {
			$raw_products_request = array(
				'topic'    => 'partner_gw_product_sync',
				'tag'      => 'update',
				'products' => $raw_products_payload,
			);
			$this->mapi->tbp_post( get_option( 'tt4b_external_data' ), 'woocommerce/php/product/batch/', 'v1.0', $raw_products_request, TBPApi::PLUGIN );
		}
		if ( 0 < count( $mapi_upload_payload ) ) {
			$mapi_upload_request = array(
				'bc_id'      => $bc_id,
				'catalog_id' => $catalog_id,
				'products'   => $mapi_upload_payload,
			);
			$this->mapi->mapi_post( 'catalog/product/upload/', $access_token, $mapi_upload_request, 'v1.3' );
		}
		if ( 0 < count( $mapi_update_payload ) ) {
			 $mapi_update_request = [
			 'bc_id' => $bc_id,
			 'catalog_id' => $catalog_id,
			 'products' => $mapi_update_payload
		 ];
		 // use upload endpoint until update endpoint has better field support
		 $this->mapi->mapi_post( 'catalog/product/upload/', $access_token, $mapi_update_request, 'v1.3' );
		}
		update_option( 'tt4b_product_restore_queue', $products_to_restore );

		++$page;
		$tt4b_catalog_sync_helper_payload = array(
			'catalog_id'               => $catalog_id,
			'bc_id'                    => $bc_id,
			'store_name'               => $store_name,
			'access_token'             => $access_token,
			'page'                     => $page,
			'page_total'               => $page_total,
			'last_catalog_sync'        => $last_catalog_sync,
			'reconciliation_sync'      => $reconciliation_sync,
			'reconciliation_sync_time' => $reconciliation_sync_time,
		);
		if ( $page <= $page_total ) {
			self::check_and_start_async_action( 'tt4b_catalog_sync_helper', $tt4b_catalog_sync_helper_payload, '' );
		}
	}

	/**
	 * Sync a product's variants to the TikTok catalog
	 *
	 * @param int    $parent_id
	 * @param string $parent_sku
	 * @param string $access_token
	 * @param int    $page_total
	 * @param int    $page
	 *
	 * /**
	 *  Sync a product's variants to the TikTok catalog
	 *
	 *  @param int    $parent_id
	 *  @param string $parent_sku
	 *  @param string $access_token
	 *  @param int    $page_total
	 *  @param int    $page
	 *
	 *  @return void
	 * @return void
	 */
	public function variation_sync_helper( int $parent_id, string $parent_sku, string $access_token, int $page_total, int $page ) {
		$wc_get_product_variation_args = array(
			'parent' => $parent_id,
			'type'   => 'variation',
			'limit'  => 100,
			'page'   => $page,
		);
		$products                      = wc_get_products( $wc_get_product_variation_args );
		if ( 0 === count( $products ) ) {
			$this->logger->log( __METHOD__, 'no variations retrieved from wc_get_products for parent product: ' . $parent_id );
		}
		$failed_products_count = 0;
		$raw_products_payload  = array();
		foreach ( $products as $product ) {
			if ( is_null( $product ) ) {
				++$failed_products_count;
				continue;
			}
			$product_id = $product->get_id();

			$request = new WP_REST_Request( 'GET', "wc/v3/products/$parent_id" );
			$request->set_query_params(
				array(
					'dates_are_gmt' => true,
				)
			);

			$response = $this->tikTokProductsController->prepare_object( $product, $request );
			if ( null == $response || $response->is_error() ) {
				$this->logger->log( __METHOD__, 'unable to parse product: ' . $product_id . ' in to REST API schema' );
				++$failed_products_count;
				continue;
			}

			$product_information    = array(
				'id'        => (string) $product_id,
				'data'      => json_encode( $response->get_data() ),
				'parent_id' => (string) $parent_id,
			);
			$raw_products_payload[] = $product_information;
		}

		if ( 0 < count( $raw_products_payload ) ) {
			$raw_products_request = array(
				'topic'    => 'partner_gw_product_variation_sync',
				'tag'      => 'update',
				'products' => $raw_products_payload,
			);
			$this->mapi->tbp_post( get_option( 'tt4b_external_data' ), 'woocommerce/php/product/batch/', 'v1.0', $raw_products_request, TBPApi::PLUGIN );
		}

		++$page;
		$tt4b_variation_sync_payload = array(
			'parent_id'    => $parent_id,
			'parent_sku'   => $parent_sku,
			'access_token' => $access_token,
			'page_total'   => $page_total,
			'page'         => $page,
		);
		if ( $page <= $page_total ) {
			self::check_and_start_async_action( 'tt4b_variation_sync_helper', $tt4b_variation_sync_payload, '' );
		}
	}

	/**
	 * Prepare child product_variations associated with parent variable product to be synced to TikTok MAPI catalog
	 *
	 * @param string                 $parent_sku
	 * @param string                 $parent_description
	 * @param string                 $store_name
	 * @param WC_Product_Variation[] $product_variations
	 * @param integer                $last_catalog_sync The unix timestamp of the last catalog sync, used to fetch products modified and created since that timestamp
	 *
	 * @return array
	 */
	public function fetch_mapi_product_variations( $parent_sku, $parent_description, $store_name, $product_variations, $last_catalog_sync ) {
		$dpa_variation_update_products = array();
		$dpa_variation_upload_products = array();
		$products_to_restore           = (array) get_option( 'tt4b_product_restore_queue', array() );
		$failed_variations_count       = 0;
		if ( 0 === count( $product_variations ) ) {
			$this->logger->log( __METHOD__, 'empty array of variable products provided' );
		}
		foreach ( $product_variations as $variation ) {
			if ( is_null( $variation ) ) {
				++$failed_variations_count;
				continue;
			}
			$dpa_variation_product = $this->generate_product_info( $store_name, $variation, $parent_sku, $parent_description );
			$variation_id          = $variation->get_id();

			$restored_product = array_key_exists( $variation_id, $products_to_restore );
			if ( $restored_product ) {
				unset( $products_to_restore[ $variation_id ] );
			}

			if ( array() === $dpa_variation_product ) {
				++$failed_variations_count;
				continue;
			}

			$create_time = $variation->get_date_created()->getTimestamp();
			if ( $create_time < $last_catalog_sync && ! $restored_product ) {
				$dpa_variation_update_products[] = $dpa_variation_product;
			} else { // phpcs:ignore Universal.ControlStructures.IfElseDeclaration.NoNewLine
				$dpa_variation_upload_products[] = $dpa_variation_product;
			}
		}
		update_option( 'tt4b_product_restore_queue', $products_to_restore );
		return array( $dpa_variation_update_products, $dpa_variation_upload_products );
	}

	/**
	 * Check if async action should be added according to name, payload, and group
	 *
	 * @param string $action_name name of the action to run
	 * @param array  $payload     array payload for the action
	 *
	 * @param string $group       action group, pass empty string if no group
	 */
	private function check_and_start_async_action( string $action_name, array $payload, string $group ) {
		if ( '' == $group ) {
			if ( false === as_has_scheduled_action(
				$action_name,
				$payload
			)
			) {
				as_enqueue_async_action(
					$action_name,
					$payload
				);
			}
		} elseif ( false === as_has_scheduled_action(
			$action_name,
			$payload,
			$group
		)
			) {

				as_enqueue_async_action(
					$action_name,
					$payload,
					$group
				);
		}
	}

	/**
	 * Generate the needed product_data in array format for products, and product variants accordingly
	 *
	 * @param string     $store_name
	 * @param WC_Product $product
	 * @param string     $parent_sku         optional - provided for child products (variants) to associate child product to parent product
	 * @param string     $parent_description optional - provided for child products in case the child doesn't have a unique description
	 *
	 * @return array
	 */
	public function generate_product_info( $store_name, $product, $parent_sku = '', $parent_description = '' ) {
		$title       = $product->get_name();
		$description = $product->get_short_description();
		if ( '' === $description && '' === $parent_description ) {
			$description = $title;
		} elseif ( '' === $description && '' !== $parent_description ) {
			$description = $parent_description;
		}
		$condition = 'NEW';

		$availability = 'IN_STOCK';
		$stock_status = $product->is_in_stock();
		if ( false === $stock_status ) {
			$availability = 'OUT_OF_STOCK';
		}
		$sku_id  = (string) $product->get_sku();
		$raw_sku = $sku_id;
		if ( '' === $sku_id ) {
			$sku_id = (string) $product->get_id();
		}
		// if parent_id is not provided then the item_group_id is equal to the current product's sku_id
		// if parent_id is provided meaning product is child of parent_id, the item_group_id should be the parent_sku
		$item_group_id = $sku_id;
		if ( '' !== $parent_sku ) {
			$item_group_id = $parent_sku;
			// if the current product SKU is the same as it's parent SKU, concatenate $parent_sku with the child post ID
			// otherwise use the SKU of the variation
			$sku_id = variation_content_id_helper( Method::CATALOG, $parent_sku, $sku_id, $product->get_id() );
			// if there is a variation description only for this variant, use that instead of either
			// the parent description or the title for the description field in the TikTok Catalog
			$variantDescription = $product->get_description();
			if ( '' !== $variantDescription ) {
				$description = $variantDescription;
			}
		}
		$link      = get_permalink( $product->get_id() );
		$image_id  = $product->get_image_id();
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );
		$price     = $product->get_price();
		// if regular price is not empty, false, or string use that instead
		$regularPrice = $product->get_regular_price();
		if ( '' !== $regularPrice && false !== $regularPrice && '0' !== $regularPrice ) {
			$price = $regularPrice;
		}
		$sale_price = $product->get_sale_price();
		if ( '0' === $sale_price || '' === $sale_price ) {
			$sale_price = $price;
		}
		// Get product gallery images - max 10
		$gallery_image_ids  = array_slice( $product->get_gallery_image_ids(), 0, 10, true );
		$gallery_image_urls = array();
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			$gallery_image_urls[] = wp_get_attachment_image_url( $gallery_image_id, 'full' );
		}

		// if any of the values are empty, the whole request will fail, so skip the product.
		$missing_fields = array();
		if ( '' === $sku_id || false === $sku_id ) {
			$missing_fields[] = 'sku_id';
		}
		if ( '' === $title || false === $title ) {
			$missing_fields[] = 'title';
		}
		if ( '' === $image_url || false === $image_url ) {
			$missing_fields[] = 'image_url';
		}
		if ( '' === $price || false === $price || '0' === $price ) {
			$missing_fields[] = 'price';
		}
		if ( count( $missing_fields ) > 0 ) {
			$debug_message = sprintf(
				'sku_id: %s title: %s is missing the following fields for product sync: %s',
				$sku_id,
				$title,
				join( ',', $missing_fields )
			);
			$this->logger->log( __METHOD__, $debug_message );
			return array();
		}

		$dpa_product = array(
			'sku_id'         => $sku_id,
			'item_group_id'  => $item_group_id,
			'title'          => $title,
			'availability'   => $availability,
			'description'    => $description,
			'image_url'      => $image_url,
			'brand'          => $store_name,
			'product_detail' => array(
				'condition' => $condition,
			),
			'price_info'     => array(
				'price'      => (float) $price,
				'sale_price' => (float) $sale_price,
			),
			'landing_page'   => array(
				'landing_page_url' => $link,
			),
			'extra_info'     => array(
				'custom_label_0' => $raw_sku,
			),

		);

		// add additional product images if available
		if ( count( $gallery_image_urls ) > 0 ) {
			$dpa_product['additional_image_link'] = $gallery_image_urls;
		}

		// Add optional catalog fields from product attributes or meta if available.
		// google_product_category is top-level; size, gender, age_group, product_category go inside product_detail.
		$top_level_fields    = array( 'google_product_category' );
		$detail_fields       = array( 'size', 'gender', 'age_group' );
		$all_optional_fields = array_merge( $top_level_fields, $detail_fields, array( 'product_type' ) );
		foreach ( $all_optional_fields as $field ) {
			$value = $product->get_attribute( $field );
			if ( empty( $value ) ) {
				$value = get_post_meta( $product->get_id(), $field, true );
			}
			if ( ! empty( $value ) ) {
				$value = html_entity_decode( $value );
				if ( 'product_type' === $field ) {
					// v1.3 API uses product_detail.product_category, not top-level product_type.
					$dpa_product['product_detail']['product_category'] = $value;
				} elseif ( in_array( $field, $detail_fields, true ) ) { // phpcs:ignore Universal.ControlStructures.IfElseDeclaration.NoNewLine
					$dpa_product['product_detail'][ $field ] = $value;
				} else { // phpcs:ignore Universal.ControlStructures.IfElseDeclaration.NoNewLine
					$dpa_product[ $field ] = $value;
				}
			}
		}

		// Fallback: derive product_category from WooCommerce product categories if not set via attribute or meta.
		if ( empty( $dpa_product['product_detail']['product_category'] ) ) {
			$product_id = $product->get_id();
			if ( $product->is_type( 'variation' ) ) {
				$product_id = $product->get_parent_id();
			}
			$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
				$decoded = array_map( 'html_entity_decode', $categories );
				$dpa_product['product_detail']['product_category'] = implode( ' > ', $decoded );
			}
		}

		return $dpa_product;
	}

	/**
	 * Returns a cron string with randomized hour and minute values for scheduling recurring eligibility collection
	 *
	 * @return string
	 */
	private function generate_cron_string() {
		$minute = rand( 0, 59 );
		$hour   = rand( 0, 23 );
		return '' . $minute . ' ' . $hour . ' * * 0-6';
	}
}
