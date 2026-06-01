<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
class Coupon_Code extends Abstract_Block_Renderer {
 const COUPON_CODE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';
 protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 $attrs = $parsed_block['attrs'] ?? array();
 $source = $attrs['source'] ?? 'createNew';
 if ( 'createNew' === $source ) {
 $coupon_code = apply_filters(
 'woocommerce_coupon_code_block_auto_generate',
 '',
 $attrs,
 $rendering_context
 );
 if ( empty( $coupon_code ) ) {
 return '';
 }
 $block_content = str_replace(
 self::COUPON_CODE_PLACEHOLDER,
 esc_html( $coupon_code ),
 $block_content
 );
 }
 $align = $attrs['align'] ?? 'center';
 if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
 $align = 'center';
 }
 $table_attrs = array(
 'style' => 'border-collapse: separate;',
 'width' => '100%',
 );
 $cell_attrs = array(
 'align' => $align,
 'style' => \WP_Style_Engine::compile_css(
 array(
 'text-align' => $align,
 ),
 ''
 ),
 );
 return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
 }
}
