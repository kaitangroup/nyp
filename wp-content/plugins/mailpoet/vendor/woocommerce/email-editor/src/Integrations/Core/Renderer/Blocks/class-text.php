<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
class Text extends Abstract_Block_Renderer {
 protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 // Do not render empty blocks.
 if ( empty( trim( wp_strip_all_tags( $block_content ) ) ) ) {
 return '';
 }
 $block_content = $this->adjustStyleAttribute( $block_content );
 $block_attributes = wp_parse_args(
 $parsed_block['attrs'] ?? array(),
 array(
 'style' => array(),
 )
 );
 $html = new \WP_HTML_Tag_Processor( $block_content );
 $classes = 'email-text-block';
 $alignment_from_class = null;
 if ( $html->next_tag() ) {
 // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
 $block_classes = $html->get_attribute( 'class' ) ?? '';
 $classes .= ' ' . $block_classes;
 // Extract text alignment from has-text-align-* classes before they're potentially modified.
 $class_attr = (string) $block_classes;
 if ( false !== strpos( $class_attr, 'has-text-align-center' ) ) {
 $alignment_from_class = 'center';
 } elseif ( false !== strpos( $class_attr, 'has-text-align-right' ) ) {
 $alignment_from_class = 'right';
 } elseif ( false !== strpos( $class_attr, 'has-text-align-left' ) ) {
 $alignment_from_class = 'left';
 }
 // remove has-background to prevent double padding applied for wrapper and inner element.
 $block_classes = str_replace( 'has-background', '', $block_classes );
 // remove border related classes because we handle border on wrapping table cell.
 $block_classes = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $block_classes );
 // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
 $html->set_attribute( 'class', trim( $block_classes ) );
 $block_content = $html->get_updated_html();
 }
 $block_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing', 'border', 'background-color', 'color', 'typography' ) );
 $additional_styles = array(
 'min-width' => '100%', // prevent Gmail App from shrinking the table on mobile devices.
 );
 // Add fallback text color when no custom text color or preset text color is set.
 if ( empty( $block_styles['declarations']['color'] ) ) {
 $email_styles = $rendering_context->get_theme_styles();
 $additional_styles['color'] = $parsed_block['email_attrs']['color'] ?? $email_styles['color']['text'] ?? '#000000'; // Fallback for the text color.
 }
 $additional_styles['text-align'] = $rendering_context->get_default_text_align();
 if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // in this case, textAlign needs to be one of 'left', 'center', 'right'.
 $additional_styles['text-align'] = $rendering_context->resolve_text_align( $parsed_block['attrs']['textAlign'] );
 } elseif ( null !== $rendering_context->sanitize_text_align( $parsed_block['attrs']['align'] ?? null ) ) {
 $additional_styles['text-align'] = $rendering_context->resolve_text_align( $parsed_block['attrs']['align'] );
 } elseif ( null !== $alignment_from_class ) {
 $additional_styles['text-align'] = $alignment_from_class;
 }
 $block_styles = Styles_Helper::extend_block_styles( $block_styles, $additional_styles );
 $table_attrs = array(
 'style' => 'border-collapse: separate;', // Needed because of border radius.
 'width' => '100%',
 );
 $cell_attrs = array(
 'class' => $classes,
 'style' => $block_styles['css'],
 'align' => $additional_styles['text-align'],
 );
 return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
 }
 private function adjustStyleAttribute( string $block_content ): string {
 $html = new \WP_HTML_Tag_Processor( $block_content );
 if ( $html->next_tag() ) {
 $element_style_value = $html->get_attribute( 'style' );
 $element_style = isset( $element_style_value ) ? strval( $element_style_value ) : '';
 // Padding may contain value like 10px or variable like var(--spacing-10).
 $element_style = (string) preg_replace( '/padding[^:]*:.?[0-9a-z-()]+;?/', '', $element_style );
 // Margin is not supported in email renderer, so we need to remove it.
 $element_style = (string) preg_replace( '/margin[^:]*:.?[0-9a-z-()]+;?/', '', $element_style );
 // Remove border styles. We apply border styles on the wrapping table cell.
 $element_style = (string) preg_replace( '/border[^:]*:.?[0-9a-z-()#]+;?/', '', $element_style );
 // We define the font-size on the wrapper element, but we need to keep font-size definition here
 // to prevent CSS Inliner from adding a default value and overriding the value set by user, which is on the wrapper element.
 // The value provided by WP uses clamp() function which is not supported in many email clients.
 $element_style = (string) preg_replace( '/font-size:[^;]+;?/', 'font-size: inherit;', $element_style );
 // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
 $html->set_attribute( 'style', esc_attr( $element_style ) );
 $block_content = $html->get_updated_html();
 }
 return $block_content;
 }
}
