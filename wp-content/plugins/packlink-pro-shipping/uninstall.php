<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * Uninstalling Packlink PRO Shipping deletes all user data.
 *
 * @package Packlink
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
require_once trailingslashit( __DIR__ ) . 'inc/autoload.php';

global $wpdb;

Packlink\WooCommerce\Components\Bootstrap_Component::init();

$plugin = new \Packlink\WooCommerce\Plugin( $wpdb, __FILE__ );
$plugin->uninstall();
