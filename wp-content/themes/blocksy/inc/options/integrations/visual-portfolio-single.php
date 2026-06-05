<?php

$prefix = 'vs_portfolio_single_';
$post_type = 'portfolio';

$maybe_taxonomy = blocksy_maybe_get_matching_taxonomy($post_type, false);

$options = [
	'vs_portfolio_single_options' => [
		'type' => 'ct-options',
		'inner-options' => [
			blocksy_get_options('general/page-title', [
				'prefix' => 'vs_portfolio_single',
				'is_single' => true,
				'is_cpt' => true,
				'enabled_label' => blocksy_safe_sprintf(
					__('%s Title', 'blocksy'),
					'Portfolio'
				),
				'location_name' => __('Portfolio Single', 'blocksy')
			]),

			blocksy_rand_md5() => [
				'type' => 'ct-title',
				'label' => __( 'Portfolio Structure', 'blocksy' ),
			],

			blocksy_rand_md5() => [
				'title' => __( 'General', 'blocksy' ),
				'type' => 'tab',
				'options' => [
					blocksy_get_options('single-elements/structure', [
						'default_structure' => 'type-4',
						'prefix' => 'vs_portfolio_single',
					]),

					blocksy_get_options('single-elements/featured-image', [
						'prefix' => 'vs_portfolio_single',
					]),

					$maybe_taxonomy ? [
						blocksy_get_options('single-elements/post-tags', [
							'prefix' => 'vs_portfolio_single',
							'post_type' => $post_type
						]),
					] : [],

					blocksy_get_options('single-elements/post-share-box', [
						'prefix' => 'vs_portfolio_single',
						'has_share_box' => 'no',
					]),

					blocksy_get_options('single-elements/author-box', [
						'prefix' => 'vs_portfolio_single',
					]),

					blocksy_get_options('single-elements/post-nav', [
						'prefix' => 'vs_portfolio_single',
						'enabled' => 'no',
						'post_type' => $post_type
					]),

					[
						blocksy_rand_md5() => [
							'type' => 'ct-title',
							'label' => __( 'Page Elements', 'blocksy' ),
						],
					],

					blocksy_get_options('single-elements/related-posts', [
						'prefix' => 'vs_portfolio_single',
						'enabled' => 'no',
						'post_type' => $post_type
					]),

					blocksy_get_options('general/comments-single', [
						'prefix' => 'vs_portfolio_single',
					])
				],
			],

			blocksy_rand_md5() => [
				'title' => __( 'Design', 'blocksy' ),
				'type' => 'tab',
				'options' => [

					blocksy_get_options('single-elements/structure-design', [
						'prefix' => 'vs_portfolio_single',
					])

				],
			],

		]
	]
];

