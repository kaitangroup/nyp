<?php

if (!defined('ABSPATH')) {
    exit;
}

class ProductVisibilityManager
{
    public function register()
    {
        add_action('wp', [$this, 'bootstrap']);

        add_filter(
            'woocommerce_get_price_html',
            [$this, 'filter_price_html'],
            9999,
            2
        );

        add_filter(
            'woocommerce_is_purchasable',
            [$this, 'filter_purchasable'],
            9999,
            2
        );
    }

    public function bootstrap()
    {
        if ($this->can_access_products()) {
            return;
        }

        /*
         * Shop Loop
         */
        remove_action(
            'woocommerce_after_shop_loop_item',
            'woocommerce_template_loop_add_to_cart',
            10
        );

        /*
         * Single Product
         */
        remove_action(
            'woocommerce_single_product_summary',
            'woocommerce_template_single_add_to_cart',
            30
        );

        add_action(
            'woocommerce_single_product_summary',
            [$this, 'render_access_notice'],
            30
        );
    }

    public function filter_price_html($price, $product)
    {
        if ($this->can_access_products()) {
            return $price;
        }

        return sprintf(
            '<span class="nyp-price-hidden">%s</span>',
            esc_html__(
                'Login as an approved partner to view pricing.',
                'nyp-partner-portal'
            )
        );
    }

    public function filter_purchasable($purchasable, $product)
    {
        return $this->can_access_products();
    }

    public function render_access_notice()
    {
        echo '<div class="woocommerce-info">';
        echo esc_html__(
            'Please register and obtain partner approval to place orders.',
            'nyp-partner-portal'
        );
        echo '</div>';
    }

    /**
     * Central access check
     */
    private function can_access_products()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();

        /*
         * Administrators
         */
        if (in_array('administrator', $user->roles, true)) {
            return true;
        }

        /*
         * Shop Managers
         */
        if (in_array('shop_manager', $user->roles, true)) {
            return true;
        }

        /*
         * Partners only
         */
        if (!in_array('nyp_partner', $user->roles, true)) {
            return false;
        }

        $status = get_user_meta(
            $user->ID,
            'nyp_partner_status',
            true
        );

        return $status === 'approved';
    }
}
