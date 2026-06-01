<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
class CSSFunction extends ValueList
{
 protected $sName;
 public function __construct($sName, $aArguments, $sSeparator = ',', $iLineNo = 0)
 {
 if ($aArguments instanceof RuleValueList) {
 $sSeparator = $aArguments->getListSeparator();
 $aArguments = $aArguments->getListComponents();
 }
 $this->sName = $sName;
 $this->setPosition($iLineNo); // TODO: redundant?
 parent::__construct($aArguments, $sSeparator, $iLineNo);
 }
 public static function parse(ParserState $oParserState, $bIgnoreCase = false)
 {
 $mResult = $oParserState->parseIdentifier($bIgnoreCase);
 $oParserState->consume('(');
 $aArguments = Value::parseValue($oParserState, ['=', ' ', ',']);
 $mResult = new CSSFunction($mResult, $aArguments, ',', $oParserState->currentLine());
 $oParserState->consume(')');
 return $mResult;
 }
 public function getName()
 {
 return $this->sName;
 }
 public function setName($sName)
 {
 $this->sName = $sName;
 }
 public function getArguments()
 {
 return $this->aComponents;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 $aArguments = parent::render($oOutputFormat);
 return "{$this->sName}({$aArguments})";
 }
}
