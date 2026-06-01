<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Logger;
if (!defined('ABSPATH')) exit;
class Email_Editor_Logger implements Email_Editor_Logger_Interface {
 private Email_Editor_Logger_Interface $logger;
 public function __construct( ?Email_Editor_Logger_Interface $logger = null ) {
 $this->logger = $logger ?? new Default_Email_Editor_Logger();
 }
 public function set_logger( Email_Editor_Logger_Interface $logger ): void {
 $this->logger = $logger;
 }
 public function emergency( string $message, array $context = array() ): void {
 $this->logger->emergency( $message, $context );
 }
 public function alert( string $message, array $context = array() ): void {
 $this->logger->alert( $message, $context );
 }
 public function critical( string $message, array $context = array() ): void {
 $this->logger->critical( $message, $context );
 }
 public function error( string $message, array $context = array() ): void {
 $this->logger->error( $message, $context );
 }
 public function warning( string $message, array $context = array() ): void {
 $this->logger->warning( $message, $context );
 }
 public function notice( string $message, array $context = array() ): void {
 $this->logger->notice( $message, $context );
 }
 public function info( string $message, array $context = array() ): void {
 $this->logger->info( $message, $context );
 }
 public function debug( string $message, array $context = array() ): void {
 $this->logger->debug( $message, $context );
 }
 public function log( string $level, string $message, array $context = array() ): void {
 $this->logger->log( $level, $message, $context );
 }
}
