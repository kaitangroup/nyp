<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\AtRule;
class AtRuleBlockList extends CSSBlockList implements AtRule
{
 private $sType;
 private $sArgs;
 public function __construct($sType, $sArgs = '', $iLineNo = 0)
 {
 parent::__construct($iLineNo);
 $this->sType = $sType;
 $this->sArgs = $sArgs;
 }
 public function atRuleName()
 {
 return $this->sType;
 }
 public function atRuleArgs()
 {
 return $this->sArgs;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 $sResult = $oOutputFormat->comments($this);
 $sResult .= $oOutputFormat->sBeforeAtRuleBlock;
 $sArgs = $this->sArgs;
 if ($sArgs) {
 $sArgs = ' ' . $sArgs;
 }
 $sResult .= "@{$this->sType}$sArgs{$oOutputFormat->spaceBeforeOpeningBrace()}{";
 $sResult .= $this->renderListContents($oOutputFormat);
 $sResult .= '}';
 $sResult .= $oOutputFormat->sAfterAtRuleBlock;
 return $sResult;
 }
 public function isRootList()
 {
 return false;
 }
}
