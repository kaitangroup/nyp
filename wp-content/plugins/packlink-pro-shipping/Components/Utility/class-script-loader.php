<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

namespace Packlink\WooCommerce\Components\Utility;

/**
 * Class Script_Loader.
 *
 * @package Packlink\WooCommerce\Components\Utility
 */
class Script_Loader {
	/**
	 * Loads javascript files to template rendering.
	 *
	 * @param array $scripts Key-value array where the key is the name of the script and the value is relative path.
	 * @param bool  $in_footer Indicates whether to load scripts in the footer.
	 */
	public static function load_js( $scripts, $in_footer = false ) {
		self::load( $scripts, $in_footer, true );
	}

	/**
	 * Loads CSS files to template rendering.
	 *
	 * @param array $scripts Key-value array where the key is the name of the script and the value is relative path.
	 */
	public static function load_css( $scripts ) {
		self::load( $scripts );
	}

	/**
	 * Loads javascript files to template rendering.
	 *
	 * @param array $files $scripts Key-value array where the key is the name of the script and the value is relative path.
	 * @param bool  $in_footer Indicates whether to load scripts in the footer.
	 * @param bool  $is_js Indicates type of files.
	 */
	private static function load( $files, $in_footer = false, $is_js = false ) {
		$base_url = Shop_Helper::get_plugin_base_url() . 'resources/';
		$version  = Shop_Helper::get_plugin_version();
		foreach ( $files as $file_path ) {
			$name = substr( $file_path, strrpos( '/', $file_path ) );
			if ( $is_js ) {
				wp_enqueue_script( $name, $base_url . $file_path, array(), $version, $in_footer );
			} else {
				wp_enqueue_style( $name, $base_url . $file_path, array(), $version );
			}
		}
	}
}
