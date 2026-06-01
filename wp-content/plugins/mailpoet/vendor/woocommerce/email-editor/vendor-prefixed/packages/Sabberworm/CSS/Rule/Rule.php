<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Rule;
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
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\RuleValueList;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\Value;
class Rule implements Commentable, CSSElement, Positionable
{
 use Position;
 private $sRule;
 private $mValue;
 private $bIsImportant;
 private $aIeHack;
 protected $aComments;
 public function __construct($sRule, $iLineNo = 0, $iColNo = 0)
 {
 $this->sRule = $sRule;
 $this->mValue = null;
 $this->bIsImportant = false;
 $this->aIeHack = [];
 $this->setPosition($iLineNo, $iColNo);
 $this->aComments = [];
 }
 public static function parse(ParserState $oParserState, $commentsBeforeRule = [])
 {
 $aComments = \array_merge($commentsBeforeRule, $oParserState->consumeWhiteSpace());
 $oRule = new Rule(
 $oParserState->parseIdentifier(!$oParserState->comes("--")),
 $oParserState->currentLine(),
 $oParserState->currentColumn()
 );
 $oRule->setComments($aComments);
 $oRule->addComments($oParserState->consumeWhiteSpace());
 $oParserState->consume(':');
 $oValue = Value::parseValue($oParserState, self::listDelimiterForRule($oRule->getRule()));
 $oRule->setValue($oValue);
 if ($oParserState->getSettings()->bLenientParsing) {
 while ($oParserState->comes('\\')) {
 $oParserState->consume('\\');
 $oRule->addIeHack($oParserState->consume());
 $oParserState->consumeWhiteSpace();
 }
 }
 $oParserState->consumeWhiteSpace();
 if ($oParserState->comes('!')) {
 $oParserState->consume('!');
 $oParserState->consumeWhiteSpace();
 $oParserState->consume('important');
 $oRule->setIsImportant(true);
 }
 $oParserState->consumeWhiteSpace();
 while ($oParserState->comes(';')) {
 $oParserState->consume(';');
 }
 return $oRule;
 }
 private static function listDelimiterForRule($sRule)
 {
 if (preg_match('/^font($|-)/', $sRule)) {
 return [',', '/', ' '];
 }
 switch ($sRule) {
 case 'src':
 return [' ', ','];
 default:
 return [',', ' ', '/'];
 }
 }
 public function setRule($sRule)
 {
 $this->sRule = $sRule;
 }
 public function getRule()
 {
 return $this->sRule;
 }
 public function getValue()
 {
 return $this->mValue;
 }
 public function setValue($mValue)
 {
 $this->mValue = $mValue;
 }
 public function setValues(array $aSpaceSeparatedValues)
 {
 $oSpaceSeparatedList = null;
 if (count($aSpaceSeparatedValues) > 1) {
 $oSpaceSeparatedList = new RuleValueList(' ', $this->iLineNo);
 }
 foreach ($aSpaceSeparatedValues as $aCommaSeparatedValues) {
 $oCommaSeparatedList = null;
 if (count($aCommaSeparatedValues) > 1) {
 $oCommaSeparatedList = new RuleValueList(',', $this->iLineNo);
 }
 foreach ($aCommaSeparatedValues as $mValue) {
 if (!$oSpaceSeparatedList && !$oCommaSeparatedList) {
 $this->mValue = $mValue;
 return $mValue;
 }
 if ($oCommaSeparatedList) {
 $oCommaSeparatedList->addListComponent($mValue);
 } else {
 $oSpaceSeparatedList->addListComponent($mValue);
 }
 }
 if (!$oSpaceSeparatedList) {
 $this->mValue = $oCommaSeparatedList;
 return $oCommaSeparatedList;
 } else {
 $oSpaceSeparatedList->addListComponent($oCommaSeparatedList);
 }
 }
 $this->mValue = $oSpaceSeparatedList;
 return $oSpaceSeparatedList;
 }
 public function getValues()
 {
 if (!$this->mValue instanceof RuleValueList) {
 return [[$this->mValue]];
 }
 if ($this->mValue->getListSeparator() === ',') {
 return [$this->mValue->getListComponents()];
 }
 $aResult = [];
 foreach ($this->mValue->getListComponents() as $mValue) {
 if (!$mValue instanceof RuleValueList || $mValue->getListSeparator() !== ',') {
 $aResult[] = [$mValue];
 continue;
 }
 if ($this->mValue->getListSeparator() === ' ' || count($aResult) === 0) {
 $aResult[] = [];
 }
 foreach ($mValue->getListComponents() as $mValue) {
 $aResult[count($aResult) - 1][] = $mValue;
 }
 }
 return $aResult;
 }
 public function addValue($mValue, $sType = ' ')
 {
 if (!is_array($mValue)) {
 $mValue = [$mValue];
 }
 if (!$this->mValue instanceof RuleValueList || $this->mValue->getListSeparator() !== $sType) {
 $mCurrentValue = $this->mValue;
 $this->mValue = new RuleValueList($sType, $this->getLineNumber());
 if ($mCurrentValue) {
 $this->mValue->addListComponent($mCurrentValue);
 }
 }
 foreach ($mValue as $mValueItem) {
 $this->mValue->addListComponent($mValueItem);
 }
 }
 public function addIeHack($iModifier)
 {
 $this->aIeHack[] = $iModifier;
 }
 public function setIeHack(array $aModifiers)
 {
 $this->aIeHack = $aModifiers;
 }
 public function getIeHack()
 {
 return $this->aIeHack;
 }
 public function setIsImportant($bIsImportant)
 {
 $this->bIsImportant = $bIsImportant;
 }
 public function getIsImportant()
 {
 return $this->bIsImportant;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 $sResult = "{$oOutputFormat->comments($this)}{$this->sRule}:{$oOutputFormat->spaceAfterRuleName()}";
 if ($this->mValue instanceof Value) { // Can also be a ValueList
 $sResult .= $this->mValue->render($oOutputFormat);
 } else {
 $sResult .= $this->mValue;
 }
 if (!empty($this->aIeHack)) {
 $sResult .= ' \\' . implode('\\', $this->aIeHack);
 }
 if ($this->bIsImportant) {
 $sResult .= ' !important';
 }
 $sResult .= ';';
 return $sResult;
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
