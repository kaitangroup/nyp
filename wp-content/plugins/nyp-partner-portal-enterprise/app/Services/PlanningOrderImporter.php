<?php

declare(strict_types=1);

namespace NYP\Services;

use WC_Order;

defined('ABSPATH') || exit;

/*
|--------------------------------------------------------------------------
| Planning Order Importer
|--------------------------------------------------------------------------
|
| Imports the Planning Brief stored in the WooCommerce session into
| a WooCommerce order.
|
| This service is executed after an order has been created and before
| payment is completed.
|
*/

class PlanningOrderImporter
{
    /**
     * Planning session.
     *
     * @var PlanningSessionStorage
     */
    protected PlanningSessionStorage $session;

    /**
     * Constructor.
     *
     * @param PlanningSessionStorage|null $session
     */
    public function __construct(
        ?PlanningSessionStorage $session = null
    ) {
        $this->session = $session ?: new PlanningSessionStorage();
    }

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */

    /**
 * Import planning into an order by ID.
 *
 * @param int $orderId
 *
 * @return void
 */
public function importByOrderId(int $orderId): void
{
    $order = wc_get_order($orderId);

    if (!$order instanceof WC_Order) {
        return;
    }

    $this->import($order);
}

    /**
 * Import the Planning Brief into a WooCommerce order.
 *
 * @param WC_Order $order
 *
 * @return void
 */
public function import(WC_Order $order): void
{
    $planning = $this->session->all();
   

    if (empty($planning)) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Import Planning Metadata
    |--------------------------------------------------------------------------
    */

    foreach ($planning as $key => $value) {

        $order->update_meta_data(
            $key,
            $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Move Uploaded Files
    |--------------------------------------------------------------------------
    */

    $this->moveUploads(
        $order,
        $planning
    );

    /*
    |--------------------------------------------------------------------------
    | Persist Order
    |--------------------------------------------------------------------------
    */

    $order->save();

    /*
    |--------------------------------------------------------------------------
    | Planning Completed
    |--------------------------------------------------------------------------
    */

    $this->session->clear();
}


/**
 * Move uploaded Planning Brief files from the temporary
 * session directory into the order directory.
 *
 * @param WC_Order $order
 * @param array    $planning
 *
 * @return void
 */
protected function moveUploads(
    WC_Order $order,
    array $planning
): void {

    $sessionId = $planning['_nyp_session_id'] ?? '';
    error_log('moveUploads() called');

    if (empty($sessionId)) {
        return;
    }

    $uploadDir = wp_upload_dir();

    $baseDirectory = trailingslashit(
        $uploadDir['basedir']
    ) . 'nyp-intake/';

    $sourceDirectory =
        $baseDirectory .
        'session/' .
        $sessionId;

    if (!is_dir($sourceDirectory)) {
        return;
    }

    $destinationDirectory =
        $baseDirectory .
        'order-' .
        $order->get_id();

    /*
    |--------------------------------------------------------------------------
    | Ensure destination exists
    |--------------------------------------------------------------------------
    */

    if (!file_exists($destinationDirectory)) {
        wp_mkdir_p(
            $destinationDirectory
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Move files
    |--------------------------------------------------------------------------
    */

    $files = scandir(
        $sourceDirectory
    );

    foreach ($files as $file) {

        if (
            $file === '.' ||
            $file === '..'
        ) {
            continue;
        }

        @rename(
            trailingslashit($sourceDirectory) . $file,
            trailingslashit($destinationDirectory) . $file
        );
    }

    @rmdir($sourceDirectory);

    /*
    |--------------------------------------------------------------------------
    | Update Uploaded File Metadata
    |--------------------------------------------------------------------------
    */

    foreach ($planning as $key => $value) {

        if (is_string($value)) {

            $value = str_replace(
                'session/' . $sessionId,
                'order-' . $order->get_id(),
                $value
            );

            $order->update_meta_data(
                $key,
                $value
            );

            continue;
        }

        if (is_array($value)) {

            $updated = [];

            foreach ($value as $item) {

                $updated[] = is_string($item)
                    ? str_replace(
                        'session/' . $sessionId,
                        'order-' . $order->get_id(),
                        $item
                    )
                    : $item;
            }

            $order->update_meta_data(
                $key,
                $updated
            );
        }
    }
}

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a planning session exists.
     *
     * @return bool
     */
    public function hasPlanning(): bool
    {
        return $this->session->has();
    }

    /**
     * Get planning session.
     *
     * @return array
     */
    public function getPlanning(): array
    {
        return $this->session->all();
    }
}