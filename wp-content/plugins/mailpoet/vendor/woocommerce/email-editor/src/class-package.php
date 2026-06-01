<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;
if (!defined('ABSPATH')) exit;
defined( 'ABSPATH' ) || exit;
class Package {
 const VERSION = '0.1.0';
 public static function init() {
 Email_Editor_Container::init();
 }
 public static function get_version() {
 return self::VERSION;
 }
 public static function get_path() {
 return dirname( __DIR__ );
 }
}
