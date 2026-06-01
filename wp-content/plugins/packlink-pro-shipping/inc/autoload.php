<?php
/**
 * Packlink PRO Shipping WooCommerce Integration.
 *
 * @package Packlink
 */

try {
	spl_autoload_register( 'packlink_wc_namespace_autoload' );
} catch ( Exception $e ) {
	wp_die( esc_html( $e->getMessage() ) );
}

/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin by looking at the $class_name parameter being passed as an argument.
 *
 * @param string $class_name Name of the class.
 */
function packlink_wc_namespace_autoload( $class_name ) {
	if ( false === strpos( $class_name, 'Packlink\\WooCommerce\\' ) ) {
		return;
	}

	// Split the class name into an array to read the namespace and class.
	$path      = explode( '\\', $class_name );
	$namespace = '';
	$file_name = '';
	for ( $i = count( $path ) - 1; $i > 0; $i -- ) {
		$current = $path[ $i ];
		$current = str_ireplace( '_', '-', $current );
		if ( count( $path ) - 1 === $i ) {
			$current   = strtolower( $current );
			$file_name = "class-$current.php";
		} else {
			if ( 'WooCommerce' === $current ) {
				// the name of the plugin folder.
				$current = basename( dirname( __DIR__ ) );
			}

			$namespace = '/' . $current . $namespace;
		}
	}

	$file_path  = trailingslashit( dirname( dirname( __DIR__ ) ) . $namespace );
	$file_path .= $file_name;
	// If the file exists in the specified path, then include it.
	if ( file_exists( $file_path ) ) {
		include_once $file_path;
	}
}
