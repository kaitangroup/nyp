<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;
if (!defined('ABSPATH')) exit;
class Html2Text_Exception extends \Exception {
 private string $more_info;
 public function __construct( string $message = '', string $more_info = '' ) {
 parent::__construct( $message );
 $this->more_info = $more_info;
 }
 public function get_more_info(): string {
 return $this->more_info;
 }
}
