<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\Handler;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\Reader;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\TokenStream;
class CommentHandler implements HandlerInterface
{
 public function handle(Reader $reader, TokenStream $stream): bool
 {
 if ('/*' !== $reader->getSubstring(2)) {
 return false;
 }
 $offset = $reader->getOffset('*/');
 if (false === $offset) {
 $reader->moveToEnd();
 } else {
 $reader->moveForward($offset + 2);
 }
 return true;
 }
}
