<?php

$option_prefix = $prefix;

// Author and categories pagination settings are the same as blog pagination settings
if ($prefix === 'author' || $prefix === 'categories') {
	$option_prefix = 'blog';
}

$paginationSpacing = blocksy_get_theme_mod($option_prefix . '_paginationSpacing', '60px');

if ($paginationSpacing !== '60px') {
	blocksy_output_responsive([
		'css' => $css,
		'tablet_css' => $tablet_css,
		'mobile_css' => $mobile_css,
		'selector' => blocksy_prefix_selector('.ct-pagination', $prefix),
		'variableName' => 'spacing',
		'value' => $paginationSpacing,
		'unit' => '',
		'previousUnit' => 'px',
	]);
}

blocksy_output_border([
	'css' => $css,
	'selector' => blocksy_prefix_selector('.ct-pagination[data-divider]', $prefix),
	'variableName' => 'pagination-divider',
	'value' => blocksy_get_theme_mod($option_prefix . '_paginationDivider'),
	'default' => [
		'width' => 1,
		'style' => 'none',
		'color' => [
			'color' => 'rgba(224, 229, 235, 0.5)',
		],
	],
	'skip_none' => true
]);

blocksy_output_spacing([
	'css' => $css,
	'tablet_css' => $tablet_css,
	'mobile_css' => $mobile_css,
	'selector' => blocksy_prefix_selector('.ct-pagination', $prefix),
	'property' => 'theme-button-border-radius',
	'value' => blocksy_get_theme_mod(
		$option_prefix . '_pagination_border_radius',
		blocksy_spacing_value()
	),
	'empty_value' => 4,
]);


blocksy_output_colors([
	'value' => blocksy_get_theme_mod($option_prefix . '_simplePaginationFontColor', []),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'active' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="simple"], [data-pagination="next_prev"]',
				$prefix
			),
			'variable' => 'theme-text-color'
		],

		'active' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="simple"]',
				$prefix
			),
			'variable' => 'theme-text-active-color'
		],

		'hover' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="simple"], [data-pagination="next_prev"]',
				$prefix
			),
			'variable' => 'theme-link-hover-color'
		],
	],
]);

blocksy_output_colors([
	'value' => blocksy_get_theme_mod($option_prefix . '_paginationButtonText', []),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="load_more"]',
				$prefix
			),
			'variable' => 'theme-button-text-initial-color'
		],

		'hover' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="load_more"]',
				$prefix
			),
			'variable' => 'theme-button-text-hover-color'
		],
	],
]);

blocksy_output_colors([
	'value' => blocksy_get_theme_mod($option_prefix . '_paginationButton', []),
	'default' => [
		'default' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
		'hover' => [ 'color' => Blocksy_Css_Injector::get_skip_rule_keyword('DEFAULT') ],
	],
	'css' => $css,
	'variables' => [
		'default' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="load_more"]',
				$prefix
			),
			'variable' => 'theme-button-background-initial-color'
		],

		'hover' => [
			'selector' => blocksy_prefix_selector(
				'[data-pagination="load_more"]',
				$prefix
			),
			'variable' => 'theme-button-background-hover-color'
		],
	],
]);
