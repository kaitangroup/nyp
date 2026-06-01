<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
use Automattic\Jetpack\Autoloader\AutoloadGenerator;
class Autoloader_Locator {
 private $version_selector;
 public function __construct( $version_selector ) {
 $this->version_selector = $version_selector;
 }
 public function find_latest_autoloader( $plugin_paths, &$latest_version ) {
 $latest_plugin = null;
 foreach ( $plugin_paths as $plugin_path ) {
 $version = $this->get_autoloader_version( $plugin_path );
 if ( ! $version || ! $this->version_selector->is_version_update_required( $latest_version, $version ) ) {
 continue;
 }
 $latest_version = $version;
 $latest_plugin = $plugin_path;
 }
 return $latest_plugin;
 }
 public function get_autoloader_path( $plugin_path ) {
 return trailingslashit( $plugin_path ) . 'vendor/autoload_packages.php';
 }
 public function get_autoloader_version( $plugin_path ) {
 $classmap = trailingslashit( $plugin_path ) . 'vendor/composer/jetpack_autoload_classmap.php';
 if ( ! file_exists( $classmap ) ) {
 return null;
 }
 $classmap = require $classmap;
 if ( isset( $classmap[ AutoloadGenerator::class ] ) ) {
 return $classmap[ AutoloadGenerator::class ]['version'];
 }
 return null;
 }
}
