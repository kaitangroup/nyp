<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use WP_Style_Engine;
class Styles_Helper {
 public static $empty_block_styles = array(
 'css' => '',
 'declarations' => array(),
 'classnames' => '',
 );
 public static function parse_value( $value ): float {
 // Handle numeric values.
 if ( is_numeric( $value ) ) {
 return (float) $value;
 }
 // Handle string values.
 if ( is_string( $value ) ) {
 if ( preg_match( '/^\s*(-?\d+(?:\.\d+)?)/', $value, $m ) ) {
 return (float) $m[1];
 }
 }
 return 0.0;
 }
 public static function parse_styles_to_array( string $styles ): array {
 $styles = explode( ';', $styles );
 $parsed_styles = array();
 foreach ( $styles as $style ) {
 $style = explode( ':', $style, 2 );
 if ( count( $style ) === 2 ) {
 $parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
 }
 }
 return $parsed_styles;
 }
 public static function get_normalized_block_styles( array $block_attributes, Rendering_Context $rendering_context ): array {
 $normalized_colors = array_filter(
 array(
 'color' => array_filter(
 array(
 'background' => isset( $block_attributes['backgroundColor'] ) && $block_attributes['backgroundColor']
 ? $rendering_context->translate_slug_to_color( $block_attributes['backgroundColor'] )
 : null,
 'text' => isset( $block_attributes['textColor'] ) && $block_attributes['textColor']
 ? $rendering_context->translate_slug_to_color( $block_attributes['textColor'] )
 : null,
 )
 ),
 'border' => array_filter(
 array(
 'color' => isset( $block_attributes['borderColor'] ) && $block_attributes['borderColor']
 ? $rendering_context->translate_slug_to_color( $block_attributes['borderColor'] )
 : null,
 )
 ),
 )
 );
 return array_replace_recursive(
 $normalized_colors,
 $block_attributes['style'] ?? array()
 );
 }
 public static function get_styles_from_block( array $block_styles, $skip_convert_vars = false ) {
 $unsupported_props = array(
 'margin' => array( 'spacing', 'margin' ),
 );
 $unsupported_props = apply_filters( 'woocommerce_email_editor_styles_unsupported_props', $unsupported_props );
 foreach ( $unsupported_props as $path ) {
 if ( ! is_array( $path ) || count( $path ) === 0 ) {
 continue;
 }
 $pointer = & $block_styles;
 $last_key = array_pop( $path );
 foreach ( $path as $segment ) {
 if ( ! is_string( $segment ) && ! is_int( $segment ) ) {
 continue 2;
 }
 if ( ! array_key_exists( $segment, $pointer ) || ! is_array( $pointer[ $segment ] ) ) {
 continue 2;
 }
 $pointer = & $pointer[ $segment ];
 }
 if ( ( is_string( $last_key ) || is_int( $last_key ) ) && array_key_exists( $last_key, $pointer ) ) {
 unset( $pointer[ $last_key ] );
 }
 }
 return wp_parse_args(
 wp_style_engine_get_styles( $block_styles, array( 'convert_vars_to_classnames' => $skip_convert_vars ) ),
 self::$empty_block_styles
 );
 }
 public static function extend_block_styles( array $block_styles, array $css_declarations ) {
 // Ensure block_styles has the required WP_Style_Engine structure.
 if ( ! isset( $block_styles['declarations'] ) || ! is_array( $block_styles['declarations'] ) ) {
 $block_styles = self::$empty_block_styles;
 }
 $block_styles['declarations'] = array_merge( $block_styles['declarations'], $css_declarations );
 $block_styles['css'] = WP_Style_Engine::compile_css( $block_styles['declarations'], '' );
 return $block_styles;
 }
 public static function get_block_styles( array $block_attributes, Rendering_Context $rendering_context, array $properties ) {
 $styles = self::get_normalized_block_styles( $block_attributes, $rendering_context );
 $filtered_styles = array();
 $style_mappings = array(
 'spacing' => array( 'spacing' ),
 'padding' => array( 'spacing', 'padding' ),
 'margin' => array( 'spacing', 'margin' ),
 'border' => array( 'border' ),
 'border-width' => array( 'border', 'width' ),
 'border-style' => array( 'border', 'style' ),
 'border-radius' => array( 'border', 'radius' ),
 'border-color' => array( 'border', 'color' ),
 'background' => array( 'background' ),
 'background-color' => array( 'color', 'background' ),
 'color' => array( 'color', 'text' ),
 'typography' => array( 'typography' ),
 'font-size' => array( 'typography', 'fontSize' ),
 'font-family' => array( 'typography', 'fontFamily' ),
 'font-weight' => array( 'typography', 'fontWeight' ),
 );
 foreach ( $properties as $property ) {
 if ( ! isset( $style_mappings[ $property ] ) ) {
 continue;
 }
 $style_pointer = $styles;
 foreach ( $style_mappings[ $property ] as $path_segment ) {
 if ( ! isset( $style_pointer[ $path_segment ] ) ) {
 continue 2;
 }
 $style_pointer = $style_pointer[ $path_segment ];
 }
 $filtered_styles_pointer = & $filtered_styles;
 foreach ( $style_mappings[ $property ] as $path_index => $path_segment ) {
 if ( count( $style_mappings[ $property ] ) - 1 === $path_index ) {
 $filtered_styles_pointer[ $path_segment ] = $style_pointer;
 break;
 }
 if ( ! isset( $filtered_styles_pointer[ $path_segment ] ) || ! is_array( $filtered_styles_pointer[ $path_segment ] ) ) {
 $filtered_styles_pointer[ $path_segment ] = array();
 }
 $filtered_styles_pointer = & $filtered_styles_pointer[ $path_segment ];
 }
 }
 $additional_css_declarations = array_filter(
 array_intersect_key(
 array(
 'text-align' => $block_attributes['textAlign'] ?? null,
 ),
 array_flip( $properties )
 )
 );
 $styles = count( $filtered_styles ) > 0 ? self::get_styles_from_block( $filtered_styles ) : self::$empty_block_styles;
 return self::extend_block_styles( $styles, $additional_css_declarations );
 }
 public static function convert_to_px( string $input, bool $use_fallback = true, ?int $base_font_size = 16 ): ?string {
 $fallback = $use_fallback ? $base_font_size . 'px' : null;
 if ( ! $input ) {
 return $fallback;
 }
 $input = trim( $input );
 // Validate input against potentially malicious values.
 if ( preg_match( '/[<>"\']/', $input ) ) {
 return $fallback;
 }
 if ( str_ends_with( $input, 'px' ) ) {
 // If already in px, return as is.
 return $input;
 }
 if ( str_ends_with( $input, 'rem' ) || str_ends_with( $input, 'em' ) ) {
 // Convert rem/em to px (assuming 16px base).
 $value = (float) str_replace( array( 'rem', 'em' ), '', $input );
 return round( $value * $base_font_size ) . 'px';
 }
 if ( str_ends_with( $input, '%' ) ) {
 // Convert percentage to px (assuming 16px base).
 $value = (float) str_replace( '%', '', $input );
 return round( ( $value / 100 ) * $base_font_size ) . 'px';
 }
 if ( is_numeric( $input ) ) {
 // If it's just a number, assume px.
 return $input . 'px';
 }
 return $fallback;
 }
 public static function remove_css_unit( string $input ): string {
 $units = array( 'px', 'pt', 'pc', 'rem', 'em', 'vmin', 'vmax', '%', 'vh', 'vw', 'ex', 'ch', 'fr' );
 return str_ireplace( $units, '', $input );
 }
 public static function clamp_to_static_px( $clamp_str, $strategy = 'min' ): ?string {
 if ( stripos( $clamp_str, 'clamp(' ) === false ) {
 return $clamp_str;
 }
 $value_array = explode( ',', $clamp_str );
 if ( count( $value_array ) < 2 ) {
 return $clamp_str; // Invalid clamp format.
 }
 $first_element = $value_array[0];
 $min = trim( str_ireplace( array( 'clamp(', 'min(', 'max(' ), '', $first_element ) );
 $last_element = $value_array[ count( $value_array ) - 1 ];
 $max = trim( rtrim( $last_element, ')' ) );
 $min_px = self::convert_to_px( $min, false );
 $max_px = self::convert_to_px( $max, false );
 // Determine which value to use.
 if ( 'min' === $strategy && ! is_null( $min_px ) ) {
 return $min_px;
 }
 if ( 'max' === $strategy && ! is_null( $max_px ) ) {
 return $max_px;
 }
 if ( 'avg' === $strategy && ! is_null( $min_px ) && ! is_null( $max_px ) ) {
 $avg = ( self::parse_value( $min_px ) + self::parse_value( $max_px ) ) / 2;
 return $avg . 'px';
 }
 // Default.
 return $min_px ?? $max_px ?? $clamp_str;
 }
}
