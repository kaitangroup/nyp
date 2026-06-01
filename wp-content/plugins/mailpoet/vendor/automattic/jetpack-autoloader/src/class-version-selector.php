<?php
if (!defined('ABSPATH')) exit;
 // phpcs:ignore
class Version_Selector {
 public function is_version_update_required( $selected_version, $compare_version ) {
 $use_dev_versions = defined( 'JETPACK_AUTOLOAD_DEV' ) && JETPACK_AUTOLOAD_DEV;
 if ( $selected_version === null ) {
 return true;
 }
 if ( $use_dev_versions && $this->is_dev_version( $selected_version ) ) {
 return false;
 }
 if ( $this->is_dev_version( $compare_version ) ) {
 if ( $use_dev_versions ) {
 return true;
 } else {
 return false;
 }
 }
 if ( version_compare( $selected_version, $compare_version, '<' ) ) {
 return true;
 }
 return false;
 }
 public function is_dev_version( $version ) {
 if ( 'dev-' === substr( $version, 0, 4 ) || '9999999-dev' === $version ) {
 return true;
 }
 return false;
 }
}
