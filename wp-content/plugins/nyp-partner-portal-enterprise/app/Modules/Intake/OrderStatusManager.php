<?php

namespace NYP\Modules\Intake;

if (!defined('ABSPATH')) {
    exit;
}

class OrderStatusManager
{
    public function register(): void
    {
        add_action(
            'init',
            [$this, 'registerStatuses']
        );

        add_filter(
            'wc_order_statuses',
            [$this, 'addStatuses']
        );
    }

    public function registerStatuses(): void
    {
        $statuses = [
            'wc-pending-intake' => 'Pending Intake',
            'wc-intake-submitted' => 'Intake Submitted',
            'wc-in-planning' => 'In Planning',
            'wc-ready-review' => 'Ready For Review',
        ];

        foreach ($statuses as $slug => $label) {

            register_post_status(
                $slug,
                [
                    'label'                     => $label,
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop(
                        $label . ' (%s)',
                        $label . ' (%s)'
                    ),
                ]
            );
        }
    }

    public function addStatuses(array $statuses): array
    {
        $newStatuses = [];

        foreach ($statuses as $key => $label) {

            $newStatuses[$key] = $label;

            if ('wc-processing' === $key) {

                $newStatuses['wc-pending-intake']
                    = 'Pending Intake';

                $newStatuses['wc-intake-submitted']
                    = 'Intake Submitted';

                $newStatuses['wc-in-planning']
                    = 'In Planning';

                $newStatuses['wc-ready-review']
                    = 'Ready For Review';
            }
        }

        return $newStatuses;
    }
}