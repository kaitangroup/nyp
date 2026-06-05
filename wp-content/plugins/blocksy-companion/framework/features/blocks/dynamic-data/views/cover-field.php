<?php

if (! defined('ABSPATH')) {
	exit;
}

$POSITION_CLASSNAMES = [
	'top left' => 'is-position-top-left',
	'top center' => 'is-position-top-center',
	'top right' => 'is-position-top-right',
	'center left' => 'is-position-center-left',
	'center center' => 'is-position-center-center',
	'center right' => 'is-position-center-right',
	'bottom left' => 'is-position-bottom-left',
	'bottom center' => 'is-position-bottom-center',
	'bottom right' => 'is-position-bottom-right',
];

$wrapper_attr = [
	'class' => 'wp-block-cover ct-dynamic-cover',
	'style' => ''
];

$aspect_ratio = blocksy_akg('aspectRatio', $attributes, 'auto');
$minimum_height = blocksy_akg('minimumHeight', $attributes, '');
$size_slug = blocksy_akg('sizeSlug', $attributes, 'full');

$has_parallax = blocksy_akg('hasParallax', $attributes, false);
$is_repeated = blocksy_akg('isRepeated', $attributes, false);
$allow_custom_content_and_wide_size = blocksy_akg('allowCustomContentAndWideSize', $attributes, true);

$focal_point = blocksy_akg('focalPoint', $attributes, []);
$content_position = blocksy_akg('contentPosition', $attributes, 'center center');

if (
	! empty($POSITION_CLASSNAMES[$content_position])
	&&
	$content_position !== 'center center'
) {
	$wrapper_attr['class'] .= ' has-custom-content-position ' . $POSITION_CLASSNAMES[$content_position];
}

$focal_point_result = '';

if (! empty($focal_point)) {
	$focal_point_result = $focal_point['x'] * 100 . '% ' . $focal_point['y'] * 100 . '%;';
}

$classes = [];

if ($has_parallax) {
	$wrapper_attr['class'] .= ' has-parallax';
}

if ($is_repeated) {
	$wrapper_attr['class'] .= ' is-repeated';
}

if (! empty($attributes['imageAlign'])) {
	$wrapper_attr['class'] .=  ' align' . $attributes['imageAlign'];
}

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

if (! empty($border_result['class'])) {
	$wrapper_attr['class'] .= ' ' . $border_result['class'];
}

if (! empty($border_result['style'])) {
	$wrapper_attr['style'] .= $border_result['style'];
}

if ($aspect_ratio !== 'auto') {
	$wrapper_attr['style'] .= 'aspect-ratio: ' . $aspect_ratio . ';';
	$minimum_height = 'unset';
}

if (! empty($minimum_height)) {
	$wrapper_attr['style'] .= 'min-height: ' . $minimum_height . ';';
}

$wrapper_attr = get_block_wrapper_attributes($wrapper_attr);

$img_attr = [
	'class' => 'wp-block-cover__image-background wp-post-image',
];

$image_result = '';

if (
	! $has_parallax
	&&
	! $is_repeated
) {
	if (! empty($focal_point_result)) {
		$img_attr['style'] = 'object-position: ' . $focal_point_result;
	}

	$image_result = wp_get_attachment_image(
		$attachment_id,
		$size_slug,
		false,
		$img_attr
	);
}

if (
	$has_parallax
	||
	$is_repeated
) {
	$attachment = wp_get_attachment_image_src(
		$attachment_id,
		$size_slug
	);

	$image_result = blocksy_html_tag(
		'div',
		[
			'class' => "wp-block-cover__image-background wp-image-{$attachment_id} has-parallax",
			'style' => 'background-image: url(' . $attachment[0] . ');background-position: 50% 50%;'
		],
		''
	);
}

$overlay_classes = [
	'wp-block-cover__background',
];

$overlay_atts = [
	'aria-hidden' => 'true',
];

$overlay_content = blocksy_html_tag(
	'span',
	array_merge(
		[
			'class' => join(' ', $overlay_classes),
		],
		$overlay_atts
	),
	''
);

$inner_classes = [
	'wp-block-cover__inner-container',
	wp_unique_id('ct-dynamic-cover__inner-')
];

if ($allow_custom_content_and_wide_size) {
	$inner_classes[] = 'is-layout-constrained';
	$inner_classes[] = 'wp-block-cover-is-layout-constrained';
} else {
	$inner_classes[] = 'is-layout-flow';
	$inner_classes[] = 'wp-block-cover-is-layout-flow';
}

$content_size = blocksy_akg('contentSize', $attributes, 0);
$wide_size = blocksy_akg('wideSize', $attributes, 0);

if (! empty($content_size)) {
	$css = new \Blocksy_Css_Injector();
	$selector = '.' . join('.', $inner_classes) . ' > :where(:not(.alignleft):not(.alignright):not(.alignfull))';

	$css->put(
		$selector,
		'max-width: ' . $content_size
	);

	$css->put(
		$selector,
		'margin-left: auto !important'
	);

	$css->put(
		$selector,
		'margin-right: auto !important'
	);

	\Blocksy\Plugin::instance()->inline_styles_collector->add([
		'css' => $css
	]);
}

if (! empty($wide_size)) {
	$css = new \Blocksy_Css_Injector();

	$css->put(
		'.' . join('.', $inner_classes) . ' > .alignwide',
		'max-width: ' . $wide_size
	);

	\Blocksy\Plugin::instance()->inline_styles_collector->add([
		'css' => $css
	]);
}

$content = blocksy_html_tag(
	'div',
	$wrapper_attr,
	$overlay_content .
	$image_result .
	blocksy_html_tag(
		'div',
		[
			'class' => join(' ', $inner_classes)
		],
		$content
	)
);

wp_enqueue_style('wp-block-cover');
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
