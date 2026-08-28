<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
class Personalizer {
 private const TAG_NAME_PATTERN = '[a-zA-Z0-9\-\/]+';
 public const RENDERING_CONTEXT_HTML = 'html';
 public const RENDERING_CONTEXT_TEXT = 'text';
 public const RENDERING_CONTEXT_HREF = 'href';
 public const RENDERING_CONTEXT_KEY = 'rendering_context';
 private Personalization_Tags_Registry $tags_registry;
 private array $context;
 public function __construct( Personalization_Tags_Registry $tags_registry ) {
 $this->tags_registry = $tags_registry;
 $this->context = array();
 }
 public function set_context( array $context ) {
 $this->context = $context;
 }
 public function get_context(): array {
 return $this->context;
 }
 public function personalize_content( string $content, string $rendering_context = self::RENDERING_CONTEXT_HTML ): string {
 if ( ! in_array( $rendering_context, array( self::RENDERING_CONTEXT_HTML, self::RENDERING_CONTEXT_TEXT ), true ) ) {
 $rendering_context = self::RENDERING_CONTEXT_HTML;
 }
 $content_processor = new HTML_Tag_Processor( $content );
 while ( $content_processor->next_token() ) {
 if ( $content_processor->get_token_type() === '#comment' ) {
 $modifiable_text = $content_processor->get_modifiable_text();
 $token = $this->parse_token( $modifiable_text );
 $tag = $this->tags_registry->get_by_token( $token['token'] );
 if ( ! $tag ) {
 continue;
 }
 $value = $tag->execute_callback( $this->get_callback_context( $rendering_context ), $token['arguments'] );
 if ( self::RENDERING_CONTEXT_HTML === $rendering_context && Personalization_Tag::VALUE_TYPE_TEXT === $tag->get_value_type() ) {
 $value = esc_html( $value );
 }
 $content_processor->replace_token( $value );
 } elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'TITLE' ) {
 // The title tag contains the subject of the email which should be personalized. HTML_Tag_Processor does parse the header tags.
 // The title content is effectively plain text, so it is personalized in the text rendering context.
 $modifiable_text = $content_processor->get_modifiable_text();
 $title = $this->personalize_content( $modifiable_text, self::RENDERING_CONTEXT_TEXT );
 $content_processor->set_modifiable_text( $title );
 } elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'A' && $content_processor->get_attribute( 'data-link-href' ) ) {
 // The anchor tag contains the data-link-href attribute which should be personalized.
 $href = (string) $content_processor->get_attribute( 'data-link-href' );
 $token = $this->parse_token( $href );
 $tag = $this->tags_registry->get_by_token( $token['token'] );
 if ( ! $tag ) {
 continue;
 }
 $value = $tag->execute_callback( $this->get_callback_context( self::RENDERING_CONTEXT_HREF ), $token['arguments'] );
 $value = $this->replace_link_href( $href, $tag->get_token(), $value );
 if ( '' !== $value ) {
 $content_processor->set_attribute( 'href', $value );
 $content_processor->remove_attribute( 'data-link-href' );
 $content_processor->remove_attribute( 'contenteditable' );
 }
 } elseif ( $content_processor->get_token_type() === '#tag' && $content_processor->get_tag() === 'A' ) {
 $href = $content_processor->get_attribute( 'href' );
 if ( ! is_string( $href ) ) {
 continue;
 }
 $personalized_href = $this->personalize_href_tokens( $href );
 if ( null !== $personalized_href ) {
 $content_processor->set_attribute( 'href', $personalized_href );
 }
 }
 }
 $content_processor->flush_updates();
 return $content_processor->get_updated_html();
 }
 private function personalize_href_tokens( string $href ): ?string {
 // Decode both URL encoding (%XX) and HTML entities (&#039;) to handle various encoding scenarios.
 $decoded_href = html_entity_decode( urldecode( $href ), ENT_QUOTES, 'UTF-8' );
 if ( ! preg_match_all( '/\[' . self::TAG_NAME_PATTERN . '(?:\s+[^\]]+)?\]/', $decoded_href, $matches ) ) {
 return null;
 }
 // Resolve every replaceable token first.
 $replacements = array();
 foreach ( array_unique( $matches[0] ) as $token_string ) {
 $token = $this->parse_token( $token_string );
 $tag = $this->tags_registry->get_by_token( $token['token'] );
 if ( ! $tag ) {
 continue;
 }
 $value = $tag->execute_callback( $this->get_callback_context( self::RENDERING_CONTEXT_HREF ), $token['arguments'] );
 if ( '' !== $value ) {
 $replacements[ $token_string ] = $value;
 }
 }
 if ( ! $replacements ) {
 return null;
 }
 // Prefer the original attribute value as the replacement base so legitimate
 // percent-encoding in the surrounding URL is preserved; fall back to the decoded
 // form when a replaced token occurrence exists only there (e.g. URL-encoded tokens).
 // Only tokens that are actually replaced matter here — an unregistered bracket
 // sequence that exists purely in the decoded form must not force the decoded base.
 // Known tradeoff: the base is chosen for the whole href, so when the decoded form
 // is used, unrelated percent-encoding elsewhere in the URL is decoded too.
 $base = $href;
 foreach ( array_keys( $replacements ) as $token_string ) {
 if ( substr_count( $href, $token_string ) !== substr_count( $decoded_href, $token_string ) ) {
 $base = $decoded_href;
 break;
 }
 }
 // The editor forces a protocol prefix when a tag is used as the whole URL
 // ("http://[tag]"). Strip it only when the token directly after it is being
 // replaced, so the tag value is used as-is while any suffix (e.g. appended
 // query parameters) is kept.
 if ( preg_match( '#^https?://(\[' . self::TAG_NAME_PATTERN . '(?:\s+[^\]]+)?\])#i', $base, $prefix_match ) && isset( $replacements[ $prefix_match[1] ] ) ) {
 $base = (string) preg_replace( '#^https?://#i', '', $base );
 }
 // Single-pass replacement — tag values are never re-scanned for other tokens,
 // and a regex replacement would interpret `$` and `\` in them.
 return strtr( $base, $replacements );
 }
 private function get_callback_context( string $rendering_context ): array {
 // array_replace() (unlike array_merge()) preserves integer keys in the consumer's context.
 return array_replace( $this->context, array( self::RENDERING_CONTEXT_KEY => $rendering_context ) );
 }
 private function parse_token( string $token ): array {
 $result = array(
 'token' => '',
 'arguments' => array(),
 );
 // Step 1: Separate the tag and attributes.
 if ( preg_match( '/^\[(' . self::TAG_NAME_PATTERN . ')\s*(.*?)\]$/', trim( $token ), $matches ) ) {
 $result['token'] = "[{$matches[1]}]"; // The tag part (e.g., "[mailpoet/subscriber-firstname]").
 $attributes_string = $matches[2]; // The attributes part (e.g., 'default="subscriber"').
 // Step 2: Extract attributes from the attribute string.
 // Match quoted values (double or single quotes separately to avoid mixing) and unquoted values.
 // Unquoted values can occur when esc_url() strips quotes from personalization tags.
 // For unquoted values with spaces, capture until the next key= pattern or closing bracket.
 // The negative lookahead (?!\w+=) is critical for preventing ReDoS:
 // it ensures the inner loop terminates as soon as the next key= pattern appears,
 // preventing excessive backtracking despite the nested quantifiers.
 if ( preg_match_all( '/(\w+)=(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+(?:\s+(?!\w+=)[^\s\]]+)*))/', $attributes_string, $attribute_matches, PREG_SET_ORDER ) ) {
 foreach ( $attribute_matches as $attribute ) {
 // $attribute[2] is double-quoted value, $attribute[3] is single-quoted value,
 // $attribute[4] is unquoted value (may contain spaces).
 // Use null coalescing as only one of these will be populated depending on which pattern matched.
 $double_quoted_value = $attribute[2] ?? '';
 $single_quoted_value = $attribute[3] ?? '';
 $unquoted_value = $attribute[4] ?? '';
 if ( '' !== $double_quoted_value ) {
 $result['arguments'][ $attribute[1] ] = $double_quoted_value;
 } elseif ( '' !== $single_quoted_value ) {
 $result['arguments'][ $attribute[1] ] = $single_quoted_value;
 } else {
 $result['arguments'][ $attribute[1] ] = $unquoted_value;
 }
 }
 }
 }
 return $result;
 }
 private function replace_link_href( string $content, string $token, string $replacement ) {
 // Escape the shortcode name for safe regex usage and strip the brackets.
 $escaped_shortcode = preg_quote( substr( $token, 1, strlen( $token ) - 2 ), '/' );
 // Create a regex pattern dynamically.
 $pattern = '/\[' . $escaped_shortcode . '(?:\s+[^\]]+)?\]/';
 // Escape `$` and `\` so they are inserted literally instead of being interpreted as backreferences.
 return trim( (string) preg_replace( $pattern, addcslashes( $replacement, '\\$' ), $content ) );
 }
}
