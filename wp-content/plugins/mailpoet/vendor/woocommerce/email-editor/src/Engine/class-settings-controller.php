<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
class Settings_Controller {
 const DEFAULT_SETTINGS = array(
 'enableCustomUnits' => array( 'px', '%' ),
 );
 private Theme_Controller $theme_controller;
 private array $allowed_iframe_style_handles = array();
 private array $iframe_assets = array();
 public function __construct(
 Theme_Controller $theme_controller
 ) {
 $this->theme_controller = $theme_controller;
 }
 public function get_settings(): array {
 $this->init_iframe_assets();
 $core_default_settings = \get_default_block_editor_settings();
 $theme_settings = $this->theme_controller->get_settings();
 $settings = array_merge( $core_default_settings, self::DEFAULT_SETTINGS );
 // Assets for iframe editor (component styles, scripts, etc.).
 $settings['__unstableResolvedAssets'] = $this->iframe_assets;
 $settings['allowedIframeStyleHandles'] = $this->allowed_iframe_style_handles;
 $editor_content_styles = file_get_contents( __DIR__ . '/content-editor.css' );
 $shares_content_styles = file_get_contents( __DIR__ . '/content-shared.css' );
 $settings['styles'] = array(
 array( 'css' => $editor_content_styles ),
 array( 'css' => $shares_content_styles ),
 );
 $settings['autosaveInterval'] = 60;
 // Disable code editing in the email editor. We manipulate HTML in renderer so it doesn't make sense to have it enabled.
 $settings['codeEditingEnabled'] = false;
 $settings['__experimentalFeatures'] = $theme_settings;
 // Controls which alignment options are available for blocks.
 $settings['supportsLayout'] = true; // Allow using default layouts.
 $settings['alignWide'] = true; // Enable wide and full alignment options.
 $settings['__unstableIsBlockBasedTheme'] = false; // Setting to true disables wide and full alignments in flow layouts.
 return $settings;
 }
 public function get_layout(): array {
 $layout_settings = $this->theme_controller->get_layout_settings();
 $layout = array(
 'contentSize' => $layout_settings['contentSize'],
 );
 if ( isset( $layout_settings['wideSize'] ) ) {
 $layout['wideSize'] = $layout_settings['wideSize'];
 }
 return $layout;
 }
 public function get_email_styles(): array {
 $theme = $this->get_theme();
 return $theme->get_data()['styles'];
 }
 public function get_layout_width_without_padding(): string {
 $styles = $this->get_email_styles();
 $layout = $this->get_layout();
 $width = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
 $width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['left'] );
 $width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['right'] );
 return "{$width}px";
 }
 public function parse_styles_to_array( string $styles ): array {
 $styles = explode( ';', $styles );
 $parsed_styles = array();
 foreach ( $styles as $style ) {
 $style = explode( ':', $style );
 if ( count( $style ) === 2 ) {
 $parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
 }
 }
 return $parsed_styles;
 }
 public function parse_number_from_string_with_pixels( string $value ): float {
 return (float) str_replace( 'px', '', $value );
 }
 public function get_theme(): \WP_Theme_JSON {
 return $this->theme_controller->get_theme();
 }
 public function translate_slug_to_font_size( string $font_size ): string {
 return $this->theme_controller->translate_slug_to_font_size( $font_size );
 }
 public function translate_slug_to_color( string $color_slug ): string {
 return $this->theme_controller->translate_slug_to_color( $color_slug );
 }
 private function get_allowed_iframe_style_handles() {
 // Core style handles.
 $allowed_iframe_style_handles = array(
 'wp-components-css',
 'wp-reset-editor-styles-css',
 'wp-block-library-css',
 'wp-block-editor-content-css',
 'wp-edit-blocks-css',
 );
 foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $block ) {
 if ( ! isset( $block->supports['email'] ) || ! $block->supports['email'] ) {
 continue;
 }
 foreach ( $block->style_handles as $handle ) {
 $allowed_iframe_style_handles[] = $handle . '-css';
 }
 foreach ( $block->editor_style_handles as $handle ) {
 $allowed_iframe_style_handles[] = $handle . '-css';
 }
 }
 return apply_filters( 'woocommerce_email_editor_allowed_iframe_style_handles', $allowed_iframe_style_handles );
 }
 private function init_iframe_assets(): void {
 if ( ! empty( $this->iframe_assets ) ) {
 return;
 }
 $this->iframe_assets = _wp_get_iframed_editor_assets();
 $this->allowed_iframe_style_handles = $this->get_allowed_iframe_style_handles();
 $cleaned_styles = array();
 foreach ( explode( "\n", (string) $this->iframe_assets['styles'] ) as $asset ) {
 foreach ( $this->allowed_iframe_style_handles as $handle ) {
 if ( strpos( $asset, $handle ) !== false ) {
 $cleaned_styles[] = $asset;
 break;
 }
 }
 }
 $this->iframe_assets['styles'] = implode( "\n", $cleaned_styles );
 }
}
