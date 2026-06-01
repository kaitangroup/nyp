<?php
/** @noinspection PhpUnhandledExceptionInspection */

use Packlink\WooCommerce\Components\Utility\Shop_Helper;

// This section will be triggered when upgrading to 2.0.4 or later version of plugin.

/** @noinspection HtmlUnknownTarget */ // phpcs:ignore
/* translators: %s: Module URL. */
$translation = __(
	'With this version you will have access to any shipping service that your clients demand. Go to the <a href="%s">configuration</a> and select which shipping services should be offered to your customers!',
	'packlink-pro-shipping'
);
$text        = sprintf( $translation, Shop_Helper::get_plugin_page_url() );

set_transient( 'packlink-pro-messages', $text );
