<?php

if (! defined('ABSPATH')) {
	exit;
}

$view_type = blocksy_akg('viewType', $attributes, 'default');

if ($view_type === 'cover') {
	if (
		! $attachment_id
		&&
		empty($content)
	) {
		return;
	}

	blocksy_render_view_e(
		dirname(__FILE__) . '/cover-field.php',
		[
			'attributes' => $attributes,
			'field' => $field,
			'content' => $content,
			'attachment_id' => $attachment_id
		]
	);

	return;
}

if (! $attachment_id) {
	return;
}

$aspect_ratio = blocksy_akg('aspectRatio', $attributes, 'auto');
$image_fit = blocksy_akg('imageFit', $attributes, 'cover');
$height = blocksy_akg('height', $attributes, '');

$lightbox = blocksy_akg('lightbox', $attributes, '');
$video_thumbnail = blocksy_akg('videoThumbnail', $attributes, '');
$image_hover_effect = blocksy_akg('image_hover_effect', $attributes, '');

$size_slug = blocksy_akg('sizeSlug', $attributes, 'full');
$alt_text = blocksy_akg('alt_text', $attributes, '');

if (empty($alt)) {
	$alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
}

$has_field_link = blocksy_akg('has_field_link', $attributes, 'no');
$has_field_link_new_tab = blocksy_akg('has_field_link_new_tab', $attributes, 'no');
$has_field_link_rel = blocksy_akg('has_field_link_rel', $attributes, '');

if (empty($url)) {
	$has_field_link = 'no';
}

$img_attr = [
	'style' => ''
];

$aria_label = get_the_title();

if (
	$attributes['field'] === 'wp:term_image'
	||
	$attributes['field'] === 'wp:archive_image'
) {
	global $blocksy_term_obj;

	if (! empty($blocksy_term_obj)) {
		$aria_label = $blocksy_term_obj->name;
	}

	$maybe_term_obj = get_queried_object();

	if (
		! empty($maybe_term_obj)
		&&
		! empty($maybe_term_obj->name)
	) {
		$aria_label = $maybe_term_obj->name;
	}
}

$wrapper_attr = [
	'class' => 'ct-dynamic-media',
	'aria-label' => wp_strip_all_tags($aria_label),
];

$link_attr = [];

$classes = [];
$styles = [];

$maybe_video = null;

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

// Aspect aspectRatio with a height set needs to override the default width/height.
if (! empty($aspect_ratio)) {
	$img_attr['style'] .= 'width:100%;height:100%;';
} elseif (! empty($height) ) {
	$img_attr['style'] .= "height:{$attributes['height']};";
}

$img_attr['style'] .= "object-fit: {$image_fit};";

if (! empty($alt_text)) {
	$img_attr['alt'] = $alt_text;
}

if ($video_thumbnail === 'yes') {
	$maybe_video = blocksy_has_video_element([
		'display_video' => true,
		'attachment_id' => $attachment_id,
	]);
}

if (
	! empty($attributes['aspectRatio'])
	&&
	$aspect_ratio !== 'auto'
) {
	$img_attr['style'] .= 'aspect-ratio: ' . $aspect_ratio . ';';
}

if (
	$image_hover_effect === 'none'
	&&
	! $maybe_video
) {
	if (! empty($border_result['class'])) {
		$img_attr['class'] = $border_result['class'];
	}

	if (! empty($border_result['style'])) {
		$img_attr['style'] .= $border_result['style'];
	}
}

$value = wp_get_attachment_image(
	$attachment_id,
	$size_slug,
	false,
	$img_attr
);

if (
	$has_field_link === 'yes'
	&&
	(
		! $maybe_video
		||
		$video_thumbnail !== 'yes'
	)
) {
	$link_attr = [
		'href' => $url
	];

	if ($has_field_link_new_tab !== 'no') {
		$link_attr['target'] = '_blank';
	}

	if (! empty($has_field_link_rel)) {
		$link_attr['rel'] = $has_field_link_rel;
	}
}

if (empty($value)) {
	return;
}

if (! empty($attributes['width'])) {
	$styles[] = 'width: ' . $attributes['width'] . ';';
}

if (! empty($attributes['height'])) {
	$styles[] = 'height: ' . $attributes['height'] . ';';
}

if (! empty($attributes['imageAlign'])) {
	$classes[] = 'align' . $attributes['imageAlign'];
}

if (
	$video_thumbnail === 'yes'
	&&
	$maybe_video
) {
	$wrapper_attr['data-media-id'] = $attachment_id;

	$value .= $maybe_video['icon'];

	if (blocksy_akg('media_video_player', $maybe_video, 'no') === 'yes') {
		$classes[] = 'ct-simplified-player';
	}

	$new_default_based_on_old_value = blocksy_akg(
		'media_video_autoplay',
		$maybe_video,
		'no'
	) === 'yes' ? 'autoplay' : 'click';

	if (
		blocksy_akg(
			'media_video_event',
			$maybe_video,
			$new_default_based_on_old_value
		) === 'autoplay'
	) {
		$wrapper_attr['data-state'] = 'autoplay';
	}
}

$wrapper_attr['class'] .= ' ' . implode(' ', $classes);

$wrapper_attr['class'] = trim($wrapper_attr['class']);

$wrapper_attr['style'] = implode(' ', $styles);

if (
	$image_hover_effect !== 'none'
	||
	$maybe_video
) {
	$span_styles = [];
	$span_classes = ['ct-dynamic-media-inner'];

	if (! empty($border_result['style'])) {
		$span_styles[] = $border_result['style'];
	}

	if (! empty($border_result['class'])) {
		$span_classes[] = $border_result['class'];
	}

	$value = blocksy_html_tag(
		'span',
		[
			'data-hover' => $image_hover_effect,
			'class' => implode(' ', $span_classes),
			'style' => implode(' ', $span_styles)
		],
		$value
	);
}

$tag_name = 'figure';

if (! empty($link_attr)) {
	$tag_name = 'a';
	$wrapper_attr = array_merge(
		$wrapper_attr,
		$link_attr
	);
}

$wrapper_attr = get_block_wrapper_attributes($wrapper_attr);

if (
	$lightbox === 'yes'
	&&
	function_exists('block_core_image_render_lightbox')
	&&
	$has_field_link !== 'yes'
	&&
	$video_thumbnail !== 'yes'
	&&
	!$maybe_video
) {
	$lightbox_block = [
		'blockName' => 'core/image',
		'attrs' => [
			'id' => $attachment_id,
			'linkDestination' => 'none',
		],
		'innerBlocks' => [],
		'innerHTML' => '',
		'innerContent' => [],
	];

	$lightbox_block_instance = null;

	if (class_exists('WP_Block')) {
		$lightbox_block_instance = new WP_Block($lightbox_block);
	}

	// Match core/image asset loading for lightbox behavior.
	if (function_exists('wp_enqueue_script_module')) {
		wp_enqueue_script_module('@wordpress/block-library/image/view');
	}

	wp_enqueue_style('wp-block-image');

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo block_core_image_render_lightbox(
		blocksy_html_tag($tag_name, $wrapper_attr, $value),
		$lightbox_block,
		$lightbox_block_instance
	);

	return;
}

blocksy_html_tag_e($tag_name, $wrapper_attr, $value);
