<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
class Flex_Layout_Renderer {
 public function render_inner_blocks_in_layout( array $parsed_block, Rendering_Context $rendering_context ): string {
 $theme_styles = $rendering_context->get_theme_styles();
 $flex_gap = $theme_styles['spacing']['blockGap'] ?? '0px';
 $flex_gap_number = Styles_Helper::parse_value( $flex_gap );
 $margin_top = $parsed_block['email_attrs']['margin-top'] ?? '0px';
 $justify = $rendering_context->resolve_text_align( $parsed_block['attrs']['layout']['justifyContent'] ?? null );
 $styles = wp_style_engine_get_styles( $parsed_block['attrs']['style'] ?? array() )['css'] ?? '';
 $styles .= 'margin-top: ' . $margin_top . ';';
 $styles .= 'text-align: ' . $justify;
 list( $inner_blocks, $should_wrap ) = $this->compute_widths_for_flex_layout( $parsed_block, $flex_gap_number );
 if ( $should_wrap ) {
 return $this->render_wrapping_layout( $inner_blocks, $styles, $justify, $flex_gap, $rendering_context );
 }
 return $this->render_single_row_layout( $inner_blocks, $styles, $justify, $flex_gap, $rendering_context );
 }
 private function render_single_row_layout( array $inner_blocks, string $styles, string $justify, string $flex_gap, Rendering_Context $rendering_context ): string {
 // MS Outlook doesn't support style attribute in divs so we conditionally wrap the buttons in a table and repeat styles.
 $output_html = sprintf(
 '<!--[if mso | IE]><table align="%2$s" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%%"><tr><td style="%1$s" ><![endif]-->
 <div style="%1$s"><table class="layout-flex-wrapper" style="display:inline-block;vertical-align:top"><tbody><tr>',
 esc_attr( $styles ),
 esc_attr( $justify )
 );
 foreach ( $inner_blocks as $key => $block ) {
 $item_styles = array();
 if ( $block['email_attrs']['layout_width'] ?? null ) {
 $item_styles['width'] = $block['email_attrs']['layout_width'];
 }
 if ( $key > 0 ) {
 $item_styles[ 'padding-' . $rendering_context->get_start_side() ] = $flex_gap;
 }
 $output_html .= '<td class="layout-flex-item" style="' . esc_attr( \WP_Style_Engine::compile_css( $item_styles, '' ) ) . '">' . render_block( $block ) . '</td>';
 }
 $output_html .= '</tr></table></div>
 <!--[if mso | IE]></td></tr></table><![endif]-->';
 return $output_html;
 }
 private function render_wrapping_layout( array $inner_blocks, string $styles, string $justify, string $flex_gap, Rendering_Context $rendering_context ): string {
 // Outlook ignores style on divs, so repeat the wrapper styles on a conditional table cell.
 $output_html = sprintf(
 '<!--[if mso | IE]><table align="%2$s" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%%"><tr><td style="%1$s" ><![endif]-->
 <div class="layout-flex-wrapper" style="%1$s">',
 esc_attr( $styles ),
 esc_attr( $justify )
 );
 // The gap padding must sit on the side away from the aligned (flush) edge, otherwise wrapped
 // lines carry an edge pad and misalign against each other. When items align to the end side
 // of the line flow (e.g. right in LTR), pad the start side of every item after the first;
 // otherwise (start-aligned or centered) pad the end side of every item before the last.
 $align_to_flow_end = $justify === $rendering_context->get_end_side();
 $last_key = count( $inner_blocks ) - 1;
 foreach ( $inner_blocks as $key => $block ) {
 $item_styles = array(
 'display' => 'inline-block',
 'vertical-align' => 'top',
 // Vertical gap so items that wrap onto a new line (and Outlook's stacked buttons) don't touch.
 'padding-bottom' => $flex_gap,
 );
 if ( $block['email_attrs']['layout_width'] ?? null ) {
 $item_styles['width'] = $block['email_attrs']['layout_width'];
 }
 if ( $align_to_flow_end && $key > 0 ) {
 $item_styles[ 'padding-' . $rendering_context->get_start_side() ] = $flex_gap;
 } elseif ( ! $align_to_flow_end && $key < $last_key ) {
 $item_styles[ 'padding-' . $rendering_context->get_end_side() ] = $flex_gap;
 }
 if ( $key > 0 ) {
 // Force a line break before every item after the first in Outlook, which can't wrap an inline row.
 $output_html .= '<!--[if mso | IE]><br><![endif]-->';
 }
 $output_html .= '<div class="layout-flex-item" style="' . esc_attr( \WP_Style_Engine::compile_css( $item_styles, '' ) ) . '">' . render_block( $block ) . '</div>';
 }
 $output_html .= '</div>
 <!--[if mso | IE]></td></tr></table><![endif]-->';
 return $output_html;
 }
 private function compute_widths_for_flex_layout( array $parsed_block, float $flex_gap ): array {
 // When there is no parent width we can't compute widths so auto width will be used, and we
 // can't tell whether the items overflow — fall back to the (non-wrapping) single row.
 if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
 return array( $parsed_block['innerBlocks'] ?? array(), false );
 }
 $blocks_count = count( $parsed_block['innerBlocks'] );
 $total_used_width = 0; // Total width assuming items without set width would consume proportional width.
 $parent_width = Styles_Helper::parse_value( $parsed_block['email_attrs']['width'] );
 $inner_blocks = $parsed_block['innerBlocks'] ?? array();
 $has_auto_width = false;
 foreach ( $inner_blocks as $key => $block ) {
 $block_width_percent = ( $block['attrs']['width'] ?? 0 ) ? intval( $block['attrs']['width'] ) : 0;
 $block_width = floor( $parent_width * ( $block_width_percent / 100 ) );
 // If width is not set, we assume it's 25% of the parent width.
 $total_used_width += $block_width ? $block_width : floor( $parent_width * ( 25 / 100 ) );
 if ( ! $block_width ) {
 $has_auto_width = true;
 $inner_blocks[ $key ]['email_attrs']['layout_width'] = null; // Will be rendered as auto.
 continue;
 }
 $inner_blocks[ $key ]['email_attrs']['layout_width'] = $this->get_width_without_gap( $block_width, $flex_gap, $block_width_percent ) . 'px';
 }
 $overflows = $blocks_count > 1 && $total_used_width > $parent_width;
 // When there is only one block, or the items fit, render the single row as set by the user.
 if ( ! $overflows ) {
 return array( $inner_blocks, false );
 }
 // Auto-width items (e.g. a nav menu of buttons) can't be shrunk to fit, so an overflowing row
 // that contains any of them can only be kept inside the content width by wrapping — the
 // explicitly-sized items in the same row keep their width and flow alongside. A row made up
 // entirely of explicit-width items is instead scaled down proportionally so it still fits on
 // one row, preserving the existing behavior.
 if ( $has_auto_width ) {
 return array( $inner_blocks, true );
 }
 foreach ( $inner_blocks as $key => $block ) {
 $proportional_space_overflow = $parent_width / $total_used_width;
 $block_width = $block['email_attrs']['layout_width'] ? Styles_Helper::parse_value( $block['email_attrs']['layout_width'] ) : 0;
 $block_proportional_width = $block_width * $proportional_space_overflow;
 $block_proportional_percentage = ( $block_proportional_width / $parent_width ) * 100;
 $inner_blocks[ $key ]['email_attrs']['layout_width'] = $block_width ? $this->get_width_without_gap( $block_proportional_width, $flex_gap, $block_proportional_percentage ) . 'px' : null;
 }
 return array( $inner_blocks, false );
 }
 private function get_width_without_gap( float $block_width, float $flex_gap, float $block_width_percent ): int {
 $width_gap_reduction = $flex_gap * ( ( 100 - $block_width_percent ) / 100 );
 return intval( floor( $block_width - $width_gap_reduction ) );
 }
}
