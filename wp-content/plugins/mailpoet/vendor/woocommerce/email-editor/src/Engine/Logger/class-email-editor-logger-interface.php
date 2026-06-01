<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Logger;
if (!defined('ABSPATH')) exit;
interface Email_Editor_Logger_Interface {
 public function emergency( string $message, array $context = array() ): void;
 public function alert( string $message, array $context = array() ): void;
 public function critical( string $message, array $context = array() ): void;
 public function error( string $message, array $context = array() ): void;
 public function warning( string $message, array $context = array() ): void;
 public function notice( string $message, array $context = array() ): void;
 public function info( string $message, array $context = array() ): void;
 public function debug( string $message, array $context = array() ): void;
 public function log( string $level, string $message, array $context = array() ): void;
}
