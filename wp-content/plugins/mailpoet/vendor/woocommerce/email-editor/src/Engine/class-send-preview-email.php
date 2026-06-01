<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Engine;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
class Send_Preview_Email {
 private Renderer $renderer;
 private Personalizer $personalizer;
 public function __construct(
 Renderer $renderer,
 Personalizer $personalizer
 ) {
 $this->renderer = $renderer;
 $this->personalizer = $personalizer;
 }
 public function send_preview_email( $data ): bool {
 if ( is_bool( $data ) ) {
 // preview mail already sent. Do not process again.
 return $data;
 }
 $this->validate_data( $data );
 $email = $data['email'];
 $post_id = $data['postId'];
 $post = $this->fetch_post( $post_id );
 $subject = $this->get_preview_email_subject( $post );
 $email_html_content = $this->render_html( $post );
 return $this->send_email( $email, $subject, $email_html_content );
 }
 public function render_html( $post ): string {
 $subject = $this->get_preview_email_subject( $post );
 $language = get_bloginfo( 'language' );
 // Add filter to set preview context for block renderers.
 add_filter( 'woocommerce_email_editor_rendering_email_context', array( $this, 'add_preview_context' ) );
 $rendered_data = $this->renderer->render(
 $post,
 $subject,
 __( 'Preview', 'woocommerce' ),
 $language
 );
 // Remove filter after rendering.
 remove_filter( 'woocommerce_email_editor_rendering_email_context', array( $this, 'add_preview_context' ) );
 $rendered_data = apply_filters( 'woocommerce_email_editor_send_preview_email_rendered_data', $rendered_data, $post );
 return $this->set_personalize_content( $rendered_data['html'] );
 }
 public function get_preview_email_subject( $post ): string {
 $subject = (string) apply_filters( 'woocommerce_email_editor_send_preview_email_subject', $post->post_title, $post );
 return $subject;
 }
 public function add_preview_context( $email_context ): array {
 $email_context['is_user_preview'] = true;
 return $email_context;
 }
 public function set_personalize_content( string $content ): string {
 $current_user = wp_get_current_user();
 $subscriber = ! empty( $current_user->ID ) ? $current_user : null;
 $personalizer_context = array(
 'recipient_email' => $subscriber ? $subscriber->user_email : null,
 'is_user_preview' => true,
 );
 $personalizer_context = apply_filters( 'woocommerce_email_editor_send_preview_email_personalizer_context', $personalizer_context );
 $this->personalizer->set_context( $personalizer_context );
 return $this->personalizer->personalize_content( $content );
 }
 public function send_email( string $to, string $subject, string $body ): bool {
 do_action( 'woocommerce_email_editor_send_preview_email_before_wp_mail', $to, $subject, $body );
 add_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );
 $result = wp_mail( $to, $subject, $body );
 // Reset content-type to avoid conflicts.
 remove_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );
 do_action( 'woocommerce_email_editor_send_preview_email_after_wp_mail', $to, $subject, $body, $result );
 return $result;
 }
 public function set_mail_content_type( string $content_type ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
 return 'text/html';
 }
 private function validate_data( array $data ) {
 if ( empty( $data['email'] ) || empty( $data['postId'] ) ) {
 throw new \InvalidArgumentException( esc_html__( 'Missing required data', 'woocommerce' ) );
 }
 if ( ! is_email( $data['email'] ) ) {
 throw new \InvalidArgumentException( esc_html__( 'Invalid email', 'woocommerce' ) );
 }
 }
 private function fetch_post( $post_id ): \WP_Post {
 $post = get_post( intval( $post_id ) );
 if ( ! $post instanceof \WP_Post ) {
 throw new \Exception( esc_html__( 'Invalid post', 'woocommerce' ) );
 }
 return $post;
 }
}
