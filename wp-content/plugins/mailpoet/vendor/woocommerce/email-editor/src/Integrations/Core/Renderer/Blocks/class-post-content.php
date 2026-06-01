<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
class Post_Content {
 public function render_stateless( $attributes, $content, $block ): string {
 // This method is only called during email rendering, so we always use stateless logic.
 $post_id = $block->context['postId'] ?? null;
 if ( ! $post_id ) {
 return '';
 }
 $email_post = get_post( $post_id );
 if ( ! $email_post || empty( $email_post->post_content ) ) {
 return '';
 }
 // Backup global state.
 global $post, $wp_query;
 $backup_post = $post;
 $backup_query = $wp_query;
 // Set up global state for block rendering.
 // This ensures that blocks which depend on global $post work correctly.
 $post = $email_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 // Create a query specifically for this post to ensure proper context.
 $wp_query = new \WP_Query( array( 'p' => $post_id ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 // Get raw post content and apply the_content filter.
 // The the_content filter processes blocks, shortcodes, etc.
 // We don't use get_the_content() to avoid issues with loop state.
 $post_content = $email_post->post_content;
 // Check for nextpage to display page links for paginated posts.
 if ( has_block( 'core/nextpage', $email_post ) ) {
 $post_content .= wp_link_pages( array( 'echo' => 0 ) );
 }
 // Apply the_content filter to process blocks.
 $post_content = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $post_content ) );
 // Restore global state.
 $post = $backup_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 $wp_query = $backup_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 if ( empty( $post_content ) ) {
 return '';
 }
 return $post_content;
 }
}
