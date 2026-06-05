<?php

if (! defined('ABSPATH')) {
	exit;
}

$value_fallback = blocksy_akg('fallback', $attributes, '');

$value = '';

$has_fallback = false;

$has_field_link = blocksy_akg('has_field_link', $attributes, 'no') === 'yes';
$has_field_link_wrap_content = blocksy_akg('has_field_link_wrap_content', $attributes, 'no');

$has_archive_prefix = blocksy_akg('has_archive_prefix', $attributes, 'no') === 'yes';

if ($field === 'wp:archive_title') {
	$archive_title_renderer = new \Blocksy\ArchiveTitleRenderer([
		'has_label' => $has_archive_prefix
	]);

	$value = get_the_archive_title();

	if (method_exists($archive_title_renderer, 'get_the_archive_title')) {
		$value = $archive_title_renderer->get_the_archive_title();
	}

	if (is_home() && !is_front_page()) {
		$post_id = get_option('page_for_posts');

		if ($post_id) {
			$value = get_the_title($post_id);
		}
	}

	if (is_search()) {
		$value = blocksy_safe_sprintf(
			// translators: 1: the search query
			__(
				'Search Results for %1$s',
				'blocksy-companion'
			),
			get_search_query()
		);
	}
}

if ($field === 'wp:archive_description') {
	$value = get_the_archive_description();

	$is_page = blocksy_is_page();

	if (
		function_exists('is_woocommerce')
		&&
		is_shop()
		&&
		$is_page
	) {
		$value = blocksy_entry_excerpt([
			'length' => PHP_INT_MAX,
			'post_id' => $is_page
		]);
	}
}

$link_attr = [];

