<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;
if (!defined('ABSPATH')) exit;
class Preset_Variable_Resolver {
 private static function to_css_variable_name( string $value ): string {
 return '--wp--' . str_replace( '|', '--', str_replace( 'var:', '', $value ) );
 }
 public static function is_preset_reference( string $value ): bool {
 return strpos( $value, 'var:preset|' ) === 0;
 }
 public static function resolve( string $value, array $variables_map ): string {
 if ( empty( $variables_map ) || ! self::is_preset_reference( $value ) ) {
 return $value;
 }
 $css_var_name = self::to_css_variable_name( $value );
 return $variables_map[ $css_var_name ] ?? $value;
 }
 public static function to_css_var( string $value ): string {
 if ( ! self::is_preset_reference( $value ) ) {
 return $value;
 }
 return 'var(' . self::to_css_variable_name( $value ) . ')';
 }
}
