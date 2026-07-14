<?php

namespace NYP\Modules\Intake;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use NYP\Helpers\ProductHelper;

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

    public function moveToAwaitingReview(
        int $orderId
    ): void
    {
        $order = wc_get_order($orderId);
    
        if (!$order instanceof \WC_Order) {
            return;
        }
    
        if (!$this->isPlanningOrder($order)) {
            return;
        }
    
        $order->update_status(
            'awaiting-review',
            __('Payment received. Awaiting NYP review.', 'nyp')
        );
    }



/**
 * Determine whether the order contains at least one
 * NYP Planning product.
 *
 * @param WC_Order $order
 *
 * @return bool
 */
protected function isPlanningOrder(
    WC_Order $order
): bool {

    foreach ($order->get_items() as $item) {

        if (!$item instanceof WC_Order_Item_Product) {
            continue;
        }

        $product = $item->get_product();

        if (!$product instanceof WC_Product) {
            continue;
        }

        if (
            ProductHelper::isPlanningProduct(
                $product
            )
        ) {
            return true;
        }
    }

    return false;
}

    public function registerStatuses(): void
    {
        $statuses = [
            'wc-awaiting-review' => 'Paid – Awaiting NYP Review',
            'wc-in-planning'     => 'In Planning',
            'wc-ready-review'    => 'Ready For Review',
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

                $newStatuses['wc-awaiting-review']
                = 'Paid – Awaiting NYP Review';

                $newStatuses['wc-in-planning']
                    = 'In Planning';

                $newStatuses['wc-ready-review']
                    = 'Ready For Review';
            }
        }

        return $newStatuses;
    }
}