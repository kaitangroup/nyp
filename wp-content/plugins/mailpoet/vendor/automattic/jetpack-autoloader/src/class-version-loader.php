<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
class Version_Loader {
 private $version_selector;
 private $classmap;
 private $psr4_map;
 private $filemap;
 public function __construct( $version_selector, $classmap, $psr4_map, $filemap ) {
 $this->version_selector = $version_selector;
 $this->classmap = $classmap;
 $this->psr4_map = $psr4_map;
 $this->filemap = $filemap;
 }
 public function get_class_map() {
 return $this->classmap;
 }
 public function get_psr4_map() {
 return $this->psr4_map;
 }
 public function find_class_file( $class_name ) {
 $data = $this->select_newest_file(
 $this->classmap[ $class_name ] ?? null,
 $this->find_psr4_file( $class_name )
 );
 if ( ! isset( $data ) ) {
 return null;
 }
 return $data['path'];
 }
 public function load_filemap() {
 if ( empty( $this->filemap ) ) {
 return;
 }
 foreach ( $this->filemap as $file_identifier => $file_data ) {
 if ( empty( $GLOBALS['__composer_autoload_files'][ $file_identifier ] ) ) {
 require_once $file_data['path'];
 $GLOBALS['__composer_autoload_files'][ $file_identifier ] = true;
 }
 }
 }
 private function select_newest_file( $classmap_data, $psr4_data ) {
 if ( ! isset( $classmap_data ) ) {
 return $psr4_data;
 } elseif ( ! isset( $psr4_data ) ) {
 return $classmap_data;
 }
 if ( $this->version_selector->is_version_update_required( $classmap_data['version'], $psr4_data['version'] ) ) {
 return $psr4_data;
 }
 return $classmap_data;
 }
 private function find_psr4_file( $class_name ) {
 if ( empty( $this->psr4_map ) ) {
 return null;
 }
 // Don't bother with classes that have no namespace.
 $class_index = strrpos( $class_name, '\\' );
 if ( ! $class_index ) {
 return null;
 }
 $class_for_path = str_replace( '\\', '/', $class_name );
 // Search for the namespace by iteratively cutting off the last segment until
 // we find a match. This allows us to check the most-specific namespaces
 // first as well as minimize the amount of time spent looking.
 for (
 $class_namespace = substr( $class_name, 0, $class_index );
 ! empty( $class_namespace );
 $class_namespace = substr( $class_namespace, 0, strrpos( $class_namespace, '\\' ) )
 ) {
 $namespace = $class_namespace . '\\';
 if ( ! isset( $this->psr4_map[ $namespace ] ) ) {
 continue;
 }
 $data = $this->psr4_map[ $namespace ];
 foreach ( $data['path'] as $path ) {
 $path .= '/' . substr( $class_for_path, strlen( $namespace ) ) . '.php';
 if ( file_exists( $path ) ) {
 return array(
 'version' => $data['version'],
 'path' => $path,
 );
 }
 }
 }
 return null;
 }
}
