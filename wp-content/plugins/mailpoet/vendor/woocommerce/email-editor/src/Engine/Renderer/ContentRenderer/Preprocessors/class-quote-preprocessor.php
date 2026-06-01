<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;
if (!defined('ABSPATH')) exit;
class Quote_Preprocessor implements Preprocessor {
 public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
 return $this->process_blocks( $parsed_blocks, $styles );
 }
 private function process_blocks( array $blocks, array $styles ): array {
 foreach ( $blocks as &$block ) {
 if ( ! isset( $block['innerBlocks'] ) ) {
 continue;
 }
 if ( 'core/quote' === $block['blockName'] ) {
 $quote_align = $block['attrs']['textAlign'] ?? null;
 $quote_typography = $block['attrs']['style']['typography'] ?? array();
 // Apply quote's text alignment to its children.
 $block['innerBlocks'] = $this->apply_alignment_to_children( $block['innerBlocks'], $quote_align );
 // Apply quote's typography to its children.
 $block['innerBlocks'] = $this->apply_typography_to_children( $block['innerBlocks'], $quote_typography, $styles );
 }
 $block['innerBlocks'] = $this->process_blocks( $block['innerBlocks'], $styles );
 }
 return $blocks;
 }
 private function apply_alignment_to_children( array $blocks, ?string $text_align = null ): array {
 if ( ! $text_align ) {
 return $blocks;
 }
 foreach ( $blocks as &$block ) {
 // Only apply alignment if the block doesn't already have one set.
 if ( ! isset( $block['attrs']['textAlign'] ) && ! isset( $block['attrs']['align'] ) ) {
 if ( ! isset( $block['attrs'] ) ) {
 $block['attrs'] = array();
 }
 $block['attrs']['textAlign'] = $text_align;
 }
 if ( isset( $block['innerBlocks'] ) ) {
 $block['innerBlocks'] = $this->apply_alignment_to_children( $block['innerBlocks'], $block['attrs']['textAlign'] ?? $block['attrs']['align'] );
 }
 }
 return $blocks;
 }
 private function apply_typography_to_children( array $blocks, array $quote_typography, array $styles ): array {
 $default_typography = $styles['blocks']['core/quote']['typography'] ?? array();
 $merged_typography = array_merge( $default_typography, $quote_typography );
 if ( empty( $merged_typography ) ) {
 return $blocks;
 }
 foreach ( $blocks as &$block ) {
 if ( 'core/paragraph' === $block['blockName'] ) {
 if ( ! isset( $block['attrs'] ) ) {
 $block['attrs'] = array();
 }
 if ( ! isset( $block['attrs']['style'] ) ) {
 $block['attrs']['style'] = array();
 }
 if ( ! isset( $block['attrs']['style']['typography'] ) ) {
 $block['attrs']['style']['typography'] = array();
 }
 // Merge typography styles, with block's own styles taking precedence.
 $block['attrs']['style']['typography'] = array_merge(
 $merged_typography,
 $block['attrs']['style']['typography']
 );
 }
 }
 return $blocks;
 }
}
