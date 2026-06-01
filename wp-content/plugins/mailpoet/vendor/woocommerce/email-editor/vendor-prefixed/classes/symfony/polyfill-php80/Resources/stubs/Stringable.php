<?php
if (!defined('ABSPATH')) exit;
if (\PHP_VERSION_ID < 80000) {
 interface EmailEditorVendor_Stringable
 {
 public function __toString();
 }
}
