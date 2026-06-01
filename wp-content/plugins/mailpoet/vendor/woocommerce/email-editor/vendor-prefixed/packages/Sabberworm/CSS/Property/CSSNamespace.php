<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
class CSSNamespace implements AtRule, Positionable
{
 use Position;
 private $mUrl;
 private $sPrefix;
 private $iLineNo;
 protected $aComments;
 public function __construct($mUrl, $sPrefix = null, $iLineNo = 0)
 {
 $this->mUrl = $mUrl;
 $this->sPrefix = $sPrefix;
 $this->setPosition($iLineNo);
 $this->aComments = [];
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 return '@namespace ' . ($this->sPrefix === null ? '' : $this->sPrefix . ' ')
 . $this->mUrl->render($oOutputFormat) . ';';
 }
 public function getUrl()
 {
 return $this->mUrl;
 }
 public function getPrefix()
 {
 return $this->sPrefix;
 }
 public function setUrl($mUrl)
 {
 $this->mUrl = $mUrl;
 }
 public function setPrefix($sPrefix)
 {
 $this->sPrefix = $sPrefix;
 }
 public function atRuleName()
 {
 return 'namespace';
 }
 public function atRuleArgs()
 {
 $aResult = [$this->mUrl];
 if ($this->sPrefix) {
 array_unshift($aResult, $this->sPrefix);
 }
 return $aResult;
 }
 public function addComments(array $aComments)
 {
 $this->aComments = array_merge($this->aComments, $aComments);
 }
 public function getComments()
 {
 return $this->aComments;
 }
 public function setComments(array $aComments)
 {
 $this->aComments = $aComments;
 }
}
