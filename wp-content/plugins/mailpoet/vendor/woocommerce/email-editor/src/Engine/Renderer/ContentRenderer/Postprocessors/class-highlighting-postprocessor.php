<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;
if (!defined('ABSPATH')) exit;
class Highlighting_Postprocessor implements Postprocessor {
 public function postprocess( string $html ): string {
 return str_replace(
 array( '<mark', '</mark>' ),
 array( '<span', '</span>' ),
 $html
 );
 }
}
