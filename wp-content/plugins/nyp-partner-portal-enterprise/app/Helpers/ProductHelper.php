<?php

declare(strict_types=1);

namespace NYP\Helpers;

use WC_Product;

defined('ABSPATH') || exit;

class ProductHelper
{
    /**
     * Determine whether the product is an NYP Kitchen Planning package.
     */
    public static function isPlanningProduct(WC_Product $product): bool
    {
        return has_term(
            'kitchen-planning',
            'product_cat',
            $product->get_id()
        );
    }
}