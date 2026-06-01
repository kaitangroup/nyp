<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use WP_Theme_JSON;
class Rendering_Context {
 private WP_Theme_JSON $theme_json;
 private array $email_context;
 private ?string $language;
 public function __construct( WP_Theme_JSON $theme_json, array $email_context = array(), ?string $language = null ) {
 $this->theme_json = $theme_json;
 $this->email_context = $email_context;
 $this->language = $language;
 }
 public function get_theme_json(): WP_Theme_JSON {
 return $this->theme_json;
 }
 public function get_theme_styles(): array {
 $theme = $this->get_theme_json();
 return $theme->get_data()['styles'] ?? array();
 }
 public function get_theme_settings() {
 return $this->get_theme_json()->get_settings();
 }
 public function get_layout_width_without_padding(): string {
 $styles = $this->get_theme_styles();
 $layout_settings = $this->get_theme_settings()['layout'] ?? array();
 $width = Styles_Helper::parse_value( $layout_settings['contentSize'] ?? '0px' );
 $padding = $styles['spacing']['padding'] ?? array();
 $width -= Styles_Helper::parse_value( $padding['left'] ?? '0px' );
 $width -= Styles_Helper::parse_value( $padding['right'] ?? '0px' );
 return "{$width}px";
 }
 public function translate_slug_to_color( string $color_slug ): string {
 $settings = $this->get_theme_settings();
 $color_definitions = array_merge(
 $settings['color']['palette']['theme'] ?? array(),
 $settings['color']['palette']['default'] ?? array()
 );
 foreach ( $color_definitions as $color_definition ) {
 if ( $color_definition['slug'] === $color_slug ) {
 return strtolower( $color_definition['color'] );
 }
 }
 return $color_slug;
 }
 public function get_email_context(): array {
 return $this->email_context;
 }
 public function get_language(): ?string {
 return $this->language;
 }
 public function get_user_id(): ?int {
 return isset( $this->email_context['user_id'] ) && is_numeric( $this->email_context['user_id'] ) ? (int) $this->email_context['user_id'] : null;
 }
 public function get_recipient_email(): ?string {
 return isset( $this->email_context['recipient_email'] ) && is_string( $this->email_context['recipient_email'] ) ? $this->email_context['recipient_email'] : null;
 }
 public function get( string $key, $default_value = null ) {
 return $this->email_context[ $key ] ?? $default_value;
 }
 public function is_rtl(): bool {
 if ( isset( $this->email_context['is_rtl'] ) && is_bool( $this->email_context['is_rtl'] ) ) {
 return $this->email_context['is_rtl'];
 }
 $primary_language = $this->get_primary_language_subtag( $this->language );
 if ( null === $primary_language ) {
 return false;
 }
 return in_array(
 $primary_language,
 array(
 'ar',
 'arc',
 'azb',
 'ckb',
 'dv',
 'fa',
 'he',
 'ku',
 'nqo',
 'ps',
 'sd',
 'ug',
 'ur',
 'yi',
 ),
 true
 );
 }
 public function get_text_direction(): string {
 return $this->is_rtl() ? 'rtl' : 'ltr';
 }
 public function get_default_text_align(): string {
 return $this->is_rtl() ? 'right' : 'left';
 }
 public function get_start_side(): string {
 return $this->is_rtl() ? 'right' : 'left';
 }
 public function get_end_side(): string {
 return $this->is_rtl() ? 'left' : 'right';
 }
 public function sanitize_text_align( $alignment ): ?string {
 if ( ! is_string( $alignment ) ) {
 return null;
 }
 return in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : null;
 }
 public function resolve_text_align( $alignment ): string {
 return $this->sanitize_text_align( $alignment ) ?? $this->get_default_text_align();
 }
 private function get_primary_language_subtag( ?string $language ): ?string {
 if ( null === $language || '' === trim( $language ) ) {
 return null;
 }
 $language = strtolower( str_replace( '_', '-', trim( $language ) ) );
 $parts = explode( '-', $language );
 $primary = $parts[0] ?? '';
 $length = strlen( $primary );
 if ( $length < 2 || $length > 3 ) {
 return null;
 }
 return strspn( $primary, 'abcdefghijklmnopqrstuvwxyz' ) === $length ? $primary : null;
 }
}
