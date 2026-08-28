<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Packlink\WooCommerce\Components\Customs\Customs_Handler;

// This section will be triggered when upgrading to 4.3.0 or later version of plugin.

// Seed the default customs mapping (idempotent: does not overwrite a merchant's saved mapping).
Customs_Handler::seed_default_customs_mapping();
