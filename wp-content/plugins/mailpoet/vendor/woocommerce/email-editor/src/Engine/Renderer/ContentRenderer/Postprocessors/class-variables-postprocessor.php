<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
class Variables_Postprocessor implements Postprocessor {
 private Theme_Controller $theme_controller;
 public function __construct(
 Theme_Controller $theme_controller
 ) {
 $this->theme_controller = $theme_controller;
 }
 public function postprocess( string $html ): string {
 $variables = $this->theme_controller->get_variables_values_map();
 $replacements = array();
 foreach ( $variables as $name => $value ) {
 $var_pattern = '/' . preg_quote( 'var(' . $name . ')', '/' ) . '/i';
 $replacements[ $var_pattern ] = $value;
 }
 // We want to replace the CSS variables only in the style attributes to avoid replacing the actual content.
 $processor = new \WP_HTML_Tag_Processor( $html );
 while ( $processor->next_tag() ) {
 $style = $processor->get_attribute( 'style' );
 if ( null !== $style && true !== $style ) {
 // Replace CSS variables with their values.
 $processed_style = preg_replace( array_keys( $replacements ), array_values( $replacements ), $style );
 if ( null !== $processed_style ) {
 $processor->set_attribute( 'style', $processed_style );
 }
 }
 }
 return $processor->get_updated_html();
 }
}
