<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
class Social_Link extends Abstract_Block_Renderer {
 protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 // We are not using this because the blocks are rendered in the Social_Links block class.
 return $block_content;
 }
}
