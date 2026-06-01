<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
class SourceException extends \Exception implements Positionable
{
 use Position;
 public function __construct($sMessage, $iLineNo = 0)
 {
 $this->setPosition($iLineNo);
 if (!empty($iLineNo)) {
 $sMessage .= " [line no: $iLineNo]";
 }
 parent::__construct($sMessage);
 }
}
