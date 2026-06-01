<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;
if (!defined('ABSPATH')) exit;
interface Block_Renderer {
 public function render( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string;
}
