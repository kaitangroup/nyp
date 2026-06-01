<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
class Group extends Abstract_Block_Renderer {
 protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 return str_replace(
 '{group_content}',
 $this->get_inner_content( $block_content ),
 $this->get_block_wrapper( $block_content, $parsed_block, $rendering_context )
 );
 }
 private function get_block_wrapper( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 $original_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';
 $block_attributes = wp_parse_args(
 $parsed_block['attrs'] ?? array(),
 array(
 'style' => array(),
 'backgroundColor' => '',
 'textColor' => '',
 'borderColor' => '',
 'layout' => array(),
 )
 );
 $table_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background', 'background-color', 'color', 'text-align' ) );
 $table_styles = Styles_Helper::extend_block_styles(
 $table_styles,
 array_filter(
 array(
 'border-collapse' => 'separate',
 'background-size' => $table_styles['background-size'] ?? 'cover',
 )
 )
 );
 // Padding properties need to be added to the table cell.
 // When suppress-horizontal-padding is set, the container's horizontal
 // padding has been distributed per-block by the Spacing_Preprocessor.
 // Only vertical padding is kept on the group's own CSS output.
 $cell_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'padding' ) );
 if ( ! empty( $parsed_block['email_attrs']['suppress-horizontal-padding'] ) ) {
 $cell_styles = $this->remove_horizontal_padding( $cell_styles );
 }
 $table_attrs = array(
 'class' => 'email-block-group ' . $original_classname,
 'style' => $table_styles['css'],
 'width' => '100%',
 );
 $cell_attrs = array(
 'class' => 'email-block-group-content',
 'style' => $cell_styles['css'],
 'width' => $parsed_block['email_attrs']['width'] ?? '100%',
 );
 return Table_Wrapper_Helper::render_table_wrapper( '{group_content}', $table_attrs, $cell_attrs );
 }
 private function remove_horizontal_padding( array $cell_styles ): array {
 if ( ! isset( $cell_styles['declarations'] ) || ! is_array( $cell_styles['declarations'] ) ) {
 return $cell_styles;
 }
 unset(
 $cell_styles['declarations']['padding-left'],
 $cell_styles['declarations']['padding-right']
 );
 // Rebuild CSS string from remaining declarations.
 return Styles_Helper::extend_block_styles( $cell_styles, array() );
 }
}
