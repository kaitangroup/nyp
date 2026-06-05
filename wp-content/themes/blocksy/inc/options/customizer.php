<?php
/**
 * Customizer options
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Blocksy
 */
$custom_post_types = blocksy_get_options('general/custom-post-types');

$extensions_options = apply_filters(
	'blocksy_extensions_customizer_options',
	[]
);

$pro_title = [
	blocksy_rand_md5() => [
		'type' => 'ct-group-title',
		'title' => '<div class="ct-onboarding-button">
			<button class="button">
			' . __('View Pro Features', 'blocksy') . '
			</button>
		</div>',
		'priority' => 1,
	]
];

if (function_exists('blocksy_companion_site_has_feature') && blocksy_companion_site_has_feature('base_pro')) {
	$pro_title = [];
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$options = [
	$pro_title,
	[
		'general_options_title' => [
			'type' => 'ct-group-title',
			'title' => __( 'General Options', 'blocksy' ),
			'priority' => 1,
		],

		'general' => [
			'title' => __( 'General', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/general' ),
		],

		'header' => [
			'title' => __( 'Header', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/header' ),
		],

		'footer' => [
			'title' => __( 'Footer', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/footer' ),
		],

		'sidebar' => [
			'title' => __( 'Sidebar', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/sidebar' ),
		],

		'color' => [
			'title' => __( 'Colors', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/colors' ),
		],

		'typography' => [
			'title' => __( 'Typography', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/typography' ),
		],

		'performance' => [
			'title' => __( 'Performance', 'blocksy' ),
			'container' => [ 'priority' => 1 ],
			'options' => blocksy_get_options( 'general/performance' ),
		],

		'post_types_title' => [
			'type' => 'ct-group-title',
			'title' => __( 'Post types', 'blocksy' ),
			'priority' => 2,
		],

		'blog_posts' => [
			'title' => __( 'Blog Posts', 'blocksy' ),
			'container' => [ 'priority' => 2 ],
			'options' => blocksy_get_options( 'posts/blog' ),
		],

		'single_blog_posts' => [
			'title' => __( 'Single Post', 'blocksy' ),
			'container' => [
				'priority' => 2,
				'type' => 'child',
			],
			'options' => blocksy_get_options( 'posts/post' ),
		],

		'archive_blog_posts_categories' => [
			'title' => __('Categories', 'blocksy'),
			'container' => [
				'priority' => 2,
				'type' => 'child',
			],
			'options' => blocksy_get_options( 'posts/categories' ),
		],

		'archive_blog_posts_categories_divider' => [
			'type' => 'ct-group-title',
			'kind' => 'divider',
			'priority' => 2,
		],

		'single_pages' => [
			'title' => __( 'Pages', 'blocksy' ),
			'container' => [ 'priority' => 2 ],
			'options' => blocksy_get_options( 'pages/page' ),
		],

		'author_page' => [
			'title' => __( 'Author Page', 'blocksy' ),
			'container' => [ 'priority' => 2 ],
			'options' => blocksy_get_options( 'pages/author-page' ),
		],

		'search_page' => [
			'title' => __( 'Search Page', 'blocksy' ),
			'container' => [ 'priority' => 2 ],
			'options' => blocksy_get_options( 'pages/search-page' ),
		],
	],

	[
		class_exists('WooCommerce') ? [
			$custom_post_types,
			'woocommerce_group_title' => [
				'type' => 'ct-group-title',
				'title' => __( 'WooCommerce', 'blocksy' ),
				'priority' => 3,
			],

			'woocommerce_general' => [
				'title' => __( 'General', 'blocksy' ),
				'container' => [
					'priority' => 3
				],
				'options' => blocksy_get_options( 'woocommerce/general-main' ),
			],

			'woocommerce_posts_archives' => [
				'title' => __( 'Product Archives', 'blocksy' ),
				'container' => [
					'priority' => 3
				],
				'options' => blocksy_get_options( 'woocommerce/archive-main' ),
			],

			'woocommerce_single' => [
				'title' => __( 'Single Product', 'blocksy' ),
				'container' => [
					'priority' => 3,
				],
				'options' => blocksy_get_options( 'woocommerce/single-main' ),
			],

			apply_filters(
				'blocksy_customizer_options:woocommerce:end',
				[]
			)
		] : [
			$custom_post_types
		],
	],

	apply_filters(
		'blocksy_misc_end_section_customizer_options',
		[]
	),

	empty($extensions_options) ? [] : [

		'extensions_group_title' => [
			'type' => 'ct-group-title',
			'title' => __( 'Extensions', 'blocksy' ),
			'priority' => 7,
		],

	],

	$extensions_options
];
