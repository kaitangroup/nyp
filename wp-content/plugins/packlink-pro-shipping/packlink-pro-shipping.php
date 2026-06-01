<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

/*
 * Plugin Name: Packlink PRO Shipping
 * Plugin URI: https://en.wordpress.org/plugins/packlink-pro-shipping/
 * Description: Save up to 70% on your shipping costs. No fixed fees, no minimum shipping volume required. Manage all your shipments in a single platform.
 * Version: 4.1.2
 * Author: Packlink Shipping S.L.
 * Author URI: https://pro.packlink.es/
 * License: GPL
 * Text Domain: packlink-pro-shipping
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 10.3.6
 */

use Packlink\WooCommerce\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once trailingslashit( __DIR__ ) . 'inc/php8-polyfills.php';
require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
require_once trailingslashit( __DIR__ ) . 'inc/autoload.php';

global $wpdb;

Plugin::instance( $wpdb, __FILE__ );
