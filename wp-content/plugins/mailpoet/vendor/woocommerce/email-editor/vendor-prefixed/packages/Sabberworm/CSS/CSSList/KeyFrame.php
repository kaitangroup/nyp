<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\AtRule;
class KeyFrame extends CSSList implements AtRule
{
 private $vendorKeyFrame;
 private $animationName;
 public function __construct($iLineNo = 0)
 {
 parent::__construct($iLineNo);
 $this->vendorKeyFrame = null;
 $this->animationName = null;
 }
 public function setVendorKeyFrame($vendorKeyFrame)
 {
 $this->vendorKeyFrame = $vendorKeyFrame;
 }
 public function getVendorKeyFrame()
 {
 return $this->vendorKeyFrame;
 }
 public function setAnimationName($animationName)
 {
 $this->animationName = $animationName;
 }
 public function getAnimationName()
 {
 return $this->animationName;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 $sResult = $oOutputFormat->comments($this);
 $sResult .= "@{$this->vendorKeyFrame} {$this->animationName}{$oOutputFormat->spaceBeforeOpeningBrace()}{";
 $sResult .= $this->renderListContents($oOutputFormat);
 $sResult .= '}';
 return $sResult;
 }
 public function isRootList()
 {
 return false;
 }
 public function atRuleName()
 {
 return $this->vendorKeyFrame;
 }
 public function atRuleArgs()
 {
 return $this->animationName;
 }
}
