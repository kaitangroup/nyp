<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\AtRule;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Charset;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\CSSNamespace;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Import;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Renderable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\AtRuleSet;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\RuleSet;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Settings;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\CSSString;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\URL;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\Value;
abstract class CSSList implements Commentable, CSSElement, Positionable
{
 use Position;
 protected $aComments;
 protected $aContents;
 public function __construct($iLineNo = 0)
 {
 $this->aComments = [];
 $this->aContents = [];
 $this->setPosition($iLineNo);
 }
 public static function parseList(ParserState $oParserState, CSSList $oList)
 {
 $bIsRoot = $oList instanceof Document;
 if (is_string($oParserState)) {
 $oParserState = new ParserState($oParserState, Settings::create());
 }
 $bLenientParsing = $oParserState->getSettings()->bLenientParsing;
 $aComments = [];
 while (!$oParserState->isEnd()) {
 $aComments = array_merge($aComments, $oParserState->consumeWhiteSpace());
 $oListItem = null;
 if ($bLenientParsing) {
 try {
 $oListItem = self::parseListItem($oParserState, $oList);
 } catch (UnexpectedTokenException $e) {
 $oListItem = false;
 }
 } else {
 $oListItem = self::parseListItem($oParserState, $oList);
 }
 if ($oListItem === null) {
 // List parsing finished
 return;
 }
 if ($oListItem) {
 $oListItem->addComments($aComments);
 $oList->append($oListItem);
 }
 $aComments = $oParserState->consumeWhiteSpace();
 }
 $oList->addComments($aComments);
 if (!$bIsRoot && !$bLenientParsing) {
 throw new SourceException("Unexpected end of document", $oParserState->currentLine());
 }
 }
 private static function parseListItem(ParserState $oParserState, CSSList $oList)
 {
 $bIsRoot = $oList instanceof Document;
 if ($oParserState->comes('@')) {
 $oAtRule = self::parseAtRule($oParserState);
 if ($oAtRule instanceof Charset) {
 if (!$bIsRoot) {
 throw new UnexpectedTokenException(
 '@charset may only occur in root document',
 '',
 'custom',
 $oParserState->currentLine()
 );
 }
 if (count($oList->getContents()) > 0) {
 throw new UnexpectedTokenException(
 '@charset must be the first parseable token in a document',
 '',
 'custom',
 $oParserState->currentLine()
 );
 }
 $oParserState->setCharset($oAtRule->getCharset());
 }
 return $oAtRule;
 } elseif ($oParserState->comes('}')) {
 if ($bIsRoot) {
 if ($oParserState->getSettings()->bLenientParsing) {
 return DeclarationBlock::parse($oParserState);
 } else {
 throw new SourceException("Unopened {", $oParserState->currentLine());
 }
 } else {
 // End of list
 return null;
 }
 } else {
 return DeclarationBlock::parse($oParserState, $oList);
 }
 }
 private static function parseAtRule(ParserState $oParserState)
 {
 $oParserState->consume('@');
 $sIdentifier = $oParserState->parseIdentifier();
 $iIdentifierLineNum = $oParserState->currentLine();
 $oParserState->consumeWhiteSpace();
 if ($sIdentifier === 'import') {
 $oLocation = URL::parse($oParserState);
 $oParserState->consumeWhiteSpace();
 $sMediaQuery = null;
 if (!$oParserState->comes(';')) {
 $sMediaQuery = trim($oParserState->consumeUntil([';', ParserState::EOF]));
 }
 $oParserState->consumeUntil([';', ParserState::EOF], true, true);
 return new Import($oLocation, $sMediaQuery ?: null, $iIdentifierLineNum);
 } elseif ($sIdentifier === 'charset') {
 $oCharsetString = CSSString::parse($oParserState);
 $oParserState->consumeWhiteSpace();
 $oParserState->consumeUntil([';', ParserState::EOF], true, true);
 return new Charset($oCharsetString, $iIdentifierLineNum);
 } elseif (self::identifierIs($sIdentifier, 'keyframes')) {
 $oResult = new KeyFrame($iIdentifierLineNum);
 $oResult->setVendorKeyFrame($sIdentifier);
 $oResult->setAnimationName(trim($oParserState->consumeUntil('{', false, true)));
 CSSList::parseList($oParserState, $oResult);
 if ($oParserState->comes('}')) {
 $oParserState->consume('}');
 }
 return $oResult;
 } elseif ($sIdentifier === 'namespace') {
 $sPrefix = null;
 $mUrl = Value::parsePrimitiveValue($oParserState);
 if (!$oParserState->comes(';')) {
 $sPrefix = $mUrl;
 $mUrl = Value::parsePrimitiveValue($oParserState);
 }
 $oParserState->consumeUntil([';', ParserState::EOF], true, true);
 if ($sPrefix !== null && !is_string($sPrefix)) {
 throw new UnexpectedTokenException('Wrong namespace prefix', $sPrefix, 'custom', $iIdentifierLineNum);
 }
 if (!($mUrl instanceof CSSString || $mUrl instanceof URL)) {
 throw new UnexpectedTokenException(
 'Wrong namespace url of invalid type',
 $mUrl,
 'custom',
 $iIdentifierLineNum
 );
 }
 return new CSSNamespace($mUrl, $sPrefix, $iIdentifierLineNum);
 } else {
 // Unknown other at rule (font-face or such)
 $sArgs = trim($oParserState->consumeUntil('{', false, true));
 if (substr_count($sArgs, "(") != substr_count($sArgs, ")")) {
 if ($oParserState->getSettings()->bLenientParsing) {
 return null;
 } else {
 throw new SourceException("Unmatched brace count in media query", $oParserState->currentLine());
 }
 }
 $bUseRuleSet = true;
 foreach (explode('/', AtRule::BLOCK_RULES) as $sBlockRuleName) {
 if (self::identifierIs($sIdentifier, $sBlockRuleName)) {
 $bUseRuleSet = false;
 break;
 }
 }
 if ($bUseRuleSet) {
 $oAtRule = new AtRuleSet($sIdentifier, $sArgs, $iIdentifierLineNum);
 RuleSet::parseRuleSet($oParserState, $oAtRule);
 } else {
 $oAtRule = new AtRuleBlockList($sIdentifier, $sArgs, $iIdentifierLineNum);
 CSSList::parseList($oParserState, $oAtRule);
 if ($oParserState->comes('}')) {
 $oParserState->consume('}');
 }
 }
 return $oAtRule;
 }
 }
 private static function identifierIs($sIdentifier, $sMatch)
 {
 return (strcasecmp($sIdentifier, $sMatch) === 0)
 ?: preg_match("/^(-\\w+-)?$sMatch$/i", $sIdentifier) === 1;
 }
 public function prepend($oItem)
 {
 array_unshift($this->aContents, $oItem);
 }
 public function append($oItem)
 {
 $this->aContents[] = $oItem;
 }
 public function splice($iOffset, $iLength = null, $mReplacement = null)
 {
 array_splice($this->aContents, $iOffset, $iLength, $mReplacement);
 }
 public function insertBefore($item, $sibling)
 {
 if (in_array($sibling, $this->aContents, true)) {
 $this->replace($sibling, [$item, $sibling]);
 } else {
 $this->append($item);
 }
 }
 public function remove($oItemToRemove)
 {
 $iKey = array_search($oItemToRemove, $this->aContents, true);
 if ($iKey !== false) {
 unset($this->aContents[$iKey]);
 return true;
 }
 return false;
 }
 public function replace($oOldItem, $mNewItem)
 {
 $iKey = array_search($oOldItem, $this->aContents, true);
 if ($iKey !== false) {
 if (is_array($mNewItem)) {
 array_splice($this->aContents, $iKey, 1, $mNewItem);
 } else {
 array_splice($this->aContents, $iKey, 1, [$mNewItem]);
 }
 return true;
 }
 return false;
 }
 public function setContents(array $aContents)
 {
 $this->aContents = [];
 foreach ($aContents as $content) {
 $this->append($content);
 }
 }
 public function removeDeclarationBlockBySelector($mSelector, $bRemoveAll = false)
 {
 if ($mSelector instanceof DeclarationBlock) {
 $mSelector = $mSelector->getSelectors();
 }
 if (!is_array($mSelector)) {
 $mSelector = explode(',', $mSelector);
 }
 foreach ($mSelector as $iKey => &$mSel) {
 if (!($mSel instanceof Selector)) {
 if (!Selector::isValid($mSel)) {
 throw new UnexpectedTokenException(
 "Selector did not match '" . Selector::SELECTOR_VALIDATION_RX . "'.",
 $mSel,
 "custom"
 );
 }
 $mSel = new Selector($mSel);
 }
 }
 foreach ($this->aContents as $iKey => $mItem) {
 if (!($mItem instanceof DeclarationBlock)) {
 continue;
 }
 if ($mItem->getSelectors() == $mSelector) {
 unset($this->aContents[$iKey]);
 if (!$bRemoveAll) {
 return;
 }
 }
 }
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 protected function renderListContents(OutputFormat $oOutputFormat)
 {
 $sResult = '';
 $bIsFirst = true;
 $oNextLevel = $oOutputFormat;
 if (!$this->isRootList()) {
 $oNextLevel = $oOutputFormat->nextLevel();
 }
 foreach ($this->aContents as $oContent) {
 $sRendered = $oOutputFormat->safely(function () use ($oNextLevel, $oContent) {
 return $oContent->render($oNextLevel);
 });
 if ($sRendered === null) {
 continue;
 }
 if ($bIsFirst) {
 $bIsFirst = false;
 $sResult .= $oNextLevel->spaceBeforeBlocks();
 } else {
 $sResult .= $oNextLevel->spaceBetweenBlocks();
 }
 $sResult .= $sRendered;
 }
 if (!$bIsFirst) {
 // Had some output
 $sResult .= $oOutputFormat->spaceAfterBlocks();
 }
 return $sResult;
 }
 abstract public function isRootList();
 public function getContents()
 {
 return $this->aContents;
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
