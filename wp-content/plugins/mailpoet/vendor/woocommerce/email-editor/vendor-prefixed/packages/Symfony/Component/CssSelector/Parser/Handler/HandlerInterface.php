<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\Handler;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\Reader;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Parser\TokenStream;
interface HandlerInterface
{
 public function handle(Reader $reader, TokenStream $stream): bool;
}
