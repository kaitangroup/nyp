<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities;
if (!defined('ABSPATH')) exit;
final class ArrayIntersector
{
 private $invertedArray;
 public function __construct(array $array)
 {
 $this->invertedArray = \array_flip($array);
 }
 public function intersectWith(array $array): array
 {
 $invertedArray = \array_flip($array);
 $invertedIntersection = \array_intersect_key($invertedArray, $this->invertedArray);
 return \array_flip($invertedIntersection);
 }
}
