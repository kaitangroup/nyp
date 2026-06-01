<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
class Plugin_Locator {
 private $path_processor;
 public function __construct( $path_processor ) {
 $this->path_processor = $path_processor;
 }
 public function find_current_plugin() {
 // Escape from `vendor/__DIR__` to root plugin directory.
 $plugin_directory = dirname( __DIR__, 2 );
 // Use the path processor to ensure that this is an autoloader we're referencing.
 $path = $this->path_processor->find_directory_with_autoloader( $plugin_directory, array() );
 if ( false === $path ) {
 throw new \RuntimeException( 'Failed to locate plugin ' . $plugin_directory );
 }
 return $path;
 }
 public function find_using_option( $option_name, $site_option = false ) {
 $raw = $site_option ? get_site_option( $option_name ) : get_option( $option_name );
 if ( false === $raw ) {
 return array();
 }
 return $this->convert_plugins_to_paths( $raw );
 }
 public function find_using_request_action( $allowed_actions ) {
 if ( empty( $_REQUEST['_wpnonce'] ) ) {
 return array();
 }
 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated just below.
 $action = isset( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : false;
 if ( ! in_array( $action, $allowed_actions, true ) ) {
 return array();
 }
 $plugin_slugs = array();
 switch ( $action ) {
 case 'activate':
 case 'deactivate':
 if ( empty( $_REQUEST['plugin'] ) ) {
 break;
 }
 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by convert_plugins_to_paths.
 $plugin_slugs[] = wp_unslash( $_REQUEST['plugin'] );
 break;
 case 'activate-selected':
 case 'deactivate-selected':
 if ( empty( $_REQUEST['checked'] ) ) {
 break;
 }
 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by convert_plugins_to_paths.
 $plugin_slugs = wp_unslash( $_REQUEST['checked'] );
 break;
 }
 return $this->convert_plugins_to_paths( $plugin_slugs );
 }
 private function convert_plugins_to_paths( $plugins ) {
 if ( ! is_array( $plugins ) || empty( $plugins ) ) {
 return array();
 }
 // We're going to look for plugins in the standard directories.
 $path_constants = array( WP_PLUGIN_DIR, WPMU_PLUGIN_DIR );
 $plugin_paths = array();
 foreach ( $plugins as $key => $value ) {
 $path = $this->path_processor->find_directory_with_autoloader( $key, $path_constants );
 if ( $path ) {
 $plugin_paths[] = $path;
 }
 $path = $this->path_processor->find_directory_with_autoloader( $value, $path_constants );
 if ( $path ) {
 $plugin_paths[] = $path;
 }
 }
 return $plugin_paths;
 }
}
