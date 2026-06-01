<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
class Fallback extends Abstract_Block_Renderer {
 protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 $block_attrs = $parsed_block['attrs'] ?? array();
 $table_attrs = array(
 'style' => 'border-collapse: separate;', // Needed because of border radius.
 'width' => '100%',
 );
 $align = $rendering_context->sanitize_text_align( $block_attrs['textAlign'] ?? null )
 ?? $rendering_context->sanitize_text_align( $block_attrs['align'] ?? null )
 ?? $rendering_context->get_default_text_align();
 $cell_attrs = array(
 'align' => $align,
 );
 return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
 }
}
