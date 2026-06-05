<?php

if (! defined('ABSPATH')) {
	exit;
}

$options = [];

$has_woo = class_exists('WooCommerce');
$woo_rules = [];

$custom_taxonomies = [];

$brands_enabled = taxonomy_exists('product_brand');

if ($filter === 'all') {
	$taxonomies = get_object_taxonomies('product');
	$taxonomies = array_diff($taxonomies, ['product_type', 'product_visibility', 'product_cat', 'product_tag', 'product_brand', 'product_shipping_class']);

	foreach ($taxonomies as $single_taxonomy) {
		$taxonomy = get_taxonomy($single_taxonomy);

		if (!$taxonomy) {
			continue;
		}

		if (!$taxonomy->publicly_queryable) {
			continue;
		}
		
		$custom_taxonomies[] = [
			'id' => 'post_type_product_taxonomy_' . $single_taxonomy,
			'title' => blocksy_companion_safe_sprintf(
				// translators: %1$s is the label of the taxonomy.
				__('Product %1$s', 'blocksy-companion'),
				$taxonomy->label
			)
		];
	}

	$woo_rules = array_merge(
		[
			[
				'id' => 'woo_shop',
				'title' => __('Shop Home', 'blocksy-companion')
			],
	
			[
				'id' => 'single_product',
				'title' => __('Single Product', 'blocksy-companion')
			],
	
			[
				'id' => 'all_product_archives',
				'title' => __('Product Archives', 'blocksy-companion')
			],
	
			[
				'id' => 'all_product_categories',
				'title' => __('Product Categories', 'blocksy-companion')
			],
	
			[
				'id' => 'all_product_attributes',
				'title' => __('Product Attributes', 'blocksy-companion')
			],
		],
		(
			$brands_enabled ? [
				[
					'id' => 'all_product_brands',
					'title' => __('Product Brands', 'blocksy-companion')
				]
			] : []
		),
		[
			[
				'id' => 'all_product_tags',
				'title' => __('Product Tags', 'blocksy-companion')
			],
		],
		$custom_taxonomies,
		[
			[
				'id' => 'product_ids',
				'title' => __('Single Product ID', 'blocksy-companion')
			],
	
			[
				'id' => 'product_with_taxonomy_ids',
				'title' => __('Single Product with Taxonomy ID', 'blocksy-companion'),
				'post_type' => 'product'
			],
	
			[
				'id' => 'product_taxonomy_ids',
				'title' => __('Taxonomy ID', 'blocksy-companion')
			],
		]
	);
}

if ($filter === 'product_tabs') {
	$woo_rules = [
		[
			'id' => 'product_ids',
			'title' => __('Product ID', 'blocksy-companion')
		],

		[
			'id' => 'product_with_taxonomy_ids',
			'title' => __('Product with Taxonomy ID', 'blocksy-companion'),
			'post_type' => 'product'
		]
	];
}

if ($filter === 'product_waitlist') {
	$woo_rules = [
		[
			'id' => 'product_ids',
			'title' => __('Product ID', 'blocksy-companion')
		],

		[
			'id' => 'product_with_taxonomy_ids',
			'title' => __('Product with Taxonomy ID', 'blocksy-companion'),
			'post_type' => 'product'
		]
	];
}

if ($has_woo) {
	$options = [
		[
			'title' => __('WooCommerce', 'blocksy-companion'),
			'rules' => $woo_rules
		]
	];
}
