<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;
if (!defined('ABSPATH')) exit;
interface Preprocessor {
 public function preprocess( array $parsed_blocks, array $layout, array $styles ): array;
}
