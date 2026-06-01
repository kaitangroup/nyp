<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
use Automattic\Jetpack\Autoloader\AutoloadGenerator;
class Autoloader_Handler {
 private $php_autoloader;
 private $hook_manager;
 private $manifest_reader;
 private $version_selector;
 public function __construct( $php_autoloader, $hook_manager, $manifest_reader, $version_selector ) {
 $this->php_autoloader = $php_autoloader;
 $this->hook_manager = $hook_manager;
 $this->manifest_reader = $manifest_reader;
 $this->version_selector = $version_selector;
 }
 public function is_initializing() {
 // If no version has been set it means that no autoloader has started initializing yet.
 global $jetpack_autoloader_latest_version;
 if ( ! isset( $jetpack_autoloader_latest_version ) ) {
 return false;
 }
 // When the version is set but the classmap is not it ALWAYS means that this is the
 // latest autoloader and is being included by an older one.
 global $jetpack_packages_classmap;
 if ( empty( $jetpack_packages_classmap ) ) {
 return true;
 }
 // Version 2.4.0 added a new global and altered the reset semantics. We need to check
 // the other global as well since it may also point at initialization.
 // Note: We don't need to check for the class first because every autoloader that
 // will set the latest version global requires this class in the classmap.
 $replacing_version = $jetpack_packages_classmap[ AutoloadGenerator::class ]['version'];
 if ( $this->version_selector->is_dev_version( $replacing_version ) || version_compare( $replacing_version, '2.4.0.0', '>=' ) ) {
 global $jetpack_autoloader_loader;
 if ( ! isset( $jetpack_autoloader_loader ) ) {
 return true;
 }
 }
 return false;
 }
 public function activate_autoloader( $plugins ) {
 global $jetpack_packages_psr4;
 $jetpack_packages_psr4 = array();
 $this->manifest_reader->read_manifests( $plugins, 'vendor/composer/jetpack_autoload_psr4.php', $jetpack_packages_psr4 );
 global $jetpack_packages_classmap;
 $jetpack_packages_classmap = array();
 $this->manifest_reader->read_manifests( $plugins, 'vendor/composer/jetpack_autoload_classmap.php', $jetpack_packages_classmap );
 global $jetpack_packages_filemap;
 $jetpack_packages_filemap = array();
 $this->manifest_reader->read_manifests( $plugins, 'vendor/composer/jetpack_autoload_filemap.php', $jetpack_packages_filemap );
 $loader = new Version_Loader(
 $this->version_selector,
 $jetpack_packages_classmap,
 $jetpack_packages_psr4,
 $jetpack_packages_filemap
 );
 $this->php_autoloader->register_autoloader( $loader );
 // Now that the autoloader is active we can load the filemap.
 $loader->load_filemap();
 }
 public function reset_autoloader() {
 $this->php_autoloader->unregister_autoloader();
 $this->hook_manager->reset();
 // Clear all of the autoloader globals so that older autoloaders don't do anything strange.
 global $jetpack_autoloader_latest_version;
 $jetpack_autoloader_latest_version = null;
 global $jetpack_packages_classmap;
 $jetpack_packages_classmap = array(); // Must be array to avoid exceptions in old autoloaders!
 global $jetpack_packages_psr4;
 $jetpack_packages_psr4 = array(); // Must be array to avoid exceptions in old autoloaders!
 global $jetpack_packages_filemap;
 $jetpack_packages_filemap = array(); // Must be array to avoid exceptions in old autoloaders!
 }
}
