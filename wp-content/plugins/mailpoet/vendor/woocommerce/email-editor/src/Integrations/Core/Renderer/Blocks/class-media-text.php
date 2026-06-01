<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
class Media_Text extends Abstract_Block_Renderer {
 protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 $block_attrs = $parsed_block['attrs'] ?? array();
 $inner_blocks = $parsed_block['innerBlocks'] ?? array();
 // Extract media content from innerHTML.
 $media_content = $this->extract_media_from_html( $parsed_block['innerHTML'] ?? $block_content );
 // Render all inner blocks content.
 $text_content = '';
 foreach ( $inner_blocks as $block ) {
 $text_content .= render_block( $block );
 }
 // If we don't have both media and text content, return empty.
 if ( empty( $media_content ) || empty( $text_content ) ) {
 return '';
 }
 // Build the email-friendly layout.
 return $this->build_email_layout( $media_content, $text_content, $block_attrs, $block_content, $rendering_context );
 }
 private function extract_media_from_html( string $block_content ): string {
 // Extract inner content from figure element (removing figure wrapper for email compatibility).
 $media_content = '';
 if ( preg_match( '/<figure[^>]*class="[^"]*\bwp-block-media-text__media\b[^"]*"[^>]*>(.*?)<\/figure>/s', $block_content, $matches ) ) {
 $media_content = trim( $matches[1] );
 }
 return $media_content;
 }
 private function build_email_layout( string $media_content, string $text_content, array $block_attrs, string $block_content, Rendering_Context $rendering_context ): string {
 // Get original wrapper classes from block content.
 $original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
 // Get layout attributes.
 $media_position = $block_attrs['mediaPosition'] ?? $rendering_context->get_start_side();
 $vertical_alignment = $this->get_vertical_alignment_from_attributes( $block_attrs );
 $media_width = $this->get_media_width_from_attributes( $block_attrs );
 $text_width = 100 - $media_width; // Text takes the remaining width.
 // Handle image linking for any linkDestination type that has an href.
 if ( ! empty( $block_attrs['href'] ) ) {
 $media_content = $this->wrap_media_with_link( $media_content, $block_attrs['href'] );
 }
 // Get block styles using the Styles_Helper.
 $block_styles = Styles_Helper::get_block_styles( $block_attrs, $rendering_context, array( 'padding', 'border', 'background', 'background-color', 'color' ) );
 $block_styles = Styles_Helper::extend_block_styles(
 $block_styles,
 array(
 'width' => '100%',
 'border-collapse' => 'collapse',
 'text-align' => $rendering_context->get_default_text_align(),
 )
 );
 // Apply class and style attributes to the wrapper table.
 $table_attrs = array(
 'class' => 'email-block-media-text ' . $original_wrapper_classname,
 'style' => $block_styles['css'],
 'align' => $rendering_context->get_default_text_align(),
 'width' => '100%',
 );
 // Build individual table cells.
 $media_cell_attrs = array(
 'style' => sprintf( 'width: %d%%; padding: 10px; vertical-align: %s;', $media_width, $vertical_alignment ),
 'valign' => $vertical_alignment,
 );
 $text_cell_attrs = array(
 'style' => sprintf( 'width: %d%%; padding: 0 8%%; vertical-align: %s;', $text_width, $vertical_alignment ),
 'valign' => $vertical_alignment,
 );
 $media_cell = Table_Wrapper_Helper::render_table_cell( $media_content, $media_cell_attrs );
 $text_cell = Table_Wrapper_Helper::render_table_cell( $text_content, $text_cell_attrs );
 // Order cells based on media position.
 if ( 'right' === $media_position ) {
 // Text first, then media.
 $cells = $text_cell . $media_cell;
 } else {
 // Media first, then text (default left position).
 $cells = $media_cell . $text_cell;
 }
 // Use render_cell = false to avoid wrapping in an extra <td>.
 return Table_Wrapper_Helper::render_table_wrapper( $cells, $table_attrs, array(), array(), false );
 }
 private function get_vertical_alignment_from_attributes( array $block_attrs ): string {
 $vertical_alignment = $block_attrs['verticalAlignment'] ?? 'middle';
 // Convert WordPress alignment values to CSS values.
 switch ( $vertical_alignment ) {
 case 'top':
 return 'top';
 case 'center':
 return 'middle';
 case 'bottom':
 return 'bottom';
 default:
 return 'middle';
 }
 }
 private function get_media_width_from_attributes( array $block_attrs ): int {
 $media_width = $block_attrs['mediaWidth'] ?? 50;
 // Ensure the width is within reasonable bounds.
 $media_width = max( 1, min( 99, (int) $media_width ) );
 return $media_width;
 }
 private function wrap_media_with_link( string $media_content, string $href ): string {
 // If media is already wrapped in a link, return as-is.
 if ( false !== strpos( $media_content, '<a ' ) ) {
 return $media_content;
 }
 // Wrap the media content with a link.
 return '<a href="' . esc_url( $href ) . '">' . $media_content . '</a>';
 }
}
