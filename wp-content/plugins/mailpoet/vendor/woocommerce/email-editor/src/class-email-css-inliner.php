<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
class Email_Css_Inliner implements Css_Inliner {
 private $inliner;
 public function from_html( string $unprocessed_html ): self {
 $inliner_class = $this->get_inliner_class();
 $that = new self();
 $that->inliner = $inliner_class::fromHtml( $unprocessed_html );
 return $that;
 }
 public function inline_css( string $css = '' ): self {
 if ( ! isset( $this->inliner ) ) {
 throw new \LogicException( 'You must call from_html before calling inline_css' );
 }
 $this->inliner->inlineCss( $css );
 return $this;
 }
 public function render(): string {
 if ( ! isset( $this->inliner ) ) {
 throw new \LogicException( 'You must call from_html before calling render' );
 }
 return $this->inliner->render();
 }
 private function get_inliner_class(): string {
 if ( class_exists( 'Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner' ) ) {
 return 'Automattic\WooCommerce\Vendor\Pelago\Emogrifier\CssInliner';
 }
 if ( class_exists( 'Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\CssInliner' ) ) {
 return 'Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\CssInliner';
 }
 throw new \Exception( 'CssInliner class not found' );
 }
}
