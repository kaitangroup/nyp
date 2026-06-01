<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Context_Aware_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Quote_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Border_Style_Postprocessor;
class Process_Manager {
 private $preprocessors = array();
 private $postprocessors = array();
 public function __construct(
 Cleanup_Preprocessor $cleanup_preprocessor,
 Blocks_Width_Preprocessor $blocks_width_preprocessor,
 Typography_Preprocessor $typography_preprocessor,
 Spacing_Preprocessor $spacing_preprocessor,
 Quote_Preprocessor $quote_preprocessor,
 Highlighting_Postprocessor $highlighting_postprocessor,
 Variables_Postprocessor $variables_postprocessor,
 Border_Style_Postprocessor $border_style_postprocessor
 ) {
 $this->register_preprocessor( $cleanup_preprocessor );
 // Spacing must run before Width: it sets root-padding-left/right in
 // email_attrs, which the Width preprocessor reads to subtract root
 // padding only from blocks that actually receive it.
 $this->register_preprocessor( $spacing_preprocessor );
 $this->register_preprocessor( $blocks_width_preprocessor );
 $this->register_preprocessor( $typography_preprocessor );
 $this->register_preprocessor( $quote_preprocessor );
 $this->register_postprocessor( $highlighting_postprocessor );
 $this->register_postprocessor( $border_style_postprocessor );
 $this->register_postprocessor( $variables_postprocessor );
 }
 public function preprocess( array $parsed_blocks, array $layout, array $styles, ?Rendering_Context $rendering_context = null ): array {
 foreach ( $this->preprocessors as $preprocessor ) {
 $parsed_blocks = $preprocessor instanceof Context_Aware_Preprocessor
 ? $preprocessor->preprocess_with_context( $parsed_blocks, $layout, $styles, $rendering_context )
 : $preprocessor->preprocess( $parsed_blocks, $layout, $styles );
 }
 return $parsed_blocks;
 }
 public function postprocess( string $html ): string {
 foreach ( $this->postprocessors as $postprocessor ) {
 $html = $postprocessor->postprocess( $html );
 }
 return $html;
 }
 public function register_preprocessor( Preprocessor $preprocessor ): void {
 $this->preprocessors[] = $preprocessor;
 }
 public function register_postprocessor( Postprocessor $postprocessor ): void {
 $this->postprocessors[] = $postprocessor;
 }
}
