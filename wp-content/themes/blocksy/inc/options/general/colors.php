<?php
/**
 * Colors options
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package   Blocksy
 */

$options = [
	'colors_section_options' => [
		'type' => 'ct-options',
		'setting' => [ 'transport' => 'postMessage' ],
		'inner-options' => [

			[
				'colorPalette' => [
					'label' => __( 'Global Color Palette', 'blocksy' ),
					'type'  => 'ct-color-palettes-picker',
					'design' => 'block',
					// translators: The interpolations addes a html link around the word.
					'desc' => blocksy_safe_sprintf(
						__('Learn more about palettes and colors %shere%s.', 'blocksy'),
						'<a href="https://creativethemes.com/blocksy/docs/general-options/colors/" target="_blank">',
						'</a>'
					),
					'setting' => [ 'transport' => 'postMessage' ],
					'predefined' => true,
					'wrapperAttr' => [
						'data-label' => 'heading-label'
					],

					'value' => [
						'color1' => [
							'color' => '#2872fa',
						],

						'color2' => [
							'color' => '#1559ed',
						],

						'color3' => [
							'color' => '#3A4F66',
						],

						'color4' => [
							'color' => '#192a3d',
						],

						'color5' => [
							'color' => '#e1e8ed',
						],

						'color6' => [
							'color' => '#f2f5f7',
						],

						'color7' => [
							'color' => '#FAFBFC',
						],

						'color8' => [
							'color' => '#ffffff',
						]
					],

					'palettes' => apply_filters('blocksy:options:colors:palette:palettes', [])
				],

			],

			apply_filters('blocksy:options:colors:palette:after', []),

			blocksy_rand_md5() => [
				'type' => 'ct-title',
				'label' => __( 'Global Colors', 'blocksy' ),
			],

			'fontColor' => [
				'label' => __( 'Base Text', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => 'var(--theme-palette-color-3)',
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
					],
				],
			],

			'linkColor' => [
				'label' => __( 'Links', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => 'var(--theme-palette-color-1)',
					],

					'hover' => [
						'color' => 'var(--theme-palette-color-2)',
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
					],

					[
						'title' => __( 'Hover', 'blocksy' ),
						'id' => 'hover',
					],
				],
			],

			'selectionColor' => [
				'label' => __( 'Text Selection', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => '#ffffff',
					],

					'hover' => [
						'color' => 'var(--theme-palette-color-1)',
					],
				],

				'pickers' => [
					[
						'title' => __( 'Text', 'blocksy' ),
						'id' => 'default',
					],

					[
						'title' => __( 'Background', 'blocksy' ),
						'id' => 'hover',
					],
				],
			],

			'border_color' => [
				'label' => __( 'Borders', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => 'var(--theme-palette-color-5)',
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
					],
				],
			],

			'headingColor' => [
				'label' => __( 'All Headings (H1 - H6)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'divider' => 'top',
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => 'var(--theme-palette-color-4)',
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
					],
				],
			],

			'heading_1_color' => [
				'label' => __( 'Heading 1 (H1)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'heading_2_color' => [
				'label' => __( 'Heading 2 (H2)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'heading_3_color' => [
				'label' => __( 'Heading 3 (H3)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'heading_4_color' => [
				'label' => __( 'Heading 4 (H4)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'heading_5_color' => [
				'label' => __( 'Heading 5 (H5)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'heading_6_color' => [
				'label' => __( 'Heading 6 (H6)', 'blocksy' ),
				'type'  => 'ct-color-picker',
				'skipEditPalette' => true,
				'design' => 'inline',
				'setting' => [ 'transport' => 'postMessage' ],

				'value' => [
					'default' => [
						'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT'),
					],
				],

				'pickers' => [
					[
						'title' => __( 'Initial', 'blocksy' ),
						'id' => 'default',
						'inherit' => 'var(--theme-headings-color)',
					],
				],
			],

			'site_background' => [
				'label' => __( 'Site Background', 'blocksy' ),
				'type' => 'ct-background',
				'design' => 'block:right',
				'responsive' => true,
				'divider' => 'top',
				'setting' => [ 'transport' => 'postMessage' ],
				'value' => blocksy_background_default_value([
					'backgroundColor' => [
						'default' => [
							'color' => 'var(--theme-palette-color-7)'
						],
					],
				])
			],

		],
	],
];
