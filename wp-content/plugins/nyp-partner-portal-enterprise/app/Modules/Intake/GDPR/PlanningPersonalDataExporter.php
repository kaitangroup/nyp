<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\GDPR;

use WC_Order;

defined('ABSPATH') || exit;

class PlanningPersonalDataExporter
{
    /**
     * Register exporter.
     */
    public function register(): void
    {
        add_filter(
            'wp_privacy_personal_data_exporters',
            [$this, 'registerExporter']
        );
    }

    /**
     * Register with WordPress.
     */
    public function registerExporter(
        array $exporters
    ): array {

        $exporters['nyp-planning-data'] = [

            'exporter_friendly_name' => __(
                'NYP Planning Data',
                'nyp'
            ),

            'callback' => [
                $this,
                'export'
            ],

        ];

        return $exporters;
    }

    /**
     * Export customer planning data.
     */
    public function export(
        string $emailAddress,
        int $page = 1
    ): array {

        $items = [];

        $orders = wc_get_orders([
            'billing_email' => $emailAddress,
            'limit'         => -1,
            'return'        => 'objects',
        ]);

        foreach ($orders as $order) {

            if (!$order instanceof WC_Order) {
                continue;
            }

            $items[] = [
                'group_id'    => 'nyp-planning',
                'group_label' => __('NYP Planning', 'nyp'),
                'item_id'     => 'order-' . $order->get_id(),
                'data'        => $this->buildOrderData($order),
            ];
        }

        return [
            'data' => $items,
            'done' => true,
        ];
    }

    /**
     * Build exported data.
     */
    protected function buildOrderData(
        WC_Order $order
    ): array {

        return [

            [
                'name'  => __('Order Number', 'nyp'),
                'value' => $order->get_order_number(),
            ],

            [
                'name'  => __('Planning Category', 'nyp'),
                'value' => $order->get_meta('_nyp_planning_category'),
            ],

            [
                'name'  => __('Project Name', 'nyp'),
                'value' => $order->get_meta('_nyp_project_name'),
            ],

            [
                'name'  => __('Service Speed', 'nyp'),
                'value' => $order->get_meta('_nyp_service_speed'),
            ],

            [
                'name'  => __('Submitted', 'nyp'),
                'value' => $order->get_date_created()
                    ? $order->get_date_created()->date_i18n()
                    : '',
            ],

            [
                'name'  => __('Uploaded Files', 'nyp'),
                'value' => $this->uploadedFiles($order),
            ],

        ];
    }

    /**
     * Build uploaded file list.
     */
    protected function uploadedFiles(
        WC_Order $order
    ): string {

        $keys = [

            '_nyp_floor_plan',
            '_nyp_kitchen_photos',
            '_nyp_inspiration_images',
            '_nyp_technical_documents',
            '_nyp_additional_files',

        ];

        $files = [];

        foreach ($keys as $key) {

            $value = $order->get_meta(
                $key,
                true
            );

            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {

                foreach ($value as $file) {
                    $files[] = basename($file);
                }

            } else {

                $files[] = basename($value);

            }
        }

        return implode(
            ', ',
            $files
        );
    }
}