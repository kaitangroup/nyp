<?php

declare(strict_types=1);

namespace NYP\Modules\Intake\GDPR;

use WC_Order;

defined('ABSPATH') || exit;

class PlanningPersonalDataEraser
{
    /**
     * Planning meta keys that may contain personal data.
     */
    protected const META_KEYS = [

        '_nyp_project_name',
        '_nyp_project_address',
        '_nyp_project_postcode',
        '_nyp_floor_plan',
        '_nyp_kitchen_photos',
        '_nyp_inspiration_images',
        '_nyp_technical_documents',
        '_nyp_additional_files',
        '_nyp_planning_data',

    ];

    /**
     * Register eraser.
     */
    public function register(): void
    {
        add_filter(
            'wp_privacy_personal_data_erasers',
            [$this, 'registerEraser']
        );
    }

    /**
     * Register with WordPress.
     */
    public function registerEraser(
        array $erasers
    ): array {

        $erasers['nyp-planning-data'] = [

            'eraser_friendly_name' => __(
                'NYP Planning Data',
                'nyp'
            ),

            'callback' => [
                $this,
                'erase',
            ],

        ];

        return $erasers;
    }

    /**
     * Erase planning data.
     */
    public function erase(
        string $emailAddress,
        int $page = 1
    ): array {

        $itemsRemoved  = false;
        $messages      = [];

        $orders = wc_get_orders([

            'billing_email' => $emailAddress,

            'limit' => -1,

            'return' => 'objects',

        ]);

        foreach ($orders as $order) {

            if (!$order instanceof WC_Order) {
                continue;
            }

            $removed = $this->eraseOrderPlanningData(
                $order
            );

            if ($removed) {
                $itemsRemoved = true;
            }
        }

        return [

            'items_removed' => $itemsRemoved,

            'items_retained' => true,

            'messages' => $messages,

            'done' => true,

        ];
    }

    /**
     * Remove planning data from an order.
     */
    protected function eraseOrderPlanningData(
        WC_Order $order
    ): bool {

        $removed = false;

        foreach (self::META_KEYS as $metaKey) {

            $value = $order->get_meta(
                $metaKey,
                true
            );

            if (empty($value)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Delete uploaded files
            |--------------------------------------------------------------------------
            */

            if (is_array($value)) {

                foreach ($value as $file) {

                    $this->deleteFile(
                        $file
                    );

                }

            } elseif (is_string($value)) {

                $this->deleteFile(
                    $value
                );

            }

            /*
            |--------------------------------------------------------------------------
            | Remove meta
            |--------------------------------------------------------------------------
            */

            $order->delete_meta_data(
                $metaKey
            );

            $removed = true;
        }

        if ($removed) {

            $order->save();

        }

        return $removed;
    }

    /**
     * Delete uploaded file.
     */
    protected function deleteFile(
        string $relativePath
    ): void {

        if (empty($relativePath)) {
            return;
        }

        $relativePath = wp_normalize_path(
            ltrim($relativePath, '/')
        );

        if (
            str_contains($relativePath, '../') ||
            str_contains($relativePath, '..\\')
        ) {
            return;
        }

        $uploads = wp_get_upload_dir();

        $baseDir = wp_normalize_path(
            $uploads['basedir']
        );

        $fullPath = wp_normalize_path(
            $baseDir . '/' . $relativePath
        );

        if (
            !str_starts_with($fullPath, $baseDir)
        ) {
            return;
        }

        if (
            file_exists($fullPath) &&
            is_file($fullPath)
        ) {
            @unlink($fullPath);
        }
    }
}