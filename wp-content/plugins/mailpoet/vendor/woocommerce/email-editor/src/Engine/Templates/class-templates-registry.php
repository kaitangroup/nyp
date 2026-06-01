<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Templates;
if (!defined('ABSPATH')) exit;
class Templates_Registry {
 private $templates = array();
 public function initialize(): void {
 apply_filters( 'woocommerce_email_editor_register_templates', $this );
 }
 public function register( Template $template ): void {
 if ( ! \WP_Block_Templates_Registry::get_instance()->is_registered( $template->get_name() ) ) {
 // skip registration if the template was already registered.
 $result = register_block_template(
 $template->get_name(),
 array(
 'title' => $template->get_title(),
 'description' => $template->get_description(),
 'content' => $template->get_content(),
 'post_types' => $template->get_post_types(),
 )
 );
 $this->templates[ $template->get_name() ] = $template;
 }
 }
 public function get_by_name( string $name ): ?Template {
 return $this->templates[ $name ] ?? null;
 }
 public function get_by_slug( string $slug ): ?Template {
 foreach ( $this->templates as $template ) {
 if ( $template->get_slug() === $slug ) {
 return $template;
 }
 }
 return null;
 }
 public function get_all() {
 return $this->templates;
 }
}
