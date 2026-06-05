<?php

$options = [
	'vs_portfolio_archive_options' => [
		'type' => 'ct-options',
		'inner-options' => [
			blocksy_get_options('general/page-title', [
				'prefix' => 'vs_portfolio_archive',
				'is_single' => true,
				'is_cpt' => true,
				'enabled_label' => blocksy_safe_sprintf(
					__('%s Title', 'blocksy'),
					'Portfolios'
				),
				'is_vs_portfolio' => true,
				'location_name' => __('Portfolio Archive', 'blocksy')
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
						'prefix' => 'vs_portfolio_archive',
					]),

				],
			],

			blocksy_rand_md5() => [
				'title' => __( 'Design', 'blocksy' ),
				'type' => 'tab',
				'options' => [

					blocksy_get_options('single-elements/structure-design', [
						'prefix' => 'vs_portfolio_archive',
						'options_conditions' => [
							'vs_portfolio_archive__content_style' => 'nonexistent'
						]
					])

				],
			],

		]
	]
];

