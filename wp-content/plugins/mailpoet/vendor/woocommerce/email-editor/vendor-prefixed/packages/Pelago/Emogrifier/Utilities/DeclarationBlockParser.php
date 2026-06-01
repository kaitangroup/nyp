<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities;
if (!defined('ABSPATH')) exit;
final class DeclarationBlockParser
{
 private static $cache = [];
 public static function clearCache(): void
 {
 self::$cache = [];
 }
 public function normalizePropertyName(string $name): string
 {
 if (\substr($name, 0, 2) === '--') {
 return $name;
 } else {
 return \strtolower($name);
 }
 }
 public function parse(string $declarationBlock): array
 {
 if (isset(self::$cache[$declarationBlock])) {
 return self::$cache[$declarationBlock];
 }
 $preg = new Preg();
 $declarations = $preg->split('/;(?!base64|charset)/', $declarationBlock);
 $properties = [];
 foreach ($declarations as $declaration) {
 $matches = [];
 if (
 $preg->match(
 '/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s',
 \trim($declaration),
 $matches
 )
 === 0
 ) {
 continue;
 }
 $propertyName = $matches[1];
 if ($propertyName === '') {
 // This cannot happen since the regular epression matches one or more characters.
 throw new \UnexpectedValueException('An empty property name was encountered.', 1727046409);
 }
 $propertyValue = $matches[2];
 $properties[$this->normalizePropertyName($propertyName)] = $propertyValue;
 }
 self::$cache[$declarationBlock] = $properties;
 return $properties;
 }
}
