<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

use WC_Order;

defined('ABSPATH') || exit;

class PlanningFileLocator
{
    /**
     * Resolve an uploaded file from order meta.
     *
     * The order meta should contain a path relative to the
     * WordPress uploads directory, e.g.
     *
     * nyp-intake/order-123/floorplan.pdf
     *
     * @param WC_Order $order
     * @param string   $metaKey
     *
     * @return string|null
     */
    public function locate(
        string $relativePath
    ): ?string {
    
        if (empty($relativePath)) {
            return null;
        }
    
        $relativePath = wp_normalize_path(
            ltrim($relativePath, '/')
        );
    
        /*
        |--------------------------------------------------------------------------
        | Prevent directory traversal
        |--------------------------------------------------------------------------
        */
    
        if (
            str_contains($relativePath, '../') ||
            str_contains($relativePath, '..\\')
        ) {
            return null;
        }
    
        $uploads = wp_get_upload_dir();
    
        $baseDir = wp_normalize_path(
            $uploads['basedir']
        );
    
        $fullPath = wp_normalize_path(
            $baseDir . '/' . $relativePath
        );
    
        /*
        |--------------------------------------------------------------------------
        | Ensure the resolved file is inside uploads
        |--------------------------------------------------------------------------
        */
    
        if (
            !str_starts_with($fullPath, $baseDir)
        ) {
            return null;
        }
    
        if (
            !file_exists($fullPath) ||
            !is_file($fullPath)
        ) {
            return null;
        }
    
        return $fullPath;
    }
}