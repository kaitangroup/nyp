<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Templates;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use WP_Block_Template;
class Templates {
 private string $template_prefix = 'woocommerce';
 private array $post_types = array();
 private string $template_directory = __DIR__ . DIRECTORY_SEPARATOR;
 private Templates_Registry $templates_registry;
 public function __construct( Templates_Registry $templates_registry ) {
 $this->templates_registry = $templates_registry;
 }
 public function initialize( array $post_types ): void {
 $this->post_types = $post_types;
 add_filter( 'theme_templates', array( $this, 'add_theme_templates' ), 10, 4 ); // Workaround needed when saving post – template association.
 add_filter( 'woocommerce_email_editor_register_templates', array( $this, 'register_templates' ) );
 $this->templates_registry->initialize();
 $this->register_post_types_to_api();
 }
 public function get_block_template( $template_slug ) {
 // Template id is always prefixed by active theme and get_stylesheet returns the active theme slug.
 $template_id = get_stylesheet() . '//' . $template_slug;
 return get_block_template( $template_id );
 }
 public function register_templates( Templates_Registry $templates_registry ): Templates_Registry {
 // Register basic blank template.
 $general_email_slug = 'email-general';
 $template_filename = $general_email_slug . '.html';
 $general_email = new Template(
 $this->template_prefix,
 $general_email_slug,
 __( 'General Email', 'woocommerce' ),
 __( 'A general template for emails.', 'woocommerce' ),
 (string) file_get_contents( $this->template_directory . $template_filename ),
 $this->post_types
 );
 $templates_registry->register( $general_email );
 return $templates_registry;
 }
 public function register_post_types_to_api(): void {
 $controller = new \WP_REST_Templates_Controller( 'wp_template' );
 $schema = $controller->get_item_schema();
 // Skip registration only when post_types is natively available in the view context.
 // WP 7.0 adds the property but only in the edit context; we must still register
 // the field so that context=view responses include it.
 $post_types_context = $schema['properties']['post_types']['context'] ?? array();
 if ( in_array( 'view', $post_types_context, true ) ) {
 return;
 }
 register_rest_field(
 'wp_template',
 'post_types',
 array(
 'get_callback' => array( $this, 'get_post_types' ),
 'update_callback' => null,
 'schema' => Builder::string()->to_array(),
 )
 );
 }
 public function get_post_types( $response_object ): array {
 $template = $this->templates_registry->get_by_slug( $response_object['slug'] ?? '' );
 if ( $template ) {
 return $template->get_post_types();
 }
 return $response_object['post_types'] ?? array();
 }
 public function add_theme_templates( $templates, $theme, $post, $post_type ) {
 if ( $post_type && ! in_array( $post_type, $this->post_types, true ) ) {
 return $templates;
 }
 $block_templates = get_block_templates();
 $email_templates_slugs = array_map(
 function ( Template $template ) {
 return $template->get_slug();
 },
 $this->templates_registry->get_all()
 );
 foreach ( $block_templates as $block_template ) {
 if ( ! in_array( $block_template->slug, $email_templates_slugs, true ) ) {
 continue;
 }
 if ( isset( $templates[ $block_template->slug ] ) ) {
 continue;
 }
 $templates[ $block_template->slug ] = $block_template->title; // Requires only the template title, not the full template object.
 }
 return $templates;
 }
}
