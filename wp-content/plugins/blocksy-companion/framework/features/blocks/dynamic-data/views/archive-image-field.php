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
	$term_id = get_queried_object_id();
}

if (! $term_id && function_exists('is_shop') && is_shop()) {
	$post_id = get_option('woocommerce_shop_page_id');
	$attachment_id = get_post_thumbnail_id($post_id);
}

if (! $term_id && is_home() && ! is_front_page()) {
	$post_id = get_option('page_for_posts');
	$attachment_id = get_post_thumbnail_id($post_id);
}

if ($term_id) {
	$id = get_term_meta($term_id, 'thumbnail_id');

	if ($id && !empty($id)) {
		$attachment_id = $id[0];
	}

	if (! $id) {
		$attachment_id = null;
	}

	$term_atts = get_term_meta(
		$term_id,
		'blocksy_taxonomy_meta_options'
	);

	if (empty($term_atts)) {
		$term_atts = [[]];
	}

	$maybe_image = blocksy_akg('image', $term_atts[0], '');

	if ($image_source === 'icon') {
		$maybe_image = blocksy_akg('icon_image', $term_atts[0], '');
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

blocksy_render_view_e(
	dirname(__FILE__) . '/image-field.php',
	[
		'attributes' => $attributes,
		'field' => $field,
		'content' => $content,
		'attachment_id' => $attachment_id,
		'url' => get_term_link($term_id)
	]
);
