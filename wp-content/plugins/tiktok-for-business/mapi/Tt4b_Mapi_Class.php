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

require_once __DIR__ . '/../utils/utilities.php';

class Tt4b_Mapi_Class {

	/**
	 * The TikTok Ads endpoint base url.
	 *
	 * @var string
	 */
	protected $mapi_url;

	/**
	 * The TBP endpoint base url.
	 *
	 * @var string
	 */
	protected $tbp_url;

	/**
	 * The plugin endpoint base url.
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * The wc_get_logger interface.
	 *
	 * @var WC_Logger_Interface
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct( Logger $logger ) {
		$this->mapi_url   = 'https://business-api.tiktok.com/open_api/';
		$this->tbp_url    = 'https://business-api.tiktok.com/tbp/';
		$this->plugin_url = 'https://business-api.tiktok.com/plugin/';
		$this->logger     = $logger;
	}

	/**
	 * Initializes actions related to Tt4b_Mapi_Class such as eligibility information collection
	 */
	public function init() {
		add_action( 'tt4b_trust_signal_collection', array( $this, 'retrieve_eligibility_information' ), 1, 0 );
		add_action( 'tt4b_trust_signal_helper', array( $this, 'retrieve_eligibility_helper' ), 2, 1 );
	}

	/**
	 * Posts to business-api.tiktok.com
	 *
	 * @param string $endpoint     The endpoint for the mapi post
	 * @param string $access_token The MAPI issued access token
	 * @param array  $params       Whichever params to be included with the post
	 * @param string $version      The MAPI version used
	 * @param bool   $blocking     Whether the request should be synchronous
	 *
	 * @return string
	 */
	public function mapi_post( $endpoint, $access_token, $params, $version, $blocking = true ) {
		$url      = $this->mapi_url . $version . '/' . $endpoint;
		$args     = array(
			'blocking'    => $blocking,
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array(
				'Access-Token' => $access_token,
				'Content-Type' => 'application/json',
			),
			'body'        => json_encode( $params ),
		);
		$response = wp_remote_post( $url, $args );
		if ( $blocking ) {
			$this->logger->log_request( $url, $args );
			$this->logger->log_response( __METHOD__, $response );
		}
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}

