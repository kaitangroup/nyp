<?php
/**
 * Polyfills for PHP functions removed in PHP 8.0 that are still referenced
 * by bundled third-party PDF libraries (setasign/fpdf, setasign/fpdi).
 *
 * Kept in the plugin instead of patching vendor/ so composer install/update
 * does not wipe the fix.
 *
 * @package Packlink\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_magic_quotes_runtime' ) ) {
	function get_magic_quotes_runtime() {
		return false;
	}
}

if ( ! function_exists( 'set_magic_quotes_runtime' ) ) {
	function set_magic_quotes_runtime( $new_setting ) {
		return true;
	}
}

if ( ! function_exists( 'get_magic_quotes_gpc' ) ) {
	function get_magic_quotes_gpc() {
		return false;
	}
}

if ( ! function_exists( 'each' ) ) {
	function each( &$array ) {
		$key = key( $array );
		if ( null === $key ) {
			return false;
		}
		$value = current( $array );
		next( $array );
		return array(
			0       => $key,
			'key'   => $key,
			1       => $value,
			'value' => $value,
		);
	}
}
