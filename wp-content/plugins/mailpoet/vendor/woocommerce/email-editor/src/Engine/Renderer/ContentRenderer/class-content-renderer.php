<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Fallback;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Post_Content;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use WP_Block_Template;
use WP_Block_Type_Registry;
use WP_Post;
use WP_Style_Engine;
class Content_Renderer {
 private Process_Manager $process_manager;
 private Theme_Controller $theme_controller;
 const CONTENT_STYLES_FILE = 'content.css';
 private WP_Block_Type_Registry $block_type_registry;
 private $backup_template_content;
 private $backup_template_id;
 private $backup_post;
 private $backup_query;
 private Fallback $fallback_renderer;
 private Email_Editor_Logger $logger;
 private $backup_post_content_callback;
 private ?string $post_content_width = null;
 private array $container_padding = array();
 private Css_Inliner $css_inliner;
 private ?Rendering_Context $rendering_context = null;
 public function __construct(
 Process_Manager $preprocess_manager,
 Css_Inliner $css_inliner,
 Theme_Controller $theme_controller,
 Email_Editor_Logger $logger
 ) {
 $this->process_manager = $preprocess_manager;
 $this->css_inliner = $css_inliner;
 $this->theme_controller = $theme_controller;
 $this->logger = $logger;
 $this->block_type_registry = WP_Block_Type_Registry::get_instance();
 $this->fallback_renderer = new Fallback();
 }
 private function initialize() {
 add_filter( 'render_block', array( $this, 'render_block' ), 10, 2 );
 add_filter( 'block_parser_class', array( $this, 'block_parser' ) );
 add_filter( 'woocommerce_email_blocks_renderer_parsed_blocks', array( $this, 'preprocess_parsed_blocks' ) );
 // Swap core/post-content render callback for email rendering.
 // This prevents issues with WordPress's static $seen_ids array when rendering
 // multiple emails in a single request (e.g., MailPoet batch processing).
 $post_content_type = $this->block_type_registry->get_registered( 'core/post-content' );
 if ( $post_content_type ) {
 // Save the original callback (may be null or WordPress's default).
 $this->backup_post_content_callback = $post_content_type->render_callback;
 // Replace with our stateless renderer.
 $post_content_renderer = new Post_Content();
 $post_content_type->render_callback = array( $post_content_renderer, 'render_stateless' );
 }
 }
 public function set_rendering_context( Rendering_Context $rendering_context ): void {
 $this->rendering_context = $rendering_context;
 }
 public function get_current_rendering_context(): ?Rendering_Context {
 return $this->rendering_context;
 }
 public function restore_rendering_context( ?Rendering_Context $rendering_context ): void {
 $this->rendering_context = $rendering_context;
 }
 public function render( WP_Post $post, WP_Block_Template $template ): string {
 $result = $this->render_without_css_inline( $post, $template );
 $styles = '<style>' . $result['styles'] . '</style>';
 $html = $this->css_inliner->from_html( $styles . $result['html'] )->inline_css()->render();
 return $this->process_manager->postprocess( $html );
 }
 public function render_without_css_inline( WP_Post $post, WP_Block_Template $template ): array {
 if ( null === $this->rendering_context ) {
 $this->rendering_context = $this->create_rendering_context( null, $post, $template );
 }
 $this->set_template_globals( $post, $template );
 $this->initialize();
 try {
 do_action( 'woocommerce_email_editor_render_start' );
 $rendered_html = get_the_block_template_html();
 } finally {
 $this->reset();
 }
 return array(
 'html' => $rendered_html,
 'styles' => $this->collect_styles( $post, $template ),
 );
 }
 public function block_parser() {
 return 'Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Parser';
 }
 public function preprocess_parsed_blocks( array $parsed_blocks ): array {
 $styles = $this->theme_controller->get_styles();
 $layout = $this->theme_controller->get_layout_settings();
 // Pass the CSS variables map so preprocessors can resolve preset
 // references (e.g. var:preset|spacing|20) in block attributes.
 $styles['__variables_map'] = $this->theme_controller->get_variables_values_map();
 // Second pass (user blocks inside post-content): if root padding was
 // applied to a container above post-content in the first pass (indicated
 // by post_content_width < contentSize), remove root padding from styles
 // to prevent double application. If the template delegates root padding
 // (post_content_width == contentSize), keep it for user blocks.
 if ( null !== $this->post_content_width ) {
 $post_content_num = (float) str_replace( 'px', '', $this->post_content_width );
 $content_size_num = (float) str_replace( 'px', '', $layout['contentSize'] );
 // Use epsilon tolerance for floating-point comparison since width
 // calculations involve round() and division that may produce imprecision.
 if ( $post_content_num < $content_size_num - 0.01 ) {
 unset( $styles['spacing']['padding']['left'], $styles['spacing']['padding']['right'] );
 }
 // Pass container padding from the first pass so the
 // Spacing_Preprocessor can distribute it to user blocks.
 if ( ! empty( $this->container_padding ) ) {
 $styles['__container_padding'] = $this->container_padding;
 }
 }
 $result = $this->process_manager->preprocess( $parsed_blocks, $layout, $styles, $this->get_rendering_context() );
 // After the first pass: find the post-content block's width and container padding.
 if ( null === $this->post_content_width ) {
 $this->post_content_width = $this->find_post_content_width( $result );
 $this->container_padding = $this->find_container_padding( $result );
 }
 return $result;
 }
 private function find_post_content_width( array $blocks, ?array $post_content_block_names = null ): ?string {
 if ( null === $post_content_block_names ) {
 $post_content_block_names = (array) apply_filters(
 'woocommerce_email_editor_post_content_block_names',
 array( 'core/post-content' )
 );
 }
 foreach ( $blocks as $block ) {
 $block_name = $block['blockName'] ?? '';
 if ( in_array( $block_name, $post_content_block_names, true ) ) {
 return $block['email_attrs']['width'] ?? null;
 }
 if ( ! empty( $block['innerBlocks'] ) ) {
 $found = $this->find_post_content_width( $block['innerBlocks'], $post_content_block_names );
 if ( null !== $found ) {
 return $found;
 }
 }
 }
 return null;
 }
 private function find_container_padding( array $blocks ): array {
 $variables_map = $this->theme_controller->get_variables_values_map();
 foreach ( $blocks as $block ) {
 $email_attrs = $block['email_attrs'] ?? array();
 if ( ! empty( $email_attrs['suppress-horizontal-padding'] ) ) {
 $padding = $block['attrs']['style']['spacing']['padding'] ?? array();
 $result = array();
 if ( isset( $padding['left'] ) && is_string( $padding['left'] ) ) {
 $result['left'] = Preset_Variable_Resolver::resolve( $padding['left'], $variables_map );
 }
 if ( isset( $padding['right'] ) && is_string( $padding['right'] ) ) {
 $result['right'] = Preset_Variable_Resolver::resolve( $padding['right'], $variables_map );
 }
 if ( ! empty( $result ) ) {
 return $result;
 }
 }
 if ( ! empty( $block['innerBlocks'] ) ) {
 $found = $this->find_container_padding( $block['innerBlocks'] );
 if ( ! empty( $found ) ) {
 return $found;
 }
 }
 }
 return array();
 }
 public function render_block( string $block_content, array $parsed_block ): string {
 $context = $this->get_rendering_context();
 $block_type = $this->block_type_registry->get_registered( $parsed_block['blockName'] );
 $result = null;
 try {
 if ( $block_type && isset( $block_type->render_email_callback ) && is_callable( $block_type->render_email_callback ) ) {
 $result = call_user_func( $block_type->render_email_callback, $block_content, $parsed_block, $context );
 }
 } catch ( \Exception $error ) {
 $this->logger->error(
 'Error thrown while rendering block.',
 array(
 'exception' => $error,
 'block_name' => $parsed_block['blockName'],
 'parsed_block' => $parsed_block,
 'message' => $error->getMessage(),
 )
 );
 // Returning the original content.
 return $block_content;
 }
 if ( null === $result ) {
 $result = $this->fallback_renderer->render( $block_content, $parsed_block, $context );
 }
 return $this->add_root_horizontal_padding( $result, $parsed_block['email_attrs'] ?? array() );
 }
 private function add_root_horizontal_padding( string $content, array $email_attrs ): string {
 $padding_left = $this->sum_padding_values(
 $email_attrs['root-padding-left'] ?? null,
 $email_attrs['container-padding-left'] ?? null
 );
 $padding_right = $this->sum_padding_values(
 $email_attrs['root-padding-right'] ?? null,
 $email_attrs['container-padding-right'] ?? null
 );
 $css_attrs = array();
 if ( $padding_left > 0 ) {
 $css_attrs['padding-left'] = $padding_left . 'px';
 }
 if ( $padding_right > 0 ) {
 $css_attrs['padding-right'] = $padding_right . 'px';
 }
 if ( empty( $css_attrs ) ) {
 return $content;
 }
 $padding_style = WP_Style_Engine::compile_css( $css_attrs, '' );
 if ( empty( $padding_style ) ) {
 return $content;
 }
 $table_attrs = array(
 'align' => $this->get_rendering_context()->get_default_text_align(),
 'width' => '100%',
 );
 $cell_attrs = array(
 'style' => $padding_style,
 );
 $div_content = sprintf(
 '<div class="email-root-padding" style="%1$s">%2$s</div>',
 esc_attr( $padding_style ),
 $content
 );
 return Table_Wrapper_Helper::render_outlook_table_wrapper( $div_content, $table_attrs, $cell_attrs );
 }
 private function sum_padding_values( ?string $value1, ?string $value2 ): float {
 $sum = 0.0;
 if ( null !== $value1 ) {
 $sum += (float) str_replace( 'px', '', $value1 );
 }
 if ( null !== $value2 ) {
 $sum += (float) str_replace( 'px', '', $value2 );
 }
 return $sum;
 }
 private function set_template_globals( WP_Post $email_post, WP_Block_Template $template ) {
 global $_wp_current_template_content, $_wp_current_template_id, $wp_query, $post;
 // Backup current values of globals.
 // Because overriding the globals can affect rendering of the page itself, we need to backup the current values.
 $this->backup_template_content = $_wp_current_template_content;
 $this->backup_template_id = $_wp_current_template_id;
 $this->backup_query = $wp_query;
 $this->backup_post = $post;
 $_wp_current_template_id = $template->id;
 $_wp_current_template_content = $template->content;
 $wp_query = new \WP_Query( array( 'p' => $email_post->ID ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to set the query for correct rendering the blocks.
 $post = $email_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to set the post for correct rendering the blocks.
 }
 private function reset(): void {
 remove_filter( 'render_block', array( $this, 'render_block' ) );
 remove_filter( 'block_parser_class', array( $this, 'block_parser' ) );
 remove_filter( 'woocommerce_email_blocks_renderer_parsed_blocks', array( $this, 'preprocess_parsed_blocks' ) );
 $this->post_content_width = null;
 $this->container_padding = array();
 $this->rendering_context = null;
 // Restore the original core/post-content render callback.
 // Note: We always restore it, even if it was null originally.
 $post_content_type = $this->block_type_registry->get_registered( 'core/post-content' );
 if ( $post_content_type ) {
 // @phpstan-ignore-next-line -- WordPress core allows null for render_callback despite type definition.
 $post_content_type->render_callback = $this->backup_post_content_callback;
 }
 // Restore globals to their original values.
 global $_wp_current_template_content, $_wp_current_template_id, $wp_query, $post;
 $_wp_current_template_content = $this->backup_template_content;
 $_wp_current_template_id = $this->backup_template_id;
 $wp_query = $this->backup_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring of the query.
 $post = $this->backup_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring of the post.
 }
 private function get_rendering_context(): Rendering_Context {
 if ( null === $this->rendering_context ) {
 $this->rendering_context = $this->create_rendering_context();
 }
 return $this->rendering_context;
 }
 public function create_rendering_context( ?string $language = null, ?WP_Post $post = null, ?WP_Block_Template $template = null ): Rendering_Context {
 $email_context = apply_filters( 'woocommerce_email_editor_rendering_email_context', array(), $post, $template );
 if ( ! is_array( $email_context ) ) {
 $email_context = array();
 }
 return new Rendering_Context( $this->theme_controller->get_theme(), $email_context, $language );
 }
 private function collect_styles( WP_Post $post, $template = null ): string {
 $styles = (string) file_get_contents( __DIR__ . '/' . self::CONTENT_STYLES_FILE );
 $styles .= (string) file_get_contents( __DIR__ . '/../../content-shared.css' );
 // Apply default contentWidth to constrained blocks.
 $layout = $this->theme_controller->get_layout_settings();
 $styles .= sprintf(
 '
 .is-layout-constrained > *:not(.alignleft):not(.alignright):not(.alignfull) {
 max-width: %1$s;
 margin-left: auto !important;
 margin-right: auto !important;
 }
 .is-layout-constrained > .alignwide {
 max-width: %2$s;
 margin-left: auto !important;
 margin-right: auto !important;
 }
 ',
 $layout['contentSize'],
 $layout['wideSize'] ?? $layout['contentSize']
 );
 // Get styles from theme.
 $styles .= $this->theme_controller->get_stylesheet_for_rendering( $post, $template );
 $block_support_styles = $this->theme_controller->get_stylesheet_from_context( 'block-supports', array() );
 // Get styles from block-supports stylesheet. This includes rules such as layout (contentWidth) that some blocks use.
 // @see https://github.com/WordPress/WordPress/blob/3c5da9c74344aaf5bf8097f2e2c6a1a781600e03/wp-includes/script-loader.php#L3134
 // @internal :where is not supported by emogrifier, so we need to replace it with *.
 $block_support_styles = str_replace(
 ':where(:not(.alignleft):not(.alignright):not(.alignfull))',
 '*:not(.alignleft):not(.alignright):not(.alignfull)',
 $block_support_styles
 );
 $block_support_styles = preg_replace(
 '/group-is-layout-(\d+) >/',
 'group-is-layout-$1 > tbody tr td >',
 $block_support_styles
 );
 $styles .= $block_support_styles;
 return wp_strip_all_tags( (string) apply_filters( 'woocommerce_email_content_renderer_styles', $styles, $post ) );
 }
}
