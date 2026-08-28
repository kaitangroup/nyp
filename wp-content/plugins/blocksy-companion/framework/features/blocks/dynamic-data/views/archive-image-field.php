<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! isset($term_id)) {
	$term_id = null;
}

$image_source = blocksy_akg('imageSource', $attributes, 'featured');
$attachment_id = null;

if (! $term_id && is_archive()) {
	$queried_object = get_queried_object();

	if (is_a($queried_object, 'WP_Term')) {
		$term_id = $queried_object->term_id;
	}
}

$special_post_id = blocksy_companion_theme_functions()->blocksy_get_special_post_id();

if ($special_post_id) {
	$attachment_id = get_post_thumbnail_id($special_post_id);
}

if (is_search()) {
	$term_id = null;
	$attachment_id = null;
}

if ($term_id) {
	$id = get_term_meta($term_id, 'thumbnail_id');

	if ($id && !empty($id)) {
		$attachment_id = $id[0];
	}

	if (! $id) {
		$attachment_id = null;
	}

	$term_atts = blocksy_companion_theme_functions()->blocksy_get_taxonomy_options($term_id);

	if (empty($term_atts)) {
		$term_atts = [];
	}

	$maybe_image = blocksy_akg('image', $term_atts, '');

	if ($image_source === 'icon') {
		$maybe_image = blocksy_akg('icon_image', $term_atts, '');
	}

	if (
		$maybe_image
		&&
		is_array($maybe_image)
		&&
		isset($maybe_image['attachment_id'])
	) {
		$attachment_id = $maybe_image['attachment_id'];
	}
}

blocksy_companion_render_view_e(
	dirname(__FILE__) . '/image-field.php',
	[
		'attributes' => $attributes,
		'field' => $field,
		'content' => $content,
		'attachment_id' => $attachment_id,
		'url' => get_term_link($term_id)
	]
);
