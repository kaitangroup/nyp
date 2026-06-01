<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
class Buttons extends Abstract_Block_Renderer {
 private $flex_layout_renderer;
 public function __construct(
 Flex_Layout_Renderer $flex_layout_renderer
 ) {
 $this->flex_layout_renderer = $flex_layout_renderer;
 }
 public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 $content = $this->render_content( $block_content, $parsed_block, $rendering_context );
 $email_attrs = $parsed_block['email_attrs'] ?? array();
 unset( $email_attrs['margin-top'] );
 return $this->add_spacer_with_context( $content, $email_attrs, $rendering_context );
 }
 protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 // Ignore font size set on the buttons block.
 // We rely on TypographyPreprocessor to set the font size on the buttons.
 // Rendering font size on the wrapper causes unwanted whitespace below the buttons.
 if ( isset( $parsed_block['attrs']['style']['typography']['fontSize'] ) ) {
 unset( $parsed_block['attrs']['style']['typography']['fontSize'] );
 }
 return $this->flex_layout_renderer->render_inner_blocks_in_layout( $parsed_block, $rendering_context );
 }
}