if ($field === 'wp:title') {
	$value = get_the_title();

	if ($has_field_link) {
		$link_attr = [
			'href' => get_permalink()
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
}

if ($field === 'wp:term_title') {
	global $blocksy_term_obj;

	if (! empty($blocksy_term_obj)) {
		$value = $blocksy_term_obj->name;

		if ($has_field_link) {
			$link_attr = [
				'href' => get_term_link($blocksy_term_obj)
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
	}
}

if ($field === 'wp:term_count') {
	global $blocksy_term_obj;

	if (! empty($blocksy_term_obj)) {
		$value = "{$blocksy_term_obj->count}";

		if ($has_field_link) {
			$link_attr = [
				'href' => get_term_link($blocksy_term_obj)
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
	}
}

if ($field === 'wp:term_description') {
	global $blocksy_term_obj;

	if (! empty($blocksy_term_obj)) {
		$value = $blocksy_term_obj->description;
	}
}

if ($field === 'wp:excerpt') {
	if (blocksy_akg('tagName', $attributes, 'div') === 'p') {
		remove_filter('the_excerpt', 'wpautop');
	}
	$value = blocksy_entry_excerpt([
		'length' => intval(blocksy_akg('excerpt_length', $attributes, 40)),
		'skip_container' => true
	]);

	if (empty($value) && ! empty($value_fallback)) {
		$has_fallback = true;
		$value = do_shortcode($value_fallback);
	}
	if (blocksy_akg('tagName', $attributes, 'div') === 'p') {
		add_filter('the_excerpt', 'wpautop');
	}
}

if ($field === 'wp:date') {
	$date_format = get_option('date_format', 'F j, Y');

	if (blocksy_akg('default_format', $attributes, 'published') === 'no') {
		$date_format = blocksy_akg('date_format', $attributes, 'F j, Y');

		if ($date_format === 'custom') {
			$date_format = blocksy_akg('custom_date_format', $attributes, 'F j, Y');
		}
	}

	$value = get_the_date($date_format);

	if (blocksy_akg('date_type', $attributes, 'published') === 'modified') {
		$value = get_the_modified_date($date_format);
	}

	if ($has_field_link) {
		$link_attr = [
			'href' => get_permalink()
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
}

if ($field === 'wp:comments') {
	$value = get_comments_number_text(
		blocksy_akg('zero_text', $attributes, __('No comments', 'blocksy-companion')),
		blocksy_akg('single_text', $attributes, __('One comment', 'blocksy-companion')),
		blocksy_akg('multiple_text', $attributes, __('% comments', 'blocksy-companion'))
	);

	if ($has_field_link) {
		$value = blocksy_html_tag(
			'a',
			array_merge(
				[
					'href' => get_comments_link()
				],

				blocksy_akg('has_field_link_new_tab', $attributes, 'no') === 'yes' ?
				[
					'target' => '_blank'
				] : [],

				! empty(blocksy_akg('has_field_link_rel', $attributes, '')) ? [
					'rel' => blocksy_akg('has_field_link_rel', $attributes, '')
				] : []
			),
			$value
		);
	}
}

if ($field === 'wp:author') {
	$author_id = blocksy_get_author_id();
	$author_field = blocksy_akg('author_field', $attributes, 'display_name');

	$overide_link = '';

	if ($author_field === 'email') {
		$value = blocksy_get_the_author_meta('user_email', $author_id);

		if (! empty($value)) {
			$overide_link = 'mailto:' . $value;
		}
	}

	if ($author_field === 'user_url') {
		$value = blocksy_get_the_author_meta('user_url', $author_id);

		if (! empty($value)) {
			$overide_link = $value;
		}
	}

	if ($author_field === 'nicename') {
		$value = blocksy_get_the_author_meta('nickname', $author_id);
	}

	if ($author_field === 'display_name') {
		$value = blocksy_get_the_author_meta('display_name', $author_id);
	}

	if ($author_field === 'first_name') {
		$value = blocksy_get_the_author_meta('first_name', $author_id);
	}

	if ($author_field === 'last_name') {
		$value = blocksy_get_the_author_meta('last_name', $author_id);
	}

	if ($author_field === 'description') {
		$value = blocksy_get_the_author_meta('description', $author_id);
	}

	if (empty($value) && ! empty($value_fallback)) {
		$has_fallback = true;
		$value = do_shortcode($value_fallback);
	}

	if (
		! empty($value)
		&&
		$has_field_link
	) {
		$value = blocksy_html_tag(
			'a',
			array_merge(
				[
					'href' => ! empty($overide_link) ? $overide_link : get_author_posts_url($author_id)
				],

				blocksy_akg('has_field_link_new_tab', $attributes, 'no') === 'yes' ? [
					'target' => '_blank'
				] : [],

				! empty(blocksy_akg('has_field_link_rel', $attributes, '')) ? [
					'rel' => blocksy_akg('has_field_link_rel', $attributes, '')
				] : []
			),
			$value
		);
	}
}

if ($field === 'wp:terms') {
	$taxonomy = blocksy_akg('taxonomy', $attributes, '');

	if (empty($taxonomy)) {
		$internal_taxonomies = get_object_taxonomies([
			'post_type' => get_post_type(),
			'public' => true,
			'show_in_nav_menus' => true,
		]);

		$taxonomies = [];

		foreach ($internal_taxonomies as $tax) {
			$taxonomy_object = get_taxonomy($tax);

			if (! $taxonomy_object->public) {
				continue;
			}

			$taxonomies[] = $tax;
		}

		if (! empty($taxonomies)) {
			$taxonomy = $taxonomies[0];
		}
	}

	$value = '';

	if (! empty($taxonomy)) {
		$terms = get_the_terms(get_the_ID(), $taxonomy);

		if (
			! empty($terms)
			&&
			! is_wp_error($terms)
		) {
			$terms = array_map(function ($term) use ($taxonomy, $attributes, $has_field_link) {
				$tagName = 'span';

				$attrs = [];

				$classes = [];

				$termAccentColor = blocksy_akg('termAccentColor', $attributes, 'yes');

				if ($termAccentColor === 'yes') {
					$classes[] = 'ct-term-' . $term->term_id;
				}

				$termClass = blocksy_akg('termClass', $attributes, '');

				if (! empty($termClass)) {
					$classes[] = $termClass;
				}

				if (! empty($classes)) {
					$attrs['class'] = implode(' ', $classes);
				}

				if ($has_field_link) {
					$tagName = 'a';

					$attrs['href'] = get_term_link($term, $taxonomy);

					if (blocksy_akg('has_field_link_new_tab', $attributes, 'no') === 'yes') {
						$attrs['target'] = '_blank';
					}

					if (! empty(blocksy_akg('has_field_link_rel', $attributes, ''))) {
						$attrs['rel'] = blocksy_akg('has_field_link_rel', $attributes, '');
					}
				}

				return '<' . $tagName . ' ' . trim(blocksy_attr_to_html($attrs)) . '>' . $term->name . '</' . $tagName . '>';
			}, $terms);

			$value = implode(
				preg_replace('/ /', "\u{00A0}", blocksy_akg('separator', $attributes, ', ')),
				$terms
			);
		}
	}

	if (empty($value) && ! empty($value_fallback)) {
		$has_fallback = true;
		$value = do_shortcode($value_fallback);
	}
}

if (
	empty(trim($value))
	&&
	trim($value) !== '0'
) {
	return;
}

$value_after = blocksy_akg('after', $attributes, '');
$value_before = blocksy_akg('before', $attributes, '');

if (! empty($value_after) && ! $has_fallback) {
	$value .= $value_after;
}

if (! empty($value_before) && ! $has_fallback) {
	$value = $value_before . $value;
}

$tagName = blocksy_akg('tagName', $attributes, 'div');

$classes = [
	'ct-dynamic-data'
];

if (! empty($attributes['align'])) {
	$classes[] = 'has-text-align-' . $attributes['align'];
}

$wrapper_attr['class'] = implode(' ', $classes);
$wrapper_attr['style'] = '';

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

if (! empty($border_result['class'])) {
	$wrapper_attr['class'] .= ' ' . $border_result['class'];
}

if (! empty($border_result['style'])) {
	$wrapper_attr['style'] .= $border_result['style'];
}

$block_type = WP_Block_Type_Registry::get_instance()->get_registered('blocksy/dynamic-data');
$block_type->supports['color'] = true;
wp_apply_colors_support($block_type, $attributes);

if (
	$has_field_link_wrap_content === 'yes'
	&&
	$has_field_link
) {
	$value = blocksy_html_tag('a', $link_attr, $value);
}

$wrapper_attr = get_block_wrapper_attributes($wrapper_attr);

blocksy_html_tag_e($tagName, $wrapper_attr, $value);