	/**
	 * Get from business-api.tiktok.com
	 *
	 * @param string $endpoint     The endpoint for the mapi post
	 * @param string $access_token The MAPI issued access token
	 * @param array  $params       Whichever params to be included with the post
	 * @param string $version      The MAPI version used
	 *
	 * @return string
	 */
	public function mapi_get( $endpoint, $access_token, $params, $version ) {
		$url  = $this->mapi_url . $version . '/' . $endpoint . '?' . http_build_query( $params );
		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Access-Token' => $access_token,
				'Content-Type' => 'application/json',
			),
		);
		$this->logger->log_request( $url, $args );
		$result = wp_remote_get( $url, $args );
		$this->logger->log_response( __METHOD__, $result );
		$body = wp_remote_retrieve_body( $result );
		return $body;
	}

	/**
	 * Get from tbp/business_profile
	 *
	 * @param string $access_token         The MAPI issued access token
	 * @param string $external_business_id The exteneral business_id of the merchant
	 *
	 * @return string
	 */
	public function get_business_profile( $access_token, $external_business_id ) {
		// returns a raw API response from TikTok tbp/business_profile/get/ endpoint

		if ( false === $external_business_id ) {
			$this->logger->log( __METHOD__, 'external_business_id not found, exiting' );
			return '';
		}

		$url    = 'tbp/business_profile/get/';
		$params = array(
			'business_platform'    => 'WOO_COMMERCE',
			'external_business_id' => $external_business_id,
			'full_data'            => 1,
		);
		$result = $this->mapi_get( $url, $access_token, $params, 'v1.2' ); // TODO: update to v1.3.
		return $result;
	}

	/**
	 *
	 * Get from api/user/location
	 *
	 * @return string
	 */
	public function get_user_location() {
		// returns a raw API response from TikTok api/user/location endpoint
		$url = 'https://ads.tiktok.com/creative_hub_server/api/user/location';
		$this->logger->log_request( $url, array() );
		$result = wp_remote_get( $url );
		$this->logger->log_response( __METHOD__, $result );
		return wp_remote_retrieve_body( $result );
	}


	/**
	 * Post to tbp
	 *
	 * @param string $external_data The external data
	 * @param string $endpoint      The endpoint
	 * @param string $version       The version
	 * @param array  $params        The body of the request
	 * @param int    $tbp_api_type  Which url to use based on the TBPApi abstract class
	 * @param bool   $blocking      Whether the request should be synchronous
	 *
	 * @return string
	 */
	public function tbp_post( $external_data, $endpoint, $version, $params, $tbp_api_type, $blocking = true ) {
		// posts to TBP
		switch ( $tbp_api_type ) {
			case TBPApi::PLUGIN:
				$domain = $this->plugin_url;
				break;
			default:
				$domain = $this->tbp_url;
				break;
		}
		$base_url = $domain . $version . '/' . $endpoint;
		$url      = $base_url . '?external_data=' . $external_data;
		$args     = array(
			'blocking'    => $blocking,
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'body'        => json_encode( $params ),
		);
		$response = wp_remote_post( $url, $args );
		if ( $blocking ) {
			$this->logger->log_request( $url, $args );
			$this->logger->log_response( __METHOD__, $response );
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Update from tbp/business_profile
	 *
	 * @param string  $access_token                           The MAPI issued access token
	 * @param string  $external_business_id                   The external business_id of the merchant
	 * @param integer $total_gmv                              The merchant's total gmv
	 * @param integer $total_orders                           The merchant's total orders
	 * @param integer $total_orders                           The merchant's tenure in days
	 * @param string  $current_tiktok_for_woocommerce_version The current tiktok-for-woocommerce version
	 *
	 * @return void
	 */
	public function update_business_profile( $access_token, $external_business_id, $total_gmv, $total_orders, $days_since_first_order, $current_tiktok_for_woocommerce_version ) {
		// updates the business_profile. Used for updating a merchants eligibility criteria.
		if ( false === $external_business_id ) {
			$this->logger->log( __METHOD__, 'external_business_id not found, exiting' );
		}

		$url             = 'tbp/business_profile/store/update/';
		$net_gmv         = array(
			array(
				'interval' => 'LIFETIME',
				'max'      => $total_gmv,
				'min'      => $total_gmv,
				'unit'     => 'CURRENCY',
			),
		);
		$net_order_count = array(
			array(
				'interval' => 'LIFETIME',
				'max'      => $total_orders,
				'min'      => $total_orders,
				'unit'     => 'COUNT',
			),
		);
		$tenure          = array(
			'min'  => $days_since_first_order,
			'max'  => $days_since_first_order,
			'unit' => 'DAYS',
		);
		$params          = array(
			'business_platform'    => 'WOO_COMMERCE',
			'external_business_id' => $external_business_id,
			'net_gmv'              => $net_gmv,
			'net_order_count'      => $net_order_count,
			'tenure'               => $tenure,
			'extra_data'           => $current_tiktok_for_woocommerce_version,
		);
		$this->mapi_post( $url, $access_token, $params, 'v1.2' ); // TODO: update to v1.3.

		// Send partner insights data.
		$insights_data = $this->collect_partner_insights_data( $days_since_first_order );
		$this->partner_insights_update( get_option( 'tt4b_external_data' ), $insights_data );
	}

	/**
	 * Returns a raw API response from TikTok
	 * marketing_api/api/developer/app/create_auto_approve/
	 *
	 * @param string $smb_id       The merchants external_business_id
	 * @param string $smb_name     The MAPI issued access token
	 * @param string $redirect_uri The redirect_url (the store url)
	 *
	 * @return string|bool
	 */
	public function create_open_source_app( $smb_id, $smb_name, $redirect_uri ) {
		$url               = 'https://ads.tiktok.com/marketing_api/api/developer/app/create_auto_approve/';
		$open_source_token = '244e1de7-8dad-4656-a859-8dc09eea299d';
		$tries             = 0;
		$params            = array(
			'business_platform' => 'PROD',
			'smb_id'            => $smb_id,
			'smb_name'          => $smb_name,
			'redirect_url'      => $redirect_uri,
		);
		$args              = array(
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array(
				'Access-Token' => $open_source_token,
				'Content-Type' => 'application/json',
				'Referer'      => 'https://ads.tiktok.com',
			),
			'body'        => json_encode( $params ),
		);
		$this->logger->log_request( $url, $args );
		while ( $tries <= 3 ) {
			$response = wp_remote_post( $url, $args );
			++$tries;
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$this->logger->log_response( __METHOD__, $response );
			} else {
				$this->logger->log_response( __METHOD__, $response );
				return wp_remote_retrieve_body( $response );
			}
		}
		return false;
	}

	/**
	 * Returns a raw API response from TikTok oauth2/access_token_v2/ endpoint
	 *
	 * @param string $app_id    The MAPI app_id
	 * @param string $secret    The MAPI secret
	 * @param string $auth_code The auth_code
	 * @param string $version   The MAPI version used
	 *
	 * @return string
	 */
	public function get_access_token( $app_id, $secret, $auth_code, $version ) {
		$endpoint = 'oauth2/access_token/';
		$url      = $this->mapi_url . $version . '/' . $endpoint;
		$params   = array(
			'app_id'    => $app_id,
			'secret'    => $secret,
			'auth_code' => $auth_code,
		);
		$args     = array(
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => json_encode( $params ),
		);
		$this->logger->log_request( $url, $args );
		$response = wp_remote_post( $url, $args );
		$this->logger->log_response( __METHOD__, $response );
		$body = wp_remote_retrieve_body( $response );
		return $body;
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

	/**
	 * Begins first eligibility information collection process, and scheduled recurring collection if not already scheduled
	 *
	 * @return void
	 */
	public function fetch_eligibility() {
		// only fetch if using woocommerce
		if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
			return;
		}

		$currentGroup = 'tt4b_version_' . get_option( 'tt4b_version' );
		if ( false === as_has_scheduled_action( 'tt4b_trust_signal_collection', array(), $currentGroup ) ) {
			// if no scheduled trust signal collection actions with $currentGroup group name, but there are scheduled actions (with no group name, or with other group names)
			// that means there are scheduled actions from a previous version that should be removed and replaced with scheduled actions from the latest
			if ( true === as_has_scheduled_action( 'tt4b_trust_signal_collection' ) ) {
				// deletes scheduled actions from previous version
				as_unschedule_all_actions( 'tt4b_trust_signal_collection' );
				as_unschedule_all_actions( 'tt4b_trust_signal_helper' );
			}
			as_enqueue_async_action( 'tt4b_trust_signal_collection' );
			$cronStr = $this->generate_cron_string();
			as_schedule_cron_action( strtotime( 'tomorrow' ), $cronStr, 'tt4b_trust_signal_collection', array(), $currentGroup );
		}
	}

	/**
	 * Retrieves eligibility information from merchants woocommerce store via creation of retrieve_eligibility_helper functions for batches of 20 orders
	 *
	 * @return void
	 */
	public function retrieve_eligibility_information() {
		// only fetch if using woocommerce
		if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
			return;
		}

		$args   = array(
			'post_status' => 'wc-completed',
			'paginate'    => true,
			'limit'       => 100,
		);
		$result = wc_get_orders( $args );
		$pages  = $result->max_num_pages;
		update_option( 'tt4b_mapi_total_gmv', 0 );
		update_option( 'tt4b_mapi_total_orders', 0 );
		update_option( 'tt4b_mapi_tenure', 0 );
		update_option( 'tt4b_eligibility_page_total', $pages );
		$oldest_orders = ( new WC_Order_Query(
			array(
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		) )->get_orders();
		if ( count( $oldest_orders ) > 0 ) {
			$oldest_order_timestamp = $oldest_orders[0]->get_date_created()->getTimestamp();
			$mapi_tenure            = (int) ( ( time() - $oldest_order_timestamp ) / DAY_IN_SECONDS );
			update_option( 'tt4b_mapi_tenure', $mapi_tenure );
		}
		if ( false === as_has_scheduled_action( 'tt4b_trust_signal_helper', array( 'page' => 1 ) ) ) {
			as_enqueue_async_action( 'tt4b_trust_signal_helper', array( 'page' => 1 ) );
		}
	}

	/**
	 * Helper function used to calculate eligibility information in batches of 20
	 *
	 * @param integer $page The page of orders from woocommerce
	 *
	 * @return void
	 */
	public function retrieve_eligibility_helper( $page ) {
		// only fetch if using woocommerce
		if ( ! did_action( 'woocommerce_loaded' ) > 0 ) {
			return;
		}

		$orders = wc_get_orders(
			array(
				'post_status' => 'wc-completed',
				'limit'       => 100,
				'page'        => $page,
			)
		);
		foreach ( $orders as $order ) {
			if ( is_null( $order ) ) {
				break;
			}
			$order_total = $order->get_total();
			if ( $order_total > 0 ) {
				$mapi_total_gmv  = get_option( 'tt4b_mapi_total_gmv' );
				$mapi_total_gmv += intval( $order_total );
				update_option( 'tt4b_mapi_total_gmv', $mapi_total_gmv );
				$mapi_total_orders = get_option( 'tt4b_mapi_total_orders' );
				++$mapi_total_orders;
				update_option( 'tt4b_mapi_total_orders', $mapi_total_orders );
			}
			if ( 0 === count( $orders ) ) {
				break;
			}
		}
		$page_total = get_option( 'tt4b_eligibility_page_total' );
		++$page;
		if ( ( $page <= $page_total ) && ( false === as_has_scheduled_action( 'tt4b_trust_signal_helper', array( 'page' => $page ) ) ) ) {
			as_enqueue_async_action( 'tt4b_trust_signal_helper', array( 'page' => $page ) );
		} else {
			$access_token         = get_option( 'tt4b_access_token' );
			$external_business_id = get_option( 'tt4b_external_business_id' );
			$total_gmv            = intval( get_option( 'tt4b_mapi_total_gmv' ) );
			$total_orders         = intval( get_option( 'tt4b_mapi_total_orders' ) );
			$tenure               = intval( get_option( 'tt4b_mapi_tenure' ) );
			$version              = get_option( 'tt4b_version' );
			$this->update_business_profile( $access_token, $external_business_id, $total_gmv, $total_orders, $tenure, $version );
		}
	}

	/**
	 * Update partner insights data
	 *
	 * @param string $external_data The external data.
	 * @param array  $insights_data Partner insights data payload.
	 *
	 * @return string
	 */
	public function partner_insights_update( $external_data, $insights_data ) {
		$base_url = 'https://biz-api.tiktok.com/plugin/v1.0/partner_insights/update/';
		$url      = $base_url . '?external_data=' . $external_data;
		$args     = array(
			'method'      => 'POST',
			'data_format' => 'body',
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'body'        => json_encode( $insights_data ),
		);
		$this->logger->log_request( $url, $args );
		$response = wp_remote_post( $url, $args );
		$this->logger->log_response( __METHOD__, $response );
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Collect partner insights data
	 *
	 * @param integer $store_tenure Store tenure in days.
	 *
	 * @return array
	 */
	public function collect_partner_insights_data( $store_tenure ) {
		// Get WordPress admin user info for contact details.
		$admin_user = get_user_by( 'email', get_option( 'admin_email' ) );
		$l30_gmv    = $this->get_last_30_days_gmv();
		$l30_orders = $this->get_last_30_days_orders();

		$advertiser_id = get_option( 'tt4b_advertiser_id' ) ?: '';
		$email         = get_option( 'admin_email', '' ) ?: '';
		$phone         = get_option( 'woocommerce_store_phone' ) ?: '';

		// Validate that at least one of advertiser_id, email, phone are not empty
		if ( empty( $advertiser_id ) && empty( $email ) && empty( $phone ) ) {
			$logger = new Logger();
			$logger->log( __METHOD__, 'Partner insights data collection failed: advertiser_id, email, and phone are all empty' );
			return array();
		}

		return array(
			'contact_info'      => array(
				'advertiser_id' => $advertiser_id,
				'email'         => $email,
				'phone'         => $phone, // need to explicitly set default to empty string here or else WP defaults empty string to false
				'first_name'    => $admin_user && $admin_user->first_name ? $admin_user->first_name : '',
				'last_name'     => $admin_user && $admin_user->last_name ? $admin_user->last_name : '',
			),
			'company_info'      => array(
				'registration_country' => $this->get_store_country(),
				'website_url'          => $this->get_website_url(),
				'number_skus'          => $this->get_product_count(),
				'has_google_pixel'     => $this->has_google_analytics(),
				'has_meta_pixel'       => $this->has_facebook_pixel(),
				'store_tenure'         => round( $store_tenure / 365, 1 ), // 'store_tenure' is in years
			),
			'budget_indicators' => array(
				'l30gmv'    => $this->calculate_gmv_tier( $l30_gmv ),
				'l30orders' => $this->calculate_orders_tier( $l30_orders ),
			),
			'plan_type'         => 'defaultplan',
			'other_indicators'  => json_encode(
				array(
					'plugin_version'      => get_option( 'tt4b_version', '1.0.0' ),
					'woocommerce_version' => defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown',
				)
			),
		);
	}

	/**
	 * Get last 30 days' GMV
	 *
	 * @return integer
	 */
	private function get_last_30_days_gmv() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$thirty_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$orders          = wc_get_orders(
			array(
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'date_created' => '>=' . $thirty_days_ago,
				'limit'        => -1,
			)
		);

		$l30_gmv = 0;
		foreach ( $orders as $order ) {
			if ( $order && $order->get_total() > 0 ) {
				$l30_gmv += $order->get_total();
			}
		}

		return intval( $l30_gmv );
	}

	/**
	 * Get last 30 days' order count
	 *
	 * @return integer
	 */
	private function get_last_30_days_orders() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$thirty_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$orders          = wc_get_orders(
			array(
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'date_created' => '>=' . $thirty_days_ago,
				'limit'        => -1,
			)
		);

		return count( $orders );
	}

	/**
	 * Calculate orders tier based on order count
	 *
	 * @param integer $order_count Number of orders.
	 *
	 * @return string
	 */
	private function calculate_orders_tier( $order_count ) {
		if ( $order_count > 50000 ) {
			return 'TIER_10';
		} elseif ( $order_count >= 26000 ) {
			return 'TIER_9';
		} elseif ( $order_count >= 10001 ) {
			return 'TIER_8';
		} elseif ( $order_count >= 5001 ) {
			return 'TIER_7';
		} elseif ( $order_count >= 1001 ) {
			return 'TIER_6';
		} elseif ( $order_count >= 501 ) {
			return 'TIER_5';
		} elseif ( $order_count >= 201 ) {
			return 'TIER_4';
		} elseif ( $order_count >= 51 ) {
			return 'TIER_3';
		} elseif ( $order_count >= 26 ) {
			return 'TIER_2';
		} else {
			return 'TIER_1';
		}
	}

	/**
	 * Get store country
	 *
	 * @return string
	 */
	private function get_store_country() {
		if ( function_exists( 'wc_get_base_location' ) ) {
			$location = wc_get_base_location();
			if ( $location['country'] ) {
				return $location['country'];
			} else {
				return 'default';
			}
		}
		return 'default';
	}

	/**
	 * Get website URL
	 *
	 * @return string
	 */
	private function get_website_url() {
		$url = get_site_url();
		return str_replace( array( 'http://', 'https://' ), '', $url );
	}

	/**
	 * Get product count
	 *
	 * @return integer
	 */
	private function get_product_count() {
		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'limit'  => -1,
					'return' => 'ids',
				)
			);
			return count( $products );
		}
		return 0;
	}

	/**
	 * Check if Google Analytics is present
	 *
	 * @return boolean
	 */
	private function has_google_analytics() {
		// Check for common GA patterns in options or active plugins.
		$ga_options = array( 'googleanalytics_account', 'ga_id', 'google_analytics' );
		foreach ( $ga_options as $option ) {
			if ( get_option( $option ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if Facebook Pixel is present
	 *
	 * @return boolean
	 */
	private function has_facebook_pixel() {
		// Check for common FB pixel patterns.
		$fb_options = array( 'facebook_pixel_id', 'fb_pixel_id' );
		foreach ( $fb_options as $option ) {
			if ( get_option( $option ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Calculate GMV tier based on total GMV
	 *
	 * @param integer $total_gmv Total GMV amount.
	 *
	 * @return string
	 */
	private function calculate_gmv_tier( $total_gmv ) {
		if ( $total_gmv > 300000 ) {
			return 'TIER_10';
		} elseif ( $total_gmv >= 200001 ) {
			return 'TIER_9';
		} elseif ( $total_gmv >= 100001 ) {
			return 'TIER_8';
		} elseif ( $total_gmv >= 50001 ) {
			return 'TIER_7';
		} elseif ( $total_gmv >= 20001 ) {
			return 'TIER_6';
		} elseif ( $total_gmv >= 15001 ) {
			return 'TIER_5';
		} elseif ( $total_gmv >= 10001 ) {
			return 'TIER_4';
		} elseif ( $total_gmv >= 5001 ) {
			return 'TIER_3';
		} elseif ( $total_gmv >= 1001 ) {
			return 'TIER_2';
		} else {
			return 'TIER_1';
		}
	}

	/**
	 * TTS Disconnect
	 *
	 * @param string $external_data The external data.
	 *
	 * @return void
	 */
	public function tts_shop_disconnect( $external_data ) {
		$base_url = 'https://business-api.tiktok.com/tbp/v2.0/shop/connection/disconnect';
		$url      = $base_url . '?external_data=' . $external_data;
		$args     = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);
		$this->logger->log_request( $url, $args );
		$response = wp_remote_post( $url, $args );
		$this->logger->log_response( __METHOD__, $response );
	}
}
