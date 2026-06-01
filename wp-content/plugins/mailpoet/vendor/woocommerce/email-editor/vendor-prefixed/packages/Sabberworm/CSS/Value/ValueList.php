<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
abstract class ValueList extends Value
{
 protected $aComponents;
 protected $sSeparator;
 public function __construct($aComponents = [], $sSeparator = ',', $iLineNo = 0)
 {
 parent::__construct($iLineNo);
 if (!is_array($aComponents)) {
 $aComponents = [$aComponents];
 }
 $this->aComponents = $aComponents;
 $this->sSeparator = $sSeparator;
 }
 public function addListComponent($mComponent)
 {
 $this->aComponents[] = $mComponent;
 }
 public function getListComponents()
 {
 return $this->aComponents;
 }
 public function setListComponents(array $aComponents)
 {
 $this->aComponents = $aComponents;
 }
 public function getListSeparator()
 {
 return $this->sSeparator;
 }
 public function setListSeparator($sSeparator)
 {
 $this->sSeparator = $sSeparator;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 return $oOutputFormat->implode(
 $oOutputFormat->spaceBeforeListArgumentSeparator($this->sSeparator) . $this->sSeparator
 . $oOutputFormat->spaceAfterListArgumentSeparator($this->sSeparator),
 $this->aComponents
 );
 }
}
