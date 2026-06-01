<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
class Spacing_Preprocessor implements Context_Aware_Preprocessor {
 private ?array $post_content_block_names = null;
 public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
 return $this->preprocess_with_context( $parsed_blocks, $layout, $styles );
 }
 public function preprocess_with_context( array $parsed_blocks, array $layout, array $styles, ?Rendering_Context $rendering_context = null ): array {
 $root_padding = $this->get_root_padding( $styles );
 $container_padding = $styles['__container_padding'] ?? array();
 $variables_map = $styles['__variables_map'] ?? array();
 $gap_padding_side = $rendering_context && $rendering_context->is_rtl() ? 'padding-right' : 'padding-left';
 $parsed_blocks = $this->add_block_gaps(
 $parsed_blocks,
 $styles['spacing']['blockGap'] ?? '',
 null,
 $root_padding,
 false,
 $container_padding,
 $variables_map,
 $gap_padding_side
 );
 return $parsed_blocks;
 }
 private function get_block_horizontal_padding( array $block, array $variables_map = array() ): array {
 $padding = $block['attrs']['style']['spacing']['padding'] ?? array();
 $has_left = isset( $padding['left'] );
 $has_right = isset( $padding['right'] );
 if ( ! $has_left && ! $has_right ) {
 return array();
 }
 $left = $has_left ? $padding['left'] : '0px';
 $right = $has_right ? $padding['right'] : '0px';
 if ( ! is_string( $left ) || ! is_string( $right ) || preg_match( '/[<>"\']/', $left . $right ) ) {
 return array();
 }
 // Resolve preset variable references (e.g. "var:preset|spacing|20")
 // to their pixel values so downstream consumers get usable CSS values.
 $left = Preset_Variable_Resolver::resolve( $left, $variables_map );
 $right = Preset_Variable_Resolver::resolve( $right, $variables_map );
 if ( $this->is_zero_value( $left ) && $this->is_zero_value( $right ) ) {
 return array();
 }
 return array(
 'left' => $left,
 'right' => $right,
 );
 }
 private const CONTAINER_BLOCKS = array( 'core/group', 'core/post-content' );
 private function add_block_gaps( array $parsed_blocks, string $gap = '', $parent_block = null, array $root_padding = array(), bool $apply_root_padding = false, array $container_padding = array(), array $variables_map = array(), string $gap_padding_side = 'padding-left' ): array {
 foreach ( $parsed_blocks as $key => $block ) {
 $block_name = $block['blockName'] ?? '';
 $parent_block_name = $parent_block['blockName'] ?? '';
 // Ensure that email_attrs are set.
 $block['email_attrs'] = $block['email_attrs'] ?? array();
 if ( 0 !== $key && $gap && 'core/buttons' !== $parent_block_name ) {
 $block['email_attrs']['margin-top'] = $gap;
 }
 // Handle horizontal gap for columns: apply physical padding to column children (except the first).
 if ( 'core/columns' === $parent_block_name && 0 !== $key && null !== $parent_block ) {
 $columns_gap = $this->get_columns_block_gap( $parent_block, $gap );
 if ( $columns_gap ) {
 $block['email_attrs'][ $gap_padding_side ] = $columns_gap;
 }
 }
 // Distribute root horizontal padding.
 // Container blocks (group, post-content) at root level do NOT get padding;
 // they delegate it to their children. Non-container blocks at root level
 // (e.g., columns, paragraph) get padding directly.
 // Blocks flagged with $apply_root_padding (children of root containers)
 // also get padding, unless they are post-content or a container wrapping
 // post-content (both delegate further down the tree).
 // Blocks that explicitly define their own horizontal padding are managing
 // their own layout and skip root padding entirely. Containers with explicit
 // padding also stop delegation so their children follow the container's padding.
 $is_root_level = null === $parent_block;
 $is_container = in_array( $block_name, self::CONTAINER_BLOCKS, true );
 $alignment = $block['attrs']['align'] ?? null;
 $has_zero_padding = $this->has_zero_horizontal_padding( $block );
 $has_own_padding = $this->has_explicit_horizontal_padding( $block );
 $wraps_post_content = $apply_root_padding && $is_container && $this->contains_post_content( $block );
 $should_apply = $apply_root_padding || ( $is_root_level && ! $is_container ) || ( $is_root_level && $is_container && $has_own_padding );
 $post_content_block_names = $this->get_post_content_block_names();
 if ( $should_apply && ! $has_zero_padding && 'full' !== $alignment && ! in_array( $block_name, $post_content_block_names, true ) && ! $wraps_post_content && ! empty( $root_padding ) ) {
 $block['email_attrs']['root-padding-left'] = $root_padding['left'];
 $block['email_attrs']['root-padding-right'] = $root_padding['right'];
 }
 // Apply container padding (from template group wrapping post-content).
 // Alignfull blocks skip both root and container padding.
 if ( $should_apply && ! $has_zero_padding && 'full' !== $alignment && ! in_array( $block_name, $post_content_block_names, true ) && ! $wraps_post_content && ! empty( $container_padding ) ) {
 $block['email_attrs']['container-padding-left'] = $container_padding['left'];
 $block['email_attrs']['container-padding-right'] = $container_padding['right'];
 }
 // Determine whether children should receive root padding delegation.
 // Root-level containers delegate to their children.
 // Post-content blocks that received delegation also pass it through.
 // Containers wrapping post-content that received delegation also delegate,
 // so that user blocks inside post-content get padding individually.
 // Containers with explicit horizontal padding stop delegation — they
 // manage their own layout.
 $children_apply = false;
 $children_container_pad = $container_padding;
 if ( $is_root_level && $is_container && ! $has_own_padding ) {
 $children_apply = true;
 } elseif ( $apply_root_padding && in_array( $block_name, $post_content_block_names, true ) ) {
 $children_apply = true;
 } elseif ( $wraps_post_content ) {
 $children_apply = true;
 // When a container wrapping post-content has its own non-zero
 // horizontal padding, distribute it as container-padding to
 // descendant blocks and suppress the container's own CSS padding.
 $block_padding = $this->get_block_horizontal_padding( $block, $variables_map );
 if ( ! empty( $block_padding ) ) {
 $children_container_pad = $block_padding;
 $block['email_attrs']['suppress-horizontal-padding'] = true;
 }
 } elseif ( $is_root_level && $is_container && $has_own_padding && ! $has_zero_padding && $this->contains_post_content( $block ) ) {
 // Root-level container with own padding that wraps post-content:
 // distribute its padding as container-padding and suppress its own CSS.
 $children_apply = true;
 $block_padding = $this->get_block_horizontal_padding( $block, $variables_map );
 if ( ! empty( $block_padding ) ) {
 $children_container_pad = $block_padding;
 $block['email_attrs']['suppress-horizontal-padding'] = true;
 }
 // This container also should not receive root padding itself
 // (it delegates everything to children).
 unset( $block['email_attrs']['root-padding-left'], $block['email_attrs']['root-padding-right'] );
 }
 $block['innerBlocks'] = $this->add_block_gaps( $block['innerBlocks'] ?? array(), $gap, $block, $root_padding, $children_apply, $children_container_pad, $variables_map, $gap_padding_side );
 $parsed_blocks[ $key ] = $block;
 }
 return $parsed_blocks;
 }
 private function get_post_content_block_names(): array {
 if ( null === $this->post_content_block_names ) {
 $this->post_content_block_names = (array) apply_filters(
 'woocommerce_email_editor_post_content_block_names',
 array( 'core/post-content' )
 );
 }
 return $this->post_content_block_names;
 }
 private function contains_post_content( array $block ): bool {
 $post_content_block_names = $this->get_post_content_block_names();
 foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
 $name = $inner_block['blockName'] ?? '';
 if ( in_array( $name, $post_content_block_names, true ) ) {
 return true;
 }
 if ( in_array( $name, self::CONTAINER_BLOCKS, true ) && $this->contains_post_content( $inner_block ) ) {
 return true;
 }
 }
 return false;
 }
 private function has_zero_horizontal_padding( array $block ): bool {
 $padding = $block['attrs']['style']['spacing']['padding'] ?? array();
 $left = $padding['left'] ?? null;
 $right = $padding['right'] ?? null;
 return $this->is_zero_value( $left ) || $this->is_zero_value( $right );
 }
 private function has_explicit_horizontal_padding( array $block ): bool {
 $padding = $block['attrs']['style']['spacing']['padding'] ?? array();
 return isset( $padding['left'] ) || isset( $padding['right'] );
 }
 private function is_zero_value( $value ): bool {
 if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
 return false;
 }
 return (bool) preg_match( '/^0(%|[a-z]*)?$/i', trim( (string) $value ) );
 }
 private function get_root_padding( array $styles ): array {
 $padding = $styles['spacing']['padding'] ?? array();
 $has_left = isset( $padding['left'] );
 $has_right = isset( $padding['right'] );
 // If neither horizontal padding key is defined, skip root padding entirely.
 if ( ! $has_left && ! $has_right ) {
 return array();
 }
 $left = $has_left ? $padding['left'] : '0px';
 $right = $has_right ? $padding['right'] : '0px';
 // Validate against potentially malicious values.
 if ( ! is_string( $left ) || ! is_string( $right ) || preg_match( '/[<>"\']/', $left . $right ) ) {
 return array();
 }
 return array(
 'left' => $left,
 'right' => $right,
 );
 }
 private function get_columns_block_gap( array $columns_block, string $default_gap = '' ): ?string {
 $block_gap = $columns_block['attrs']['style']['spacing']['blockGap'] ?? null;
 // Columns block uses object format: { "top": "...", "left": "..." }.
 // If blockGap.left is explicitly set, use it.
 if ( is_array( $block_gap ) && isset( $block_gap['left'] ) && is_string( $block_gap['left'] ) ) {
 $gap_value = $block_gap['left'];
 // Validate against potentially malicious values.
 if ( preg_match( '/[<>"\']/', $gap_value ) ) {
 return null;
 }
 // Return the value as-is. WP's styles engine will handle transformation of preset variables.
 return $gap_value;
 }
 // If blockGap.left is not set, use the default gap value if provided.
 if ( $default_gap ) {
 return $default_gap;
 }
 return null;
 }
}
