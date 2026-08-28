<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;
if (!defined('ABSPATH')) exit;
class Post_Content {
 public function render_stateless( $attributes, $content, $block ): string {
 // This method is only called during email rendering, so we always use stateless logic.
 $post_id = $block->context['postId'] ?? null;
 if ( $post_id ) {
 $email_post = get_post( $post_id );
 } elseif ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof \WP_Post && 0 === $GLOBALS['post']->ID ) {
 // Synthetic posts (ID 0, e.g. rendering directly from a file template)
 // exist only as the global set up by the content renderer — the postId
 // block context is 0 and get_post() cannot resolve them. The ID check
 // keeps a real page's global post from ever leaking into email output.
 $email_post = $GLOBALS['post'];
 } else {
 return '';
 }
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
 // A synthetic post (ID 0) would make the query fetch latest posts, so
 // populate an empty query manually instead.
 if ( $email_post->ID > 0 ) {
 $wp_query = new \WP_Query( array( 'p' => $email_post->ID ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 } else {
 $wp_query = new \WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 $wp_query->post = $email_post;
 $wp_query->posts = array( $email_post );
 $wp_query->post_count = 1;
 $wp_query->found_posts = 1;
 }
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
