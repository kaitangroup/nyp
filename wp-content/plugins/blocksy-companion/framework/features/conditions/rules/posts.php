<?php

if (! defined('ABSPATH')) {
	exit;
}

$options = [];

$posts_options = [];

if ($filter === 'all' || $filter === 'archive') {
	$posts_options[] = [
		'id' => 'all_post_archives',
		'title' => __('Post Archives', 'blocksy-companion')
	];

	$posts_options[] = [
		'id' => 'post_categories',
		'title' => __('Post Categories', 'blocksy-companion')
	];

	$posts_options[] = [
		'id' => 'post_tags',
		'title' => __('Post Tags', 'blocksy-companion')
	];

	$taxonomies = get_object_taxonomies('post');
	$taxonomies = array_diff($taxonomies, ['category', 'post_tag', 'post_format']);

	foreach ($taxonomies as $single_taxonomy) {
		$taxonomy = get_taxonomy($single_taxonomy);

		if (!$taxonomy) {
			continue;
		}

		if (!$taxonomy->publicly_queryable) {
			continue;
		}

		$posts_options[] = [
			'id' => 'post_type_post_taxonomy_' . $single_taxonomy,
			'title' => blocksy_companion_safe_sprintf(
				// translators: %1$s is the label of the taxonomy.
				__('Post %1$s', 'blocksy-companion'),
				$taxonomy->label
			)
		];
	}
}

if ($filter === 'all' || $filter === 'singular') {
	$posts_options[] = [
		'id' => 'single_post',
		'title' => __('Single Post', 'blocksy-companion')
	];

}

$options[] = [
	'title' => __('Posts', 'blocksy-companion'),
	'rules' => $posts_options
];
