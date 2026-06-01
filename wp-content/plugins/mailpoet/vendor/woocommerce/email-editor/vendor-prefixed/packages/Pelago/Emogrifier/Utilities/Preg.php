<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities;
if (!defined('ABSPATH')) exit;
final class Preg
{
 private $throwExceptions = false;
 public function throwExceptions(bool $throw): self
 {
 $this->throwExceptions = $throw;
 return $this;
 }
 public function replace($pattern, $replacement, string $subject, int $limit = -1, ?int &$count = null): string
 {
 $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
 if ($result === null) {
 $this->logOrThrowPregLastError();
 $result = $subject;
 }
 return $result;
 }
 public function replaceCallback(
 $pattern,
 callable $callback,
 string $subject,
 int $limit = -1,
 ?int &$count = null
 ): string {
 $result = \preg_replace_callback($pattern, $callback, $subject, $limit, $count);
 if ($result === null) {
 $this->logOrThrowPregLastError();
 $result = $subject;
 }
 return $result;
 }
 public function split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array
 {
 if (($flags & PREG_SPLIT_OFFSET_CAPTURE) !== 0) {
 throw new \RuntimeException('PREG_SPLIT_OFFSET_CAPTURE is not supported by Preg::split', 1726506348);
 }
 $result = \preg_split($pattern, $subject, $limit, $flags);
 if ($result === false) {
 $this->logOrThrowPregLastError();
 $result = [$subject];
 }
 return $result;
 }
 public function match(string $pattern, string $subject, ?array &$matches = null): int
 {
 $result = \preg_match($pattern, $subject, $matches);
 if ($result === false) {
 $this->logOrThrowPregLastError();
 $result = 0;
 $matches = [];
 }
 return $result;
 }
 public function matchAll(string $pattern, string $subject, ?array &$matches = null): int
 {
 $result = \preg_match_all($pattern, $subject, $matches);
 if ($result === false) {
 $this->logOrThrowPregLastError();
 $result = 0;
 $matches = \array_fill(0, \substr_count($pattern, '(') + 1, []);
 }
 return $result;
 }
 private function logOrThrowPregLastError(): void
 {
 $pcreConstants = \get_defined_constants(true)['pcre'];
 $pcreErrorConstantNames = \array_flip(\array_filter(
 $pcreConstants,
 static function (string $key): bool {
 return \substr($key, -6) === '_ERROR';
 },
 ARRAY_FILTER_USE_KEY
 ));
 $pregLastError = \preg_last_error();
 $message = 'PCRE regex execution error `' . (string) ($pcreErrorConstantNames[$pregLastError] ?? $pregLastError)
 . '`';
 if ($this->throwExceptions) {
 throw new \RuntimeException($message, 1592870147);
 }
 \trigger_error($message);
 }
}
