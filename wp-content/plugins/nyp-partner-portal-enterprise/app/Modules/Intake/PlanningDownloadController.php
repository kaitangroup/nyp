<?php

declare(strict_types=1);

namespace NYP\Modules\Intake;

use WC_Order;
use NYP\Modules\Intake\PlanningFileLocator;
use NYP\Modules\Intake\PlanningFileAuthorizer;

defined('ABSPATH') || exit;

class PlanningDownloadController
{
    public const QUERY_VAR = 'nyp_download';

    protected PlanningFileAuthorizer $authorizer;

    protected PlanningFileLocator $locator;

    public function __construct(
        ?PlanningFileAuthorizer $authorizer = null,
        ?PlanningFileLocator $locator = null
    ) {
        $this->authorizer = $authorizer ?: new PlanningFileAuthorizer();
    
        $this->locator = $locator ?: new PlanningFileLocator();
    }

    /**
     * Register hooks.
     */
    public function register(): void
{
    add_action(
        'admin_post_nyp_download_file',
        [$this, 'handleAdminDownload']
    );

    add_action(
        'init',
        [$this, 'handleRequest']
    );
}

public function handleAdminDownload(): void
{
    check_admin_referer(
        'nyp_download_file'
    );

    $orderId = absint(
        $_GET['order_id'] ?? 0
    );

    $metaKey = sanitize_key(
        $_GET['meta_key'] ?? ''
    );

    $index = isset($_GET['index'])
    ? absint($_GET['index'])
    : null;

    if (!$orderId || empty($metaKey)) {
        wp_die('Invalid download request.');
    }

    $order = wc_get_order($orderId);

    if (!$order instanceof WC_Order) {
        wp_die('Order not found.');
    }

    if (
        !$this->authorizer->canDownload(
            $order,
            get_current_user_id()
        )
    ) {
        wp_die('Access denied.');
    }

    $value = $order->get_meta($metaKey);

if (is_array($value)) {

    if (
        !isset($value[$index])
    ) {
        wp_die('Invalid file.');
    }

    $relativePath = $value[$index];

} else {

    $relativePath = $value;
}

$path = $this->locator->locate(
    $relativePath
);

    if (!$path) {
        wp_die('File not found.');
    }

    $this->streamFile($path);
}

    /**
 * Handle secure frontend download requests.
 */
public function handleRequest(): void
{
    if (empty($_GET[self::QUERY_VAR])) {
        return;
    }

    $orderId = absint(
        $_GET['order'] ?? 0
    );

    $metaKey = sanitize_key(
        $_GET['file'] ?? ''
    );

    $index = isset($_GET['index'])
        ? absint($_GET['index'])
        : null;

    if (
        !$orderId ||
        empty($metaKey)
    ) {
        wp_die(
            esc_html__(
                'Invalid download request.',
                'nyp'
            ),
            400
        );
    }

    $order = wc_get_order($orderId);

    if (
        !$order instanceof WC_Order
    ) {
        wp_die(
            esc_html__(
                'Order not found.',
                'nyp'
            ),
            404
        );
    }

    if (
        !$this->authorizer->canDownload(
            $order,
            get_current_user_id()
        )
    ) {
        wp_die(
            esc_html__(
                'Access denied.',
                'nyp'
            ),
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve uploaded file
    |--------------------------------------------------------------------------
    */

    $value = $order->get_meta(
        $metaKey,
        true
    );

    if (is_array($value)) {

        if (
            $index === null ||
            !isset($value[$index])
        ) {
            wp_die(
                esc_html__(
                    'Invalid file.',
                    'nyp'
                ),
                404
            );
        }

        $relativePath = $value[$index];

    } else {

        $relativePath = $value;
    }

    $path = $this->locator->locate(
        $relativePath
    );

    if (!$path) {
        wp_die(
            esc_html__(
                'File not found.',
                'nyp'
            ),
            404
        );
    }

    $this->streamFile($path);
}
    /**
     * Stream the file.
     */
    protected function streamFile(
        string $path
    ): void {

        nocache_headers();

        header(
            'Content-Type: ' .
            (mime_content_type($path) ?: 'application/octet-stream')
        );

        header(
            'Content-Length: ' .
            filesize($path)
        );

        header(
            'Content-Disposition: attachment; filename="' .
            basename($path) .
            '"'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        readfile($path);

        exit;
    }
}