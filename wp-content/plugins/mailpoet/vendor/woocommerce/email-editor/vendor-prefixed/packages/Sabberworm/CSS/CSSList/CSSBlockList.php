<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Rule\Rule;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\RuleSet;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\CSSFunction;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\Value;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\ValueList;
abstract class CSSBlockList extends CSSList
{
 public function __construct($iLineNo = 0)
 {
 parent::__construct($iLineNo);
 }
 protected function allDeclarationBlocks(array &$aResult)
 {
 foreach ($this->aContents as $mContent) {
 if ($mContent instanceof DeclarationBlock) {
 $aResult[] = $mContent;
 } elseif ($mContent instanceof CSSBlockList) {
 $mContent->allDeclarationBlocks($aResult);
 }
 }
 }
 protected function allRuleSets(array &$aResult)
 {
 foreach ($this->aContents as $mContent) {
 if ($mContent instanceof RuleSet) {
 $aResult[] = $mContent;
 } elseif ($mContent instanceof CSSBlockList) {
 $mContent->allRuleSets($aResult);
 }
 }
 }
 public function getAllValues(
 $element = null,
 $ruleSearchPatternOrSearchInFunctionArguments = null,
 $searchInFunctionArguments = false
 ) {
 if (\is_bool($ruleSearchPatternOrSearchInFunctionArguments)) {
 $searchInFunctionArguments = $ruleSearchPatternOrSearchInFunctionArguments;
 $searchString = null;
 } else {
 $searchString = $ruleSearchPatternOrSearchInFunctionArguments;
 }
 if ($element === null) {
 $element = $this;
 } elseif (\is_string($element)) {
 $searchString = $element;
 $element = $this;
 }
 $result = [];
 $this->allValues($element, $result, $searchString, $searchInFunctionArguments);
 return $result;
 }
 protected function allValues($oElement, array &$aResult, $sSearchString = null, $bSearchInFunctionArguments = false)
 {
 if ($oElement instanceof CSSBlockList) {
 foreach ($oElement->getContents() as $oContent) {
 $this->allValues($oContent, $aResult, $sSearchString, $bSearchInFunctionArguments);
 }
 } elseif ($oElement instanceof RuleSet) {
 foreach ($oElement->getRules($sSearchString) as $oRule) {
 $this->allValues($oRule, $aResult, $sSearchString, $bSearchInFunctionArguments);
 }
 } elseif ($oElement instanceof Rule) {
 $this->allValues($oElement->getValue(), $aResult, $sSearchString, $bSearchInFunctionArguments);
 } elseif ($oElement instanceof ValueList) {
 if ($bSearchInFunctionArguments || !($oElement instanceof CSSFunction)) {
 foreach ($oElement->getListComponents() as $mComponent) {
 $this->allValues($mComponent, $aResult, $sSearchString, $bSearchInFunctionArguments);
 }
 }
 } else {
 // Non-List `Value` or `CSSString` (CSS identifier)
 $aResult[] = $oElement;
 }
 }
 protected function allSelectors(array &$aResult, $sSpecificitySearch = null)
 {
 $aDeclarationBlocks = [];
 $this->allDeclarationBlocks($aDeclarationBlocks);
 foreach ($aDeclarationBlocks as $oBlock) {
 foreach ($oBlock->getSelectors() as $oSelector) {
 if ($sSpecificitySearch === null) {
 $aResult[] = $oSelector;
 } else {
 $sComparator = '===';
 $aSpecificitySearch = explode(' ', $sSpecificitySearch);
 $iTargetSpecificity = $aSpecificitySearch[0];
 if (count($aSpecificitySearch) > 1) {
 $sComparator = $aSpecificitySearch[0];
 $iTargetSpecificity = $aSpecificitySearch[1];
 }
 $iTargetSpecificity = (int)$iTargetSpecificity;
 $iSelectorSpecificity = $oSelector->getSpecificity();
 $bMatches = false;
 switch ($sComparator) {
 case '<=':
 $bMatches = $iSelectorSpecificity <= $iTargetSpecificity;
 break;
 case '<':
 $bMatches = $iSelectorSpecificity < $iTargetSpecificity;
 break;
 case '>=':
 $bMatches = $iSelectorSpecificity >= $iTargetSpecificity;
 break;
 case '>':
 $bMatches = $iSelectorSpecificity > $iTargetSpecificity;
 break;
 default:
 $bMatches = $iSelectorSpecificity === $iTargetSpecificity;
 break;
 }
 if ($bMatches) {
 $aResult[] = $oSelector;
 }
 }
 }
 }
 }
}
