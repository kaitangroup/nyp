<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Renderable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Rule\Rule;
abstract class RuleSet implements CSSElement, Commentable, Positionable
{
 use Position;
 private $aRules;
 protected $aComments;
 public function __construct($iLineNo = 0)
 {
 $this->aRules = [];
 $this->setPosition($iLineNo);
 $this->aComments = [];
 }
 public static function parseRuleSet(ParserState $oParserState, RuleSet $oRuleSet)
 {
 while ($oParserState->comes(';')) {
 $oParserState->consume(';');
 }
 while (true) {
 $commentsBeforeRule = $oParserState->consumeWhiteSpace();
 if ($oParserState->comes('}')) {
 break;
 }
 $oRule = null;
 if ($oParserState->getSettings()->bLenientParsing) {
 try {
 $oRule = Rule::parse($oParserState, $commentsBeforeRule);
 } catch (UnexpectedTokenException $e) {
 try {
 $sConsume = $oParserState->consumeUntil(["\n", ";", '}'], true);
 // We need to “unfind” the matches to the end of the ruleSet as this will be matched later
 if ($oParserState->streql(substr($sConsume, -1), '}')) {
 $oParserState->backtrack(1);
 } else {
 while ($oParserState->comes(';')) {
 $oParserState->consume(';');
 }
 }
 } catch (UnexpectedTokenException $e) {
 // We’ve reached the end of the document. Just close the RuleSet.
 return;
 }
 }
 } else {
 $oRule = Rule::parse($oParserState, $commentsBeforeRule);
 }
 if ($oRule) {
 $oRuleSet->addRule($oRule);
 }
 }
 $oParserState->consume('}');
 }
 public function addRule(Rule $oRule, $oSibling = null)
 {
 $sRule = $oRule->getRule();
 if (!isset($this->aRules[$sRule])) {
 $this->aRules[$sRule] = [];
 }
 $iPosition = count($this->aRules[$sRule]);
 if ($oSibling !== null) {
 $iSiblingPos = array_search($oSibling, $this->aRules[$sRule], true);
 if ($iSiblingPos !== false) {
 $iPosition = $iSiblingPos;
 $oRule->setPosition($oSibling->getLineNo(), $oSibling->getColNo() - 1);
 }
 }
 if ($oRule->getLineNumber() === null) {
 //this node is added manually, give it the next best line
 $columnNumber = $oRule->getColNo();
 $rules = $this->getRules();
 $pos = count($rules);
 if ($pos > 0) {
 $last = $rules[$pos - 1];
 $oRule->setPosition($last->getLineNo() + 1, $columnNumber);
 } else {
 $oRule->setPosition(1, $columnNumber);
 }
 } elseif ($oRule->getColumnNumber() === null) {
 $oRule->setPosition($oRule->getLineNumber(), 0);
 }
 array_splice($this->aRules[$sRule], $iPosition, 0, [$oRule]);
 }
 public function getRules($mRule = null)
 {
 if ($mRule instanceof Rule) {
 $mRule = $mRule->getRule();
 }
 $aResult = [];
 foreach ($this->aRules as $sName => $aRules) {
 // Either no search rule is given or the search rule matches the found rule exactly
 // or the search rule ends in “-” and the found rule starts with the search rule.
 if (
 !$mRule || $sName === $mRule
 || (
 strrpos($mRule, '-') === strlen($mRule) - strlen('-')
 && (strpos($sName, $mRule) === 0 || $sName === substr($mRule, 0, -1))
 )
 ) {
 $aResult = array_merge($aResult, $aRules);
 }
 }
 usort($aResult, function (Rule $first, Rule $second) {
 if ($first->getLineNo() === $second->getLineNo()) {
 return $first->getColNo() - $second->getColNo();
 }
 return $first->getLineNo() - $second->getLineNo();
 });
 return $aResult;
 }
 public function setRules(array $aRules)
 {
 $this->aRules = [];
 foreach ($aRules as $rule) {
 $this->addRule($rule);
 }
 }
 public function getRulesAssoc($mRule = null)
 {
 $aResult = [];
 foreach ($this->getRules($mRule) as $oRule) {
 $aResult[$oRule->getRule()] = $oRule;
 }
 return $aResult;
 }
 public function removeRule($mRule)
 {
 if ($mRule instanceof Rule) {
 $sRule = $mRule->getRule();
 if (!isset($this->aRules[$sRule])) {
 return;
 }
 foreach ($this->aRules[$sRule] as $iKey => $oRule) {
 if ($oRule === $mRule) {
 unset($this->aRules[$sRule][$iKey]);
 }
 }
 } elseif ($mRule !== null) {
 $this->removeMatchingRules($mRule);
 } else {
 $this->removeAllRules();
 }
 }
 public function removeMatchingRules($searchPattern)
 {
 foreach ($this->aRules as $propertyName => $rules) {
 // Either the search rule matches the found rule exactly
 // or the search rule ends in “-” and the found rule starts with the search rule or equals it
 // (without the trailing dash).
 if (
 $propertyName === $searchPattern
 || (\strrpos($searchPattern, '-') === \strlen($searchPattern) - \strlen('-')
 && (\strpos($propertyName, $searchPattern) === 0
 || $propertyName === \substr($searchPattern, 0, -1)))
 ) {
 unset($this->aRules[$propertyName]);
 }
 }
 }
 public function removeAllRules()
 {
 $this->aRules = [];
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 protected function renderRules(OutputFormat $oOutputFormat)
 {
 $sResult = '';
 $bIsFirst = true;
 $oNextLevel = $oOutputFormat->nextLevel();
 foreach ($this->getRules() as $oRule) {
 $sRendered = $oNextLevel->safely(function () use ($oRule, $oNextLevel) {
 return $oRule->render($oNextLevel);
 });
 if ($sRendered === null) {
 continue;
 }
 if ($bIsFirst) {
 $bIsFirst = false;
 $sResult .= $oNextLevel->spaceBeforeRules();
 } else {
 $sResult .= $oNextLevel->spaceBetweenRules();
 }
 $sResult .= $sRendered;
 }
 if (!$bIsFirst) {
 // Had some output
 $sResult .= $oOutputFormat->spaceAfterRules();
 }
 return $oOutputFormat->removeLastSemicolon($sResult);
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
