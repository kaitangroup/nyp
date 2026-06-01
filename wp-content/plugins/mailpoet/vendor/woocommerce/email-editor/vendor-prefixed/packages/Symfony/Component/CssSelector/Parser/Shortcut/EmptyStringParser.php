<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\Shortcut;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Node\ElementNode;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Node\SelectorNode;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\ParserInterface;
class EmptyStringParser implements ParserInterface
{
 public function parse(string $source): array
 {
 // Matches an empty string
 if ('' == $source) {
 return [new SelectorNode(new ElementNode(null, '*'))];
 }
 return [];
 }
}
