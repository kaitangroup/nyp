<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;
if (!defined('ABSPATH')) exit;
class Table_Wrapper_Helper {
 private const DEFAULT_TABLE_ATTRS = array(
 'border' => '0',
 'cellpadding' => '0',
 'cellspacing' => '0',
 'role' => 'presentation',
 );
 public static function render_table_cell(
 string $content,
 array $cell_attrs = array()
 ): string {
 $cell_attr_string = self::build_attributes_string( $cell_attrs );
 return sprintf(
 '<td%1$s>%2$s</td>',
 $cell_attr_string ? ' ' . $cell_attr_string : '',
 $content
 );
 }
 public static function render_outlook_table_cell(
 string $content,
 array $cell_attrs = array()
 ): string {
 $content_with_outlook_conditional = '<![endif]-->' . $content . '<!--[if mso | IE]>';
 return '<!--[if mso | IE]>' . self::render_table_cell( $content_with_outlook_conditional, $cell_attrs ) . '<![endif]-->';
 }
 public static function render_table_wrapper(
 string $content,
 array $table_attrs = array(),
 array $cell_attrs = array(),
 array $row_attrs = array(),
 bool $render_cell = true
 ): string {
 $merged_table_attrs = array_merge( self::DEFAULT_TABLE_ATTRS, $table_attrs );
 $table_attr_string = self::build_attributes_string( $merged_table_attrs );
 $row_attr_string = self::build_attributes_string( $row_attrs );
 if ( $render_cell ) {
 $content = self::render_table_cell( $content, $cell_attrs );
 }
 return sprintf(
 '<table%2$s>
 <tbody>
 <tr%3$s>
 %1$s
 </tr>
 </tbody>
 </table>',
 $content,
 $table_attr_string ? ' ' . $table_attr_string : '',
 $row_attr_string ? ' ' . $row_attr_string : ''
 );
 }
 public static function render_outlook_table_wrapper(
 string $content,
 array $table_attrs = array(),
 array $cell_attrs = array(),
 array $row_attrs = array(),
 bool $render_cell = true
 ): string {
 $content_with_outlook_conditional = '<![endif]-->' . $content . '<!--[if mso | IE]>';
 return '<!--[if mso | IE]>' . self::render_table_wrapper( $content_with_outlook_conditional, $table_attrs, $cell_attrs, $row_attrs, $render_cell ) . '<![endif]-->';
 }
 private static function build_attributes_string( array $attributes ): string {
 $attr_parts = array();
 foreach ( $attributes as $key => $value ) {
 if ( '' !== $value ) {
 $attr_parts[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
 }
 }
 return implode( ' ', $attr_parts );
 }
}
