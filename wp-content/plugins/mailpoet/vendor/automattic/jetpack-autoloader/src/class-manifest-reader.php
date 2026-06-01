<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
class Manifest_Reader {
 private $version_selector;
 public function __construct( $version_selector ) {
 $this->version_selector = $version_selector;
 }
 public function read_manifests( $plugin_paths, $manifest_path, &$path_map ) {
 $file_paths = array_map(
 function ( $path ) use ( $manifest_path ) {
 return trailingslashit( $path ) . $manifest_path;
 },
 $plugin_paths
 );
 foreach ( $file_paths as $path ) {
 $this->register_manifest( $path, $path_map );
 }
 return $path_map;
 }
 protected function register_manifest( $manifest_path, &$path_map ) {
 if ( ! is_readable( $manifest_path ) ) {
 return;
 }
 $manifest = require $manifest_path;
 if ( ! is_array( $manifest ) ) {
 return;
 }
 foreach ( $manifest as $key => $data ) {
 $this->register_record( $key, $data, $path_map );
 }
 }
 protected function register_record( $key, $data, &$path_map ) {
 if ( isset( $path_map[ $key ]['version'] ) ) {
 $selected_version = $path_map[ $key ]['version'];
 } else {
 $selected_version = null;
 }
 if ( $this->version_selector->is_version_update_required( $selected_version, $data['version'] ) ) {
 $path_map[ $key ] = array(
 'version' => $data['version'],
 'path' => $data['path'],
 );
 }
 }
}
