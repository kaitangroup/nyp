<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;
if (!defined('ABSPATH')) exit;
class Container {
 protected array $services = array();
 protected array $instances = array();
 public function __unserialize( array $data ): void {
 throw new \Exception( 'Deserialization of Container is not allowed for security reasons.' );
 }
 public function set( string $name, callable $callback ): void {
 $this->services[ $name ] = $callback;
 }
 public function get( string $name ): object {
 // Check if the service is already instantiated.
 if ( isset( $this->instances[ $name ] ) ) {
 $instance = $this->instances[ $name ];
 return $instance;
 }
 // Check if the service is registered.
 if ( ! isset( $this->services[ $name ] ) ) {
 throw new \Exception( esc_html( "Service not found: $name" ) );
 }
 $instance = $this->services[ $name ]( $this );
 $this->instances[ $name ] = $instance;
 return $instance;
 }
}
