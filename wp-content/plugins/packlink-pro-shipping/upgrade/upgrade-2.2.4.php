<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Packlink\WooCommerce\Components\Utility\Database;

// This section will be triggered when upgrading to 2.2.4 or later version of plugin.
global $wpdb;

$database = new Database( $wpdb );

$database->add_additional_index();