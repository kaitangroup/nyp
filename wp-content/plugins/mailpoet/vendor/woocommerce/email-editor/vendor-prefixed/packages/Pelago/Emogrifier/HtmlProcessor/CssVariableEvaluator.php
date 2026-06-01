<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\HtmlProcessor;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities\DeclarationBlockParser;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities\Preg;
final class CssVariableEvaluator extends AbstractHtmlProcessor
{
 private $currentVariableDefinitions = [];
 public function evaluateVariables(): self
 {
 return $this->evaluateVariablesInElementAndDescendants($this->getHtmlElement(), []);
 }
 private function getVariableDefinitionsFromDeclarations(array $declarations): array
 {
 return \array_filter(
 $declarations,
 static function (string $key): bool {
 return \substr($key, 0, 2) === '--';
 },
 ARRAY_FILTER_USE_KEY
 );
 }
 private function getPropertyValueReplacement(array $matches): string
 {
 $variableName = $matches[1];
 if (isset($this->currentVariableDefinitions[$variableName])) {
 $variableValue = $this->currentVariableDefinitions[$variableName];
 } else {
 $fallbackValueSeparator = $matches[2] ?? '';
 if ($fallbackValueSeparator !== '') {
 $fallbackValue = $matches[3];
 // The fallback value may use other CSS variables, so recurse
 $variableValue = $this->replaceVariablesInPropertyValue($fallbackValue);
 } else {
 $variableValue = $matches[0];
 }
 }
 return $variableValue;
 }
 private function replaceVariablesInPropertyValue(string $propertyValue): string
 {
 return (new Preg())->replaceCallback(
 '/
 var\\(
 \\s*+
 # capture variable name including `--` prefix
 (
 --[^\\s\\),]++
 )
 \\s*+
 # capture optional fallback value
 (?:
 # capture separator to confirm there is a fallback value
 (,)\\s*
 # begin capture with named group that can be used recursively
 (?<recursable>
 # begin named group to match sequence without parentheses, except in strings
 (?<noparentheses>
 # repeated zero or more times:
 (?:
 # sequence without parentheses or quotes
 [^\\(\\)\'"]++
 |
 # string in double quotes
 "(?>[^"\\\\]++|\\\\.)*"
 |
 # string in single quotes
 \'(?>[^\'\\\\]++|\\\\.)*\'
 )*+
 )
 # repeated zero or more times:
 (?:
 # sequence in parentheses
 \\(
 # using the named recursable pattern
 (?&recursable)
 \\)
 # sequence without parentheses, except in strings
 (?&noparentheses)
 )*+
 )
 )?+
 \\)
 /x',
 \Closure::fromCallable([$this, 'getPropertyValueReplacement']),
 $propertyValue
 );
 }
 private function replaceVariablesInDeclarations(array $declarations): ?array
 {
 $substitutionsMade = false;
 $result = \array_map(
 function (string $propertyValue) use (&$substitutionsMade): string {
 $newPropertyValue = $this->replaceVariablesInPropertyValue($propertyValue);
 if ($newPropertyValue !== $propertyValue) {
 $substitutionsMade = true;
 }
 return $newPropertyValue;
 },
 $declarations
 );
 return $substitutionsMade ? $result : null;
 }
 private function getDeclarationsAsString(array $declarations): string
 {
 $declarationStrings = \array_map(
 static function (string $key, string $value): string {
 return $key . ': ' . $value;
 },
 \array_keys($declarations),
 \array_values($declarations)
 );
 return \implode('; ', $declarationStrings) . ';';
 }
 private function evaluateVariablesInElementAndDescendants(
 \DOMElement $element,
 array $ancestorVariableDefinitions
 ): self {
 $style = $element->getAttribute('style');
 // Avoid parsing declarations if none use or define a variable
 if ((new Preg())->match('/(?<![\\w\\-])--[\\w\\-]/', $style) !== 0) {
 $declarations = (new DeclarationBlockParser())->parse($style);
 $variableDefinitions = $this->currentVariableDefinitions
 = $this->getVariableDefinitionsFromDeclarations($declarations) + $ancestorVariableDefinitions;
 $newDeclarations = $this->replaceVariablesInDeclarations($declarations);
 if ($newDeclarations !== null) {
 $element->setAttribute('style', $this->getDeclarationsAsString($newDeclarations));
 }
 } else {
 $variableDefinitions = $ancestorVariableDefinitions;
 }
 foreach ($element->childNodes as $child) {
 if ($child instanceof \DOMElement) {
 $this->evaluateVariablesInElementAndDescendants($child, $variableDefinitions);
 }
 }
 return $this;
 }
}
