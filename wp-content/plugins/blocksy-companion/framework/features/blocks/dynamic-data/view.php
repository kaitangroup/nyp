<?php

if (! defined('ABSPATH')) {
	exit;
}

$field = blocksy_akg('field', $attributes, 'wp:title');

if (strpos($field, 'woo:') === 0) {
	blocksy_render_view_e(
		dirname(__FILE__) . '/views/woo-field.php',
		[
			'attributes' => $attributes,
			'field' => $field
		]
	);

	return;
}

if (strpos($field, 'wp:') === 0) {
	if (
		$field !== 'wp:featured_image'
		&&
		$field !== 'wp:author_avatar'
		&&
		$field !== 'wp:archive_image'
	) {
		blocksy_render_view_e(
			dirname(__FILE__) . '/views/wp-field.php',
			[
				'attributes' => $attributes,
				'field' => $field,
				'block' => $block
			]
		);
	}

	if ($field === 'wp:term_image') {
		global $blocksy_term_obj;

		if (isset($blocksy_term_obj)) {
			blocksy_render_view_e(
				dirname(__FILE__) . '/views/archive-image-field.php',
				[
					'attributes' => $attributes,
					'field' => $field,
					'content' => $content,
					'term_id' => $blocksy_term_obj->term_id,
				]
			);
		}
	}

	if ($field === 'wp:archive_image') {
		blocksy_render_view_e(
			dirname(__FILE__) . '/views/archive-image-field.php',
			[
				'attributes' => $attributes,
				'field' => $field,
				'content' => $content
			]
		);
	}

	if ($field === 'wp:featured_image') {
		blocksy_render_view_e(
			dirname(__FILE__) . '/views/image-field.php',
			[
				'attributes' => $attributes,
				'field' => $field,
				'content' => $content,
				'attachment_id' => get_post_thumbnail_id(),
				'url' => get_permalink()
			]
		);
	}

	if ($field === 'wp:author_avatar') {
		blocksy_render_view_e(
			dirname(__FILE__) . '/views/avatar-field.php',
			[
				'attributes' => $attributes,
				'field' => $field
			]
		);
	}

	return;
}

if (! function_exists('blocksy_companion_get_ext')) {
	return;
}

if (
	! blocksy_companion_get_ext('post-types-extra')
	||
	! blocksy_companion_get_ext('post-types-extra')->dynamic_data
) {
	return;
}

$field_descriptor = explode(':', $field);

if (count($field_descriptor) < 2) {
	return;
}

$field_render = blocksy_companion_get_ext('post-types-extra')
	->dynamic_data
	->custom_fields_manager
	->render_field(
		$field_descriptor[1],
		[
			'provider' => $field_descriptor[0],
			'allow_images' => true
		]
	);

if (! $field_render) {
	return;
}

if ($field_render['type'] === \Blocksy\Extensions\PostTypesExtra\CustomField::$TYPE_IMAGE) {
	if (isset($field_render['value']['id'])) {
		blocksy_render_view_e(
			dirname(__FILE__) . '/views/image-field.php',
			[
				'attributes' => $attributes,
				'field' => $field,
				'attachment_id' => $field_render['value']['id']
			]
		);
	}

	return;
}

blocksy_render_view_e(
	dirname(__FILE__) . '/views/custom-text-field.php',
	[
		'attributes' => $attributes,
		'value' => $field_render['value']
	]
);
