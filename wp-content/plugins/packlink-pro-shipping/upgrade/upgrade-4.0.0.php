<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Packlink\WooCommerce\Components\Migrator\ActionSchedulerMigrator\Queued_Tasks_Migrator;

// This section will be triggered when upgrading to 4.0.0 or later version of plugin.

$queued_tasks_migrator = new Queued_Tasks_Migrator();

// STEP 2. Migrate legacy queue items, draft statuses, schedules, and cleanup.
$queued_tasks_migrator->migrate();
