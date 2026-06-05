<?php

function blocksy_isolated_get_search_form($args) {
	if (class_exists('IS_Admin_Public')) {
		remove_filter(
			'get_search_form',
			[\IS_Admin_Public::getInstance(), 'get_search_form'],
			9999999
		);
	}

	get_search_form($args);

	if (class_exists('IS_Admin_Public')) {
		add_filter(
			'get_search_form',
			[\IS_Admin_Public::getInstance(), 'get_search_form'],
			9999999
		);
	}
}

function blocksy_reqursive_taxonomy($tax, $parent_term_id, $level, $selected_cat) {
	if (! $parent_term_id) {
		return [];
	}

	$terms = get_terms([
		'taxonomy' => $tax,
		'hide_empty' => true,
		'hierarchical' => false,
		'parent' => $parent_term_id,
	]);

	if (!count($terms)) {
		return [];
	}

	$els = [];

	foreach ($terms as $term) {
		$selected_attr = $selected_cat == $term->term_id ? 'selected' : '';

		$prefix = '&nbsp;&nbsp;&nbsp;';

		for ($i=0; $i < $level; $i++) {
			$prefix .= '&nbsp;&nbsp;&nbsp;';
		}

		$els[] = blocksy_html_tag(
			'option',
			[
				'value' => $tax . ':' . $term->term_id,
				$selected_attr => $selected_attr
			],
			$prefix . $term->name
		);

		$children = get_terms([
			'taxonomy' => $tax,
			'hide_empty' => true,
			'hierarchical' => false,
			'parent' => $term->term_id,
		]);

		if (count($children)) {
			$els = array_merge(
				$els,
				blocksy_reqursive_taxonomy(
					$tax,
					$term->term_id,
					$level + 1,
					$selected_cat
				)
			);
		}
	}

	return $els;
}

function blocksy_get_search_post_type($search_through = []) {
	$all_cpts = blocksy_manager()->post_types->get_supported_post_types();

	if (function_exists('is_bbpress')) {
		$all_cpts[] = 'forum';
		$all_cpts[] = 'topic';
		$all_cpts[] = 'reply';
	}

	if (class_exists('Tribe__Events__Main')) {
		$all_cpts[] = 'tribe_events';
	}

	foreach ($all_cpts as $single_cpt) {
		if (! isset($search_through[$single_cpt])) {
			$search_through[$single_cpt] = false;
		}
	}

	$post_type = [];

	foreach ($search_through as $single_post_type => $enabled) {
		if (
			! $enabled
			||
			! get_post_type_object($single_post_type)
		) {
			continue;
		}

		if (
			$single_post_type !== 'post'
			&&
			$single_post_type !== 'page'
			&&
			$single_post_type !== 'product'
			&&
			! in_array($single_post_type, $all_cpts)
		) {
			continue;
		}

		$post_type[] = $single_post_type;
	}

	// All subtypes used in the REST API Post Search Handler.
	// wp-includes/rest-api/search/class-wp-rest-post-search-handler.php
	$rest_api_all_subtypes = array_diff(
		array_values(
			get_post_types(
				[
					'public' => true,
					'show_in_rest' => true
				],
				'names'
			)
		),
		['attachment']
	);

	if (
		count(array_keys($search_through)) === count($post_type)
		&&
		count($post_type) === count($rest_api_all_subtypes)
	) {
		$post_type = [];
	}

	return $post_type;
}