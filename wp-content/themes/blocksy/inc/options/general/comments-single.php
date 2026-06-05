<?php

if (! isset($prefix)) {
	$prefix = '';
} else {
	$prefix = $prefix . '_';
}

if (! isset($enabled)) {
	$enabled = 'yes';
}

$options = [
	$prefix . 'has_comments' => [
		'label' => __( 'Comments', 'blocksy' ),
		'type' => 'ct-panel',
		'switch' => true,
		'value' => $enabled,
		'sync' => blocksy_sync_whole_page([
			'prefix' => $prefix,
		]),
		'inner-options' => [

			blocksy_rand_md5() => [
				'title' => __( 'General', 'blocksy' ),
				'type' => 'tab',
				'options' => [

					$prefix . 'has_comments_website' => [
						'label' => __( 'Website Input Field', 'blocksy' ),
						'type' => 'ct-switch',
						'value' => 'yes',
						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
					],

					$prefix . 'comments_label_position' => [
						'label' => __('Inputs Label Position', 'blocksy'),
						'type' => 'ct-radio',
						'value' => 'outside',
						'view' => 'text',
						'design' => 'block',
						'divider' => 'top',
						'choices' => [
							'outside' => __('Outside', 'blocksy'),
							'inside' => __('Inside', 'blocksy'),
						],

						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
					],

					$prefix . 'comments_heading_tag' => [
						'label' => __('Module Title Tag', 'blocksy'),
						'type' => 'ct-select',
						'value' => 'h3',
						'view' => 'text',
						'design' => 'inline',
						'divider' => 'top:full',
						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
						'choices' => blocksy_ordered_keys(
							[
								'h1' => 'H1',
								'h2' => 'H2',
								'h3' => 'H3',
								'h4' => 'H4',
								'h5' => 'H5',
								'h6' => 'H6',
								// 'p' => 'p',
								// 'span' => 'span',
							]
						),
					],


					$prefix . 'comments_author_heading_tag' => [
						'label' => __('Author Name Tag', 'blocksy'),
						'type' => 'ct-select',
						'value' => 'h4',
						'view' => 'text',
						'design' => 'inline',
						// 'divider' => 'top',
						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
						'choices' => blocksy_ordered_keys(
							[
								'h1' => 'H1',
								'h2' => 'H2',
								'h3' => 'H3',
								'h4' => 'H4',
								'h5' => 'H5',
								'h6' => 'H6',
								// 'p' => 'p',
								// 'span' => 'span',
							]
						),
					],

					$prefix . 'comments_position' => [
						'label' => __('Comment Form Position', 'blocksy'),
						'type' => 'ct-radio',
						'value' => 'below',
						'view' => 'text',
						'design' => 'block',
						'divider' => 'top:full',
						'choices' => [
							'below' => __('Below List', 'blocksy'),
							'above' => __('Above List', 'blocksy'),
						],

						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
					],

					blocksy_rand_md5() => [
						'type' => 'ct-divider',
					],

					$prefix . 'comments_containment' => [
						'label' => __('Module Placement', 'blocksy'),
						'type' => 'ct-radio',
						'value' => 'separated',
						'view' => 'text',
						'design' => 'block',
						'desc' => __('Separate or unify the comments module from or with the entry content area.', 'blocksy'),
						'choices' => [
							'separated' => __('Separated', 'blocksy'),
							'contained' => __('Contained', 'blocksy'),
						],

						'sync' => blocksy_sync_whole_page([
							'prefix' => $prefix,
						]),
		            ],

					blocksy_rand_md5() => [
						'type' => 'ct-divider',
					],

					blocksy_rand_md5() => [
						'type' => 'ct-condition',
						'condition' => [ $prefix . 'comments_containment' => 'separated' ],
						'options' => [

							$prefix . 'comments_structure' => [
								'label' => __( 'Container Structure', 'blocksy' ),
								'type' => 'ct-radio',
								'value' => 'narrow',
								'view' => 'text',
								'design' => 'block',
								'choices' => [
									'narrow' => __( 'Narrow', 'blocksy' ),
									'normal' => __( 'Normal', 'blocksy' ),
								],
								'sync' => 'live'
							],

						],
					],

					blocksy_rand_md5() => [
						'type' => 'ct-condition',
						'condition' => [
							$prefix . 'comments_containment' => 'separated',
							$prefix . 'comments_structure' => 'narrow'
						],
						'options' => [
							$prefix . 'comments_narrow_width' => [
								'label' => __( 'Container Max Width', 'blocksy' ),
								'type' => 'ct-slider',
								'value' => 750,
								'min' => 500,
								'max' => 800,
								'divider' => 'bottom',
								'sync' => 'live'
							],
						],
					],

				],
			],

			blocksy_rand_md5() => [
				'title' => __( 'Design', 'blocksy' ),
				'type' => 'tab',
				'options' => [

					$prefix . 'comments_author_name_font_color' => [
						'label' => __( 'Author Name Font Color', 'blocksy' ),
						'type'  => 'ct-color-picker',
						'design' => 'inline',
						'sync' => 'live',
						'value' => [
							'default' => [
								'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],

							'hover' => [
								'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],
						],

						'pickers' => [
							[
								'title' => __( 'Initial', 'blocksy' ),
								'id' => 'default',
								'inherit' => 'var(--theme-heading-color, var(--theme-heading-4-color, var(--theme-headings-color)))'
							],

							[
								'title' => __( 'Hover', 'blocksy' ),
								'id' => 'hover',
								'inherit' => 'var(--theme-link-hover-color)'
							],
						],
					],

					$prefix . 'comments_font_color' => [
						'label' => __( 'Text Font Color', 'blocksy' ),
						'type'  => 'ct-color-picker',
						'design' => 'inline',
						'sync' => 'live',
						'value' => [
							'default' => [
								'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],

							'hover' => [
								'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
							],
						],

						'pickers' => [
							[
								'title' => __( 'Initial', 'blocksy' ),
								'id' => 'default',
								'inherit' => 'var(--theme-text-color)'
							],

							[
								'title' => __( 'Hover', 'blocksy' ),
								'id' => 'hover',
								'inherit' => 'var(--theme-link-hover-color)'
							],
						],
					],

					blocksy_rand_md5() => [
						'type' => 'ct-condition',
						'condition' => [ $prefix . 'comments_containment' => 'separated' ],
						'options' => [

							$prefix . 'comments_background' => [
								'label' => __( 'Container Background', 'blocksy' ),
								'type' => 'ct-background',
								'design' => 'inline',
								'divider' => 'top',
								'sync' => 'live',
								'value' => blocksy_background_default_value([
									'backgroundColor' => [
										'default' => [
											'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
										],
									],
								])
							],

						],
					],

				],
			],

		],
	],
];
