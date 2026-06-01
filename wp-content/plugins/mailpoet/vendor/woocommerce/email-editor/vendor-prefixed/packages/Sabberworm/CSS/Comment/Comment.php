<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Renderable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
class Comment implements Positionable, Renderable
{
 use Position;
 protected $sComment;
 public function __construct($sComment = '', $iLineNo = 0)
 {
 $this->sComment = $sComment;
 $this->setPosition($iLineNo);
 }
 public function getComment()
 {
 return $this->sComment;
 }
 public function setComment($sComment)
 {
 $this->sComment = $sComment;
 }
 public function __toString()
 {
 return $this->render(new OutputFormat());
 }
 public function render($oOutputFormat)
 {
 return '/*' . $this->sComment . '*/';
 }
}
