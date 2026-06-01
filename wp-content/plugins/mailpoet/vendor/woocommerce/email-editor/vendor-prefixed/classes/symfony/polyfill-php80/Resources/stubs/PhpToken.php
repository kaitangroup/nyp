<?php
if (!defined('ABSPATH')) exit;
if (\PHP_VERSION_ID < 80000 && extension_loaded('tokenizer')) {
 class EmailEditorVendor_PhpToken extends Symfony\Polyfill\Php80\EmailEditorVendor_PhpToken
 {
 }
}
