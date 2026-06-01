<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator\Schema;
if (!defined('ABSPATH')) exit;
use Automattic\WooCommerce\EmailEditor\Validator\Schema;
class Null_Schema extends Schema {
 protected $schema = array(
 'type' => 'null',
 );
}
