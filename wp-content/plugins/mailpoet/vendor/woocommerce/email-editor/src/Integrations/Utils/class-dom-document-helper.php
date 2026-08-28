<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;
if (!defined('ABSPATH')) exit;
class Dom_Document_Helper {
 private \DOMDocument $dom;
 public function __construct( string $html_content ) {
 $this->load_html( $html_content );
 }
 private function load_html( string $html_content ): void {
 libxml_use_internal_errors( true );
 $this->dom = new \DOMDocument();
 if ( ! empty( $html_content ) ) {
 // prefixing the content with the XML declaration to force the input encoding to UTF-8.
 $this->dom->loadHTML( '<?xml encoding="UTF-8">' . $html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
 }
 libxml_clear_errors();
 }
 public function find_element( string $tag_name ): ?\DOMElement {
 $elements = $this->dom->getElementsByTagName( $tag_name );
 return $elements->item( 0 ) ? $elements->item( 0 ) : null;
 }
 public function find_elements( string $tag_name ): array {
 $elements = array();
 foreach ( $this->dom->getElementsByTagName( $tag_name ) as $element ) {
 if ( $element instanceof \DOMElement ) {
 $elements[] = $element;
 }
 }
 return $elements;
 }
 public function get_attribute_value( \DOMElement $element, string $attribute ): string {
 return $element->hasAttribute( $attribute ) ? $element->getAttribute( $attribute ) : '';
 }
 public function get_attribute_value_by_tag_name( string $tag_name, string $attribute ): ?string {
 $element = $this->find_element( $tag_name );
 if ( ! $element ) {
 return null;
 }
 return $this->get_attribute_value( $element, $attribute );
 }
 public function get_outer_html( \DOMElement $element ): string {
 return (string) $this->dom->saveHTML( $element );
 }
 public function remove_element( \DOMElement $element ): void {
 $parent = $element->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 if ( $parent instanceof \DOMNode ) {
 $parent->removeChild( $element );
 }
 }
 public function get_root_html(): string {
 $html = '';
 foreach ( $this->dom->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 // Skip the `<?xml encoding="UTF-8">` processing instruction that load_html() prepends to
 // force UTF-8; serializing it back would corrupt callers (e.g. strip_tags treats the
 // unterminated `<?` as a tag and swallows the rest of the string).
 if ( XML_PI_NODE === $child->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 continue;
 }
 $html .= (string) $this->dom->saveHTML( $child );
 }
 return $html;
 }
 public function get_element_inner_html( \DOMElement $element ): string {
 $inner_html = '';
 $children = $element->childNodes; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 foreach ( $children as $child ) {
 $inner_html .= $this->dom->saveHTML( $child );
 }
 return $inner_html;
 }
}
