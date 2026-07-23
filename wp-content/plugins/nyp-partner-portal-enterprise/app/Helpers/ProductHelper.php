<?php

declare(strict_types=1);

namespace NYP\Helpers;

use WC_Product;

defined('ABSPATH') || exit;

class ProductHelper
{
    public const OVERNIGHT_PRODUCT_ID = 30;

    public static function isOvernightUpgrade(
        WC_Product $product
    ): bool {
        return $product->get_id() === self::OVERNIGHT_PRODUCT_ID;
    }
    
    
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