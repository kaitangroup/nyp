<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
use WP_Theme_JSON;
use WP_Theme_JSON_Resolver;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
class Site_Style_Sync_Controller {
 private ?WP_Theme_JSON $site_theme = null;
 private ?array $base_theme_data = null;
 private $email_safe_fonts = array();
 public function __construct() {
 add_action( 'init', array( $this, 'initialize' ), 20 );
 }
 public function initialize(): void {
 add_action( 'switch_theme', array( $this, 'invalidate_site_theme_cache' ) );
 add_action( 'customize_save_after', array( $this, 'invalidate_site_theme_cache' ) );
 }
 public function sync_site_styles( ?WP_Theme_JSON $base_theme = null ): array {
 // Store base theme data for fallback lookups.
 $this->base_theme_data = $base_theme ? $base_theme->get_data() : null;
 $site_theme = $this->get_site_theme();
 $site_data = $site_theme->get_data();
 $synced_data = array(
 'version' => 3,
 'settings' => $this->sync_settings_data( $site_data['settings'] ?? array() ),
 'styles' => $this->sync_styles_data( $site_data['styles'] ?? array() ),
 );
 $synced_data = apply_filters( 'woocommerce_email_editor_synced_site_styles', $synced_data, $site_data );
 return $synced_data;
 }
 public function get_theme( ?WP_Theme_JSON $base_theme = null ): ?WP_Theme_JSON {
 if ( ! $this->is_sync_enabled() ) {
 return null;
 }
 $synced_data = $this->sync_site_styles( $base_theme );
 if ( empty( $synced_data ) || ! isset( $synced_data['version'] ) ) {
 return null;
 }
 return new WP_Theme_JSON( $synced_data, 'theme' );
 }
 public function is_sync_enabled(): bool {
 return apply_filters( 'woocommerce_email_editor_site_style_sync_enabled', true );
 }
 public function invalidate_site_theme_cache(): void {
 if ( ! $this->is_sync_enabled() ) {
 return;
 }
 $this->site_theme = null;
 }
 private function get_site_theme(): WP_Theme_JSON {
 if ( null === $this->site_theme ) {
 // Get only the theme and user customizations (e.g. from site editor).
 $this->site_theme = new WP_Theme_JSON();
 $this->site_theme->merge( WP_Theme_JSON_Resolver::get_theme_data() );
 $this->site_theme->merge( WP_Theme_JSON_Resolver::get_user_data() );
 $this->site_theme = apply_filters( 'woocommerce_email_editor_site_theme', $this->site_theme );
 if ( isset( $this->site_theme->get_raw_data()['styles'] ) ) {
 $this->site_theme = WP_Theme_JSON::resolve_variables( $this->site_theme );
 }
 }
 return $this->site_theme;
 }
 private function sync_settings_data( array $site_settings ): array {
 $email_settings = array();
 // Sync color palette.
 if ( isset( $site_settings['color']['palette'] ) ) {
 $email_settings['color']['palette'] = $site_settings['color']['palette'];
 }
 return $email_settings;
 }
 private function sync_styles_data( array $site_styles ): array {
 $email_styles = array();
 // Sync color styles.
 if ( ! empty( $site_styles['color'] ) ) {
 $email_styles['color'] = $this->convert_color_styles( $site_styles['color'] );
 }
 // Sync typography styles.
 if ( ! empty( $site_styles['typography'] ) ) {
 $email_styles['typography'] = $this->convert_typography_styles( $site_styles['typography'] );
 }
 // Sync spacing styles.
 if ( ! empty( $site_styles['spacing'] ) ) {
 $email_styles['spacing'] = $this->convert_spacing_styles( $site_styles['spacing'] );
 }
 // Sync element styles.
 if ( ! empty( $site_styles['elements'] ) ) {
 $email_styles['elements'] = $this->convert_element_styles( $site_styles['elements'] );
 }
 return $email_styles;
 }
 public function get_email_safe_fonts(): array {
 if ( empty( $this->email_safe_fonts ) ) {
 $theme_data = (array) json_decode( (string) file_get_contents( __DIR__ . '/theme.json' ), true );
 $font_families = $theme_data['settings']['typography']['fontFamilies'] ?? array();
 if ( ! empty( $font_families ) ) {
 foreach ( $font_families as $font_family ) {
 $this->email_safe_fonts[ strtolower( $font_family['slug'] ) ] = $font_family['fontFamily'];
 }
 }
 }
 return $this->email_safe_fonts;
 }
 private function convert_color_styles( array $color_styles ): array {
 $email_colors = array();
 $this->resolve_and_assign( $color_styles, 'background', $email_colors );
 $this->resolve_and_assign( $color_styles, 'text', $email_colors );
 return $email_colors;
 }
 private function convert_typography_styles( array $typography_styles, string $element = '' ): array {
 $email_typography = array();
 // Handle special cases with processors.
 $this->resolve_and_assign( $typography_styles, 'fontFamily', $email_typography, array( $this, 'convert_to_email_safe_font' ) );
 $this->resolve_and_assign(
 $typography_styles,
 'fontSize',
 $email_typography,
 function ( $value ) use ( $element ) {
 // Try element-specific fallback first, then global fallback.
 $fallback = null;
 if ( $element ) {
 $fallback = $this->get_base_theme_value( array( 'styles', 'elements', $element, 'typography', 'fontSize' ) );
 }
 if ( ! $fallback ) {
 $fallback = $this->get_base_theme_value( array( 'styles', 'typography', 'fontSize' ) );
 }
 return $this->convert_to_px_size( $value, $fallback );
 }
 );
 // Handle compatible properties without processing.
 $compatible_props = array( 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing', 'textTransform', 'textDecoration' );
 foreach ( $compatible_props as $prop ) {
 $this->resolve_and_assign( $typography_styles, $prop, $email_typography );
 }
 return $email_typography;
 }
 private function convert_spacing_styles( array $spacing_styles ): array {
 $email_spacing = array();
 $this->resolve_and_assign(
 $spacing_styles,
 'padding',
 $email_spacing,
 function ( $value ) {
 return $this->convert_spacing_values( $value, array( 'styles', 'spacing', 'padding' ) );
 }
 );
 $this->resolve_and_assign(
 $spacing_styles,
 'blockGap',
 $email_spacing,
 function ( $value ) {
 $fallback = $this->get_base_theme_value( array( 'styles', 'spacing', 'blockGap' ) );
 return $this->convert_to_px_size( $value, $fallback );
 }
 );
 // Note: We intentionally skip margin as it's not supported in email renderer.
 return $email_spacing;
 }
 private function convert_element_styles( array $element_styles ): array {
 $email_elements = array();
 // Process supported elements.
 $supported_elements = array( 'heading', 'button', 'link', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
 foreach ( $supported_elements as $element ) {
 if ( isset( $element_styles[ $element ] ) ) {
 $email_elements[ $element ] = $this->convert_element_style( $element_styles[ $element ], $element );
 }
 }
 return $email_elements;
 }
 private function convert_element_style( array $element_style, string $element_name = '' ): array {
 $email_element = array();
 // Convert typography if present.
 if ( isset( $element_style['typography'] ) ) {
 $email_element['typography'] = $this->convert_typography_styles( $element_style['typography'], $element_name );
 }
 // Convert color if present.
 if ( isset( $element_style['color'] ) ) {
 $email_element['color'] = $this->convert_color_styles( $element_style['color'] );
 }
 // Convert spacing if present.
 if ( isset( $element_style['spacing'] ) ) {
 $email_element['spacing'] = $this->convert_spacing_styles( $element_style['spacing'] );
 }
 return $email_element;
 }
 private function resolve_and_assign( array $styles, string $property, array &$target, ?callable $processor = null ): bool {
 if ( ! isset( $styles[ $property ] ) ) {
 return false;
 }
 $resolved = $this->resolve_style_value( $styles[ $property ] );
 if ( ! $resolved ) {
 return false;
 }
 $target[ $property ] = $processor ? $processor( $resolved ) : $resolved;
 return true;
 }
 private function resolve_style_value( $style_value ) {
 // Check if this is a reference array.
 if ( is_array( $style_value ) && isset( $style_value['ref'] ) ) {
 $ref = $style_value['ref'];
 if ( ! is_string( $ref ) || empty( $ref ) ) {
 return null;
 }
 $path = explode( '.', $ref );
 return _wp_array_get( $this->get_site_theme()->get_data(), $path, null );
 }
 return $style_value;
 }
 private function convert_to_email_safe_font( string $font_family ): string {
 // Get email-safe fonts.
 $email_safe_fonts = $this->get_email_safe_fonts();
 // Map common web fonts to email-safe alternatives.
 $font_map = array(
 'helvetica' => $email_safe_fonts['arial'], // Arial fallback.
 'times' => $email_safe_fonts['georgia'], // Georgia fallback.
 'courier' => $email_safe_fonts['courier-new'], // Courier New.
 'trebuchet' => $email_safe_fonts['trebuchet-ms'],
 );
 $email_safe_fonts = array_merge( $email_safe_fonts, $font_map );
 $get_font_family = function ( $font_name ) use ( $email_safe_fonts ) {
 $font_name_lower = strtolower( $font_name );
 // First check for match in the email-safe slug.
 if ( isset( $email_safe_fonts[ $font_name_lower ] ) ) {
 return $email_safe_fonts[ $font_name_lower ];
 }
 // If no match in the slug, check for match in the font family name.
 foreach ( $email_safe_fonts as $safe_font_slug => $safe_font ) {
 if ( stripos( $safe_font, $font_name_lower ) !== false ) {
 return $safe_font;
 }
 }
 return null;
 };
 // Check if it's already an email-safe font.
 $font_family_array = explode( ',', $font_family );
 $first_font = trim( $font_family_array[0] );
 $first_font = trim( $first_font, "\"'" );
 $safe_font_family = $get_font_family( $first_font );
 if ( $safe_font_family ) {
 return $safe_font_family;
 }
 // Default to arial font if no match found.
 return $email_safe_fonts['arial'];
 }
 private function convert_to_px_size( string $size, ?string $fallback = null ): string {
 $converted = null;
 // Replace clamp() with its minimum value. We use min because it's emails are most likely to be viewed on smaller screens.
 if ( stripos( $size, 'clamp(' ) !== false ) {
 $converted = Styles_Helper::clamp_to_static_px( $size, 'min' );
 // If clamp_to_static_px returns the original value, it failed to convert.
 if ( $converted === $size ) {
 $converted = null;
 }
 }
 // Try standard conversion.
 if ( is_null( $converted ) ) {
 $converted = Styles_Helper::convert_to_px( $size, false );
 }
 // If all conversions failed, use fallback if provided.
 if ( is_null( $converted ) && $fallback ) {
 return $fallback;
 }
 // Return converted value or original if conversion failed.
 return $converted ?? $size;
 }
 private function convert_spacing_values( $spacing_values, array $base_path ) {
 if ( ! is_string( $spacing_values ) && ! is_array( $spacing_values ) ) {
 return $spacing_values;
 }
 if ( is_string( $spacing_values ) ) {
 $fallback = $this->get_base_theme_value( $base_path );
 return $this->convert_to_px_size( $spacing_values, $fallback );
 }
 $px_values = array();
 foreach ( $spacing_values as $side => $value ) {
 if ( is_string( $value ) ) {
 // Build path for side-specific fallback (e.g., ['styles', 'spacing', 'padding', 'top']).
 $side_path = array_merge( $base_path, array( $side ) );
 $fallback = $this->get_base_theme_value( $side_path );
 $px_values[ $side ] = $this->convert_to_px_size( $value, $fallback );
 } else {
 $px_values[ $side ] = $value;
 }
 }
 return $px_values;
 }
 private function get_base_theme_value( array $path ): ?string {
 if ( ! $this->base_theme_data ) {
 return null;
 }
 $value = _wp_array_get( $this->base_theme_data, $path );
 return is_string( $value ) ? $value : null;
 }
}
