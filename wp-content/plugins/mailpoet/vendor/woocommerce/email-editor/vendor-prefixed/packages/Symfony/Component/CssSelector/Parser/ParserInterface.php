<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Node\SelectorNode;
interface ParserInterface
{
 public function parse(string $source): array;
}
