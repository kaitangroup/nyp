<?php

namespace NYP\Services;

if (!defined('ABSPATH')) {
    exit;
}

class PlanningIdGenerator
{
    public function generate(): string
    {
        $date = current_time('Ymd');

        $optionKey = sprintf(
            'nyp_planning_counter_%s',
            $date
        );

        $counter = (int) get_option(
            $optionKey,
            0
        );

        $counter++;

        update_option(
            $optionKey,
            $counter,
            false
        );

        return sprintf(
            'NYP-%s-%04d',
            $date,
            $counter
        );
    }
}