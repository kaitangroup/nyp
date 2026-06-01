<?php
/**
 * Copyright (c) Bytedance, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package TikTok
 */
require_once 'Tt4b_Pixel_Class.php';
add_action( 'woocommerce_add_to_cart', array( 'Tt4b_Pixel_Class', 'inject_add_to_cart_event' ), 40, 4 );
add_action( 'woocommerce_before_single_product_summary', array( 'Tt4b_Pixel_Class', 'inject_view_content_event' ) );
add_action( 'woocommerce_payment_complete', array( 'Tt4b_Pixel_Class', 'inject_purchase_event' ) );
add_action( 'woocommerce_thankyou', array( 'Tt4b_Pixel_Class', 'inject_purchase_event' ) );
add_action( 'woocommerce_after_checkout_form', array( 'Tt4b_Pixel_Class', 'inject_initiate_checkout_event' ) );
add_action( 'woocommerce_blocks_checkout_enqueue_data', array( 'Tt4b_Pixel_Class', 'inject_initiate_checkout_event' ) );
add_action( 'wp_head', array( 'Tt4b_Pixel_Class', 'print_script' ) );
add_action( 'init', array( 'Tt4b_Pixel_Class', 'set_ttclid' ) );
add_action( 'wp_enqueue_scripts', array( 'Tt4b_Pixel_Class', 'add_ajax_snippet' ) );
//add_filter( 'woocommerce_loop_add_to_cart_args', array( 'Tt4b_Pixel_Class', 'filter_add_to_cart_attributes' ), 10, 2 );
