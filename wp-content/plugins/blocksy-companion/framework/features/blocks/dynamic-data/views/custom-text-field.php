<?php

if (! defined('ABSPATH')) {
	exit;
}

$value_fallback = blocksy_akg('fallback', $attributes, '');

if (! $value) {
	$value = '';
}

$has_fallback = false;

if (empty($value) && ! empty($value_fallback)) {
	$has_fallback = true;
	$value = do_shortcode($value_fallback);
}

if (
	! is_string($value)
	||
	empty(trim($value))
) {
	return;
}

$value_after = blocksy_akg('after', $attributes, '');
$value_before = blocksy_akg('before', $attributes, '');

$has_field_link = blocksy_akg('has_field_link', $attributes, 'no');
$has_field_link_wrap_content = blocksy_akg('has_field_link_wrap_content', $attributes, 'no');

$link_source = blocksy_akg('link_source', $attributes, '');

$final_link = [
	'value' => '',
];

if (
	$has_field_link === 'yes'
	&&
	! empty($link_source)
) {
	$link_field_descriptor = explode(':', $link_source);
	
	if (count($link_field_descriptor) === 2) {
		$final_link = blocksy_companion_get_ext('post-types-extra')
			->dynamic_data
			->custom_fields_manager
			->render_field(
				$link_field_descriptor[1],
				[
					'provider' => $link_field_descriptor[0],
					'allow_images' => true
				]
			);
		}

	
}

$tagName = blocksy_akg('tagName', $attributes, 'div');

$classes = ['ct-dynamic-data'];

if (! empty($attributes['align'])) {
	$classes[] = 'has-text-align-' . $attributes['align'];
}

$wrapper_attr['class'] = implode(' ', $classes);

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

if (! empty($border_result['class'])) {
	$wrapper_attr['class'] .= ' ' . $border_result['class'];
}

if (! empty($border_result['style'])) {
	$wrapper_attr['style'] = $border_result['style'];
}

$block_type = WP_Block_Type_Registry::get_instance()->get_registered('blocksy/dynamic-data');
$block_type->supports['color'] = true;
wp_apply_colors_support($block_type, $attributes);

$link_attr = [];

if ($has_field_link === 'yes') {
	$link_attr = [
		'href' => $final_link['value'],
	];

	if (blocksy_akg('has_field_link_new_tab', $attributes, 'no') === 'yes') {
		$link_attr['target'] = '_blank';
	}

	if (! empty(blocksy_akg('has_field_link_rel', $attributes, ''))) {
		$link_attr['rel'] = blocksy_akg(
			'has_field_link_rel',
			$attributes,
			''
		);
	}

	if ($has_field_link_wrap_content === 'no') {
		$value = blocksy_html_tag('a', $link_attr, $value);
	}
}

$wrapper_attr = get_block_wrapper_attributes($wrapper_attr);

if (! empty($value_after) && ! $has_fallback) {
	$value .= $value_after;
}

if (! empty($value_before) && ! $has_fallback) {
	$value = $value_before . $value;
}

if (
	$has_field_link_wrap_content === 'yes'
	&&
	$has_field_link === 'yes'
) {
	$value = blocksy_html_tag('a', $link_attr, $value);
}

blocksy_html_tag_e($tagName, $wrapper_attr, $value);

