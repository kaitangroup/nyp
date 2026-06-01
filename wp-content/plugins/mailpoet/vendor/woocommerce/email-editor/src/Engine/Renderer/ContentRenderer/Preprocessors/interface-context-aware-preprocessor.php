<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
interface Context_Aware_Preprocessor extends Preprocessor {
 public function preprocess_with_context( array $parsed_blocks, array $layout, array $styles, ?Rendering_Context $rendering_context = null ): array;
}
