<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
class Path_Processor {
 public function tokenize_path_constants( $path ) {
 $path = wp_normalize_path( $path );
 $constants = $this->get_normalized_constants();
 foreach ( $constants as $constant => $constant_path ) {
 $len = strlen( $constant_path );
 if ( substr( $path, 0, $len ) !== $constant_path ) {
 continue;
 }
 return substr_replace( $path, '{{' . $constant . '}}', 0, $len );
 }
 return $path;
 }
 public function untokenize_path_constants( $tokenized_path ) {
 $tokenized_path = wp_normalize_path( $tokenized_path );
 $constants = $this->get_normalized_constants();
 foreach ( $constants as $constant => $constant_path ) {
 $constant = '{{' . $constant . '}}';
 $len = strlen( $constant );
 if ( substr( $tokenized_path, 0, $len ) !== $constant ) {
 continue;
 }
 return $this->get_real_path( substr_replace( $tokenized_path, $constant_path, 0, $len ) );
 }
 return $tokenized_path;
 }
 public function find_directory_with_autoloader( $file, $directories_to_check ) {
 $file = wp_normalize_path( $file );
 if ( ! $this->is_absolute_path( $file ) ) {
 $file = $this->find_absolute_plugin_path( $file, $directories_to_check );
 if ( ! isset( $file ) ) {
 return false;
 }
 }
 // We need the real path for consistency with __DIR__ paths.
 $file = $this->get_real_path( $file );
 // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 $directory = @is_file( $file ) ? dirname( $file ) : $file;
 if ( ! @is_file( $directory . '/vendor/composer/jetpack_autoload_classmap.php' ) ) {
 return false;
 }
 // phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
 return $directory;
 }
 private function get_normalized_constants() {
 $raw_constants = array(
 // Order the constants from most-specific to least-specific.
 'WP_PLUGIN_DIR',
 'WPMU_PLUGIN_DIR',
 'WP_CONTENT_DIR',
 'ABSPATH',
 );
 $constants = array();
 foreach ( $raw_constants as $raw ) {
 if ( ! defined( $raw ) ) {
 continue;
 }
 $path = wp_normalize_path( constant( $raw ) );
 if ( isset( $path ) ) {
 $constants[ $raw ] = $path;
 }
 }
 return $constants;
 }
 private function is_absolute_path( $path ) {
 if ( empty( $path ) || 0 === strlen( $path ) || '.' === $path[0] ) {
 return false;
 }
 // Absolute paths on Windows may begin with a drive letter.
 if ( preg_match( '/^[a-zA-Z]:[\/\\\\]/', $path ) ) {
 return true;
 }
 // A path starting with / or \ is absolute; anything else is relative.
 return ( '/' === $path[0] || '\\' === $path[0] );
 }
 private function find_absolute_plugin_path( $normalized_path, $directories_to_check ) {
 // We're only able to find the absolute path for plugin/theme PHP files.
 if ( ! is_string( $normalized_path ) || '.php' !== substr( $normalized_path, -4 ) ) {
 return null;
 }
 foreach ( $directories_to_check as $directory ) {
 $normalized_check = wp_normalize_path( trailingslashit( $directory ) ) . $normalized_path;
 // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
 if ( @is_file( $normalized_check ) ) {
 return $normalized_check;
 }
 }
 return null;
 }
 private function get_real_path( $path ) {
 // We want to resolve symbolic links for consistency with __DIR__ paths.
 // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
 $real_path = @realpath( $path );
 if ( false === $real_path ) {
 // Let the autoloader deal with paths that don't exist.
 $real_path = $path;
 }
 // Using realpath will make it platform-specific so we must normalize it after.
 if ( $path !== $real_path ) {
 $real_path = wp_normalize_path( $real_path );
 }
 return $real_path;
 }
}
