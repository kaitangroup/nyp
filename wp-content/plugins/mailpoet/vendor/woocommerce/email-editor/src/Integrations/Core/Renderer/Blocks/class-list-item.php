<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
class List_Item extends Abstract_Block_Renderer {
 protected function add_spacer( $content, $email_attrs ): string {
 return $content;
 }
 protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
 return $block_content;
 }
}
