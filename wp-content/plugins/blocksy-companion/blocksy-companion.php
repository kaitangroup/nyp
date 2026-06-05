<?php

/*
Plugin Name: Blocksy Companion
Description: This plugin is the companion for the Blocksy theme, it runs and adds its enhacements only if the Blocksy theme is installed and active.
Version: 2.1.44
Author: CreativeThemes
Author URI: https://creativethemes.com
Text Domain: blocksy-companion
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.5
Requires PHP: 7.0
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
register_activation_hook( __FILE__, function () {
    if ( class_exists( '\\Blocksy\\Plugin' ) && !function_exists( 'blocksy_companion_fs' ) ) {
        $to_deactivate = plugin_basename( str_replace( '-pro/', '/', __FILE__ ) );
        if ( is_plugin_active( $to_deactivate ) ) {
            deactivate_plugins( $to_deactivate );
        }
    }
    if ( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] && isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) {
        return;
    }
    add_option( 'blocksy_companion_activation_redirect', wp_get_current_user()->ID );
} );
if ( function_exists( 'blocksy_companion_fs' ) || class_exists( '\\Blocksy\\Plugin' ) ) {
    if ( function_exists( 'blocksy_companion_fs' ) ) {
        blocksy_companion_fs()->set_basename( false, __FILE__ );
    }
} else {
    if ( !function_exists( 'blocksy_companion_fs' ) && file_exists( dirname( __FILE__ ) . '/vendor/freemius/start.php' ) && (is_admin() || wp_doing_cron() || defined( 'WP_CLI' ) && WP_CLI || isset( $_REQUEST['customize_theme'] )) ) {
        global $blocksy_companion_fs;
        if ( !isset( $blocksy_companion_fs ) ) {
            if ( !defined( 'WP_FS__PRODUCT_5115_MULTISITE' ) ) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Freemius requirement
                define( 'WP_FS__PRODUCT_5115_MULTISITE', true );
            }
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $blocksy_has_account = true;
            $blocksy_fs_instance = \Freemius::instance( 5115, 'blocksy-companion', true );
            $blocksy_active_extensions = get_option( 'blocksy_active_extensions', [] );
            if ( !is_array( $blocksy_active_extensions ) ) {
                $blocksy_active_extensions = [];
            }
            if ( in_array( 'white-label', $blocksy_active_extensions ) && ($blocksy_fs_instance->is_plan( 'agency' ) || $blocksy_fs_instance->is_plan( 'agency_v2' )) ) {
                $blocksy_wl_settings = apply_filters( 'blocksy:ext:white-label:settings', get_option( 'blocksy_ext_white_label_settings', [] ) );
                if ( $blocksy_wl_settings && isset( $blocksy_wl_settings['hide_billing_account'] ) && $blocksy_wl_settings['hide_billing_account'] && !is_multisite() ) {
                    $blocksy_has_account = false;
                }
            }
            $blocksy_companion_fs = fs_dynamic_init( array(
                'id'               => '5115',
                'slug'             => 'blocksy-companion',
                'premium_slug'     => 'blocksy-companion-pro',
                'type'             => 'plugin',
                'public_key'       => 'pk_b00a5cbae90b2e948015a7d0710f5',
                'premium_suffix'   => 'PRO',
                'is_premium'       => false,
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'menu'             => ( true ? [
                    'slug'    => 'ct-dashboard',
                    'support' => false,
                    'contact' => false,
                    'pricing' => false,
                    'account' => $blocksy_has_account,
                ] : [
                    'support' => false,
                    'contact' => false,
                    'pricing' => false,
                    'account' => false,
                ] ),
                'is_live'          => true,
                'is_org_compliant' => true,
            ) );
            function blocksy_companion_fs() {
                global $blocksy_companion_fs;
                // if (! is_admin()) {
                // throw new Error('Called in frontend!');
                // }
                return $blocksy_companion_fs;
            }

            blocksy_companion_fs();
            do_action( 'blocksy_companion_fs_loaded' );
        }
    }
    define( 'BLOCKSY__FILE__', __FILE__ );
    define( 'BLOCKSY_PLUGIN_BASE', plugin_basename( BLOCKSY__FILE__ ) );
    define( 'BLOCKSY_PATH', plugin_dir_path( BLOCKSY__FILE__ ) );
    define( 'BLOCKSY_URL', plugin_dir_url( BLOCKSY__FILE__ ) );
    if ( !version_compare( PHP_VERSION, '7.0', '>=' ) ) {
        add_action( 'admin_notices', 'blocksy_companion_fail_php_version' );
    } elseif ( !version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ) {
        add_action( 'admin_notices', 'blocksy_companion_fail_wp_version' );
    } else {
        require BLOCKSY_PATH . 'plugin.php';
    }
    /**
     * Blocksy admin notice for minimum PHP version.
     *
     * Warning when the site doesn't have the minimum required PHP version.
     */
    function blocksy_companion_fail_php_version() {
        /* translators: %s: PHP version */
        $message = sprintf( esc_html__( 'Blocksy requires PHP version %s+, plugin is currently NOT RUNNING.', 'blocksy-companion' ), '7.0' );
        $html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
        echo wp_kses_post( $html_message );
    }

    /**
     * Blocksy admin notice for minimum WordPress version.
     *
     * Warning when the site doesn't have the minimum required WordPress version.
     */
    function blocksy_companion_fail_wp_version() {
        /* translators: %s: WordPress version */
        $message = sprintf( esc_html__( 'Blocksy requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'blocksy-companion' ), '5.0' );
        $html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
        echo wp_kses_post( $html_message );
    }

}