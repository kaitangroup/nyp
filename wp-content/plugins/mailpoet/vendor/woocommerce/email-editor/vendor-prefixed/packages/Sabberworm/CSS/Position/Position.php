<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position;
if (!defined('ABSPATH')) exit;
trait Position
{
 protected $lineNumber;
 protected $columnNumber;
 public function getLineNumber()
 {
 return $this->lineNumber;
 }
 public function getLineNo()
 {
 $lineNumber = $this->getLineNumber();
 return $lineNumber !== null ? $lineNumber : 0;
 }
 public function getColumnNumber()
 {
 return $this->columnNumber;
 }
 public function getColNo()
 {
 $columnNumber = $this->getColumnNumber();
 return $columnNumber !== null ? $columnNumber : 0;
 }
 public function setPosition($lineNumber, $columnNumber = null)
 {
 // The conditional is for backwards compatibility (backcompat); `0` will not be allowed in future.
 $this->lineNumber = $lineNumber !== 0 ? $lineNumber : null;
 $this->columnNumber = $columnNumber;
 }
}
