<?php
namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Renderable;
interface AtRule extends Renderable, Commentable
{
 const BLOCK_RULES = 'media/document/supports/region-style/font-feature-values';
 const SET_RULES = 'font-face/counter-style/page/swash/styleset/annotation';
 public function atRuleName();
 public function atRuleArgs();
}
