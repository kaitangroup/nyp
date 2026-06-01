<?php
if (!defined('ABSPATH')) exit;
if (\PHP_VERSION_ID < 80000 && extension_loaded('tokenizer')) {
 class PhpToken extends Automattic\WooCommerce\EmailEditorVendor\Symfony\Polyfill\Php80\PhpToken
 {
 }
}
