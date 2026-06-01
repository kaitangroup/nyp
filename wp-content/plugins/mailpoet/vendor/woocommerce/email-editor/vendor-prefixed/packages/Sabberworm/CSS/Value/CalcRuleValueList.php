<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
class CalcRuleValueList extends RuleValueList
{
 public function __construct($iLineNo = 0)
 {
 parent::__construct(',', $iLineNo);
 }
 public function render($oOutputFormat)
 {
 return $oOutputFormat->implode(' ', $this->aComponents);
 }
}
