<?php

namespace Blocksy\Editor\Blocks;

if (! defined('ABSPATH')) {
	exit;
}

class BreadCrumbs {
	public function __construct() {
		add_action('init', [$this, 'blocksy_breadcrumbs_block']);

		add_action(
			'wp_ajax_blocksy_blocks_retrieve_breadcrumbs_data_descriptor',
			function () {
				$blocksy_manager = blocksy_companion_theme_functions()->blocksy_manager();

				if (
					! current_user_can('manage_options')
					||
					! $blocksy_manager
				) {
					wp_send_json_error();
				}

				$data = json_decode(file_get_contents('php://input'), true);

				if (! isset($data['post_id'])) {
					wp_send_json_success(apply_filters(
						'blocksy:general:blocks:breadcrumbs:data',
						[
							'post_id' => null,
							'post_type' => 'post',
						]
					));
				}

				if (! $data || ! isset($data['post_id'])) {
					wp_send_json_error();
				}

				global $post, $wp_query;
				$post_id = intval($data['post_id']);
				$post = $post_id ? get_post($post_id) : null;

				if (
					isset($data['taxonomyTerms'])
					&& is_array($data['taxonomyTerms'])
					&& ! empty($data['taxonomyTerms'])
				) {
					add_filter(
						'wp_get_object_terms',
						function ($terms, $object_ids, $taxonomies) use ($data) {
							$terms = [];

							foreach ($data['taxonomyTerms'] as $slug => $values) {
								foreach ($values as $category_id) {
									$term = get_term($category_id, $slug);

									if (! $term || is_wp_error($term)) {
										continue;
									}

									$terms[] = $term;
								}
							}

							return $terms;
						},
						10,
						3
					);
				}

				if (! $post || is_wp_error($post)) {
					wp_send_json_error();
				}

				$wp_query->is_single = true;
				$wp_query->is_singular = true;

				if ($post->post_type === 'product') {
					$wp_query->is_product = true;
					add_filter('is_woocommerce', '__return_true');

					global $product;

					$product = wc_get_product($post_id);
				}

				$post->post_title = '__BLOCKSY_BREADCRUMBS_POST_TITLE__';
				add_filter(
					'the_title',
					function ($post_title, $post_id) {
						if ($GLOBALS['post']->ID !== $post_id) {
							return $post_title;
						}

						return $GLOBALS['post']->post_title;
					},
					10,
					2
				);
				
				$breadcrumbs_builder = new \Blocksy\BreadcrumbsBuilder([
					'is_frontend' => false,
				]);

				wp_send_json_success(apply_filters(
					'blocksy:general:blocks:breadcrumbs:data',
					[
						'post_id' => $post_id,
						'post_type' => $post->post_type,
						'render' => $breadcrumbs_builder->render(
							[
								'class' => 'ct-breadcrumbs-block',
							]
						),
					]
				));
			}
		);
	}

	public function blocksy_breadcrumbs_block() {
		$block_data = [
			'api_version' => 3,
			'render_callback' => function ($attributes, $content) {
				$attributes = wp_parse_args(
					$attributes,
					[
						'className' => '',
						'style' => []
					]
				);

				$colors = isset($attributes['style']['color']) ? $attributes['style']['color'] : [];

				if (isset($attributes['linkColor'])) {
					$var = $attributes['linkColor'];
					$colors['--theme-link-initial-color'] = "var(--wp--preset--color--$var)";
				}

				if (isset($attributes['customLinkColor'])) {
					$colors['--theme-link-initial-color'] = $attributes['customLinkColor'];
				}

				if (isset($attributes['textColor'])) {
					$var = $attributes['textColor'];
					$colors['--theme-text-color'] = "var(--wp--preset--color--$var)";
				}

				if (isset($attributes['customTextColor'])) {
					$colors['--theme-text-color'] = $attributes['customTextColor'];
				}

				if (isset($attributes['linkHoverColor'])) {
					$var = $attributes['linkHoverColor'];
					$colors['--theme-link-hover-color'] = "var(--wp--preset--color--$var)";
				}

				if (isset($attributes['customLinkHoverColor'])) {
					$colors['--theme-link-hover-color'] = $attributes['customLinkHoverColor'];
				}

				$colors_css = '';

				foreach ($colors as $key => $value) {
					if (empty($value)) {
						continue;
					}

					$colors_css .= $key . ':' . $value . ';';
				}

				$breadcrumbs_builder = new \Blocksy\BreadcrumbsBuilder();

				$wp_styles = wp_style_engine_get_styles(
					$attributes['style']
				);

				$wp_styles_css = isset($wp_styles['css']) ? $wp_styles['css'] : '';

				return $breadcrumbs_builder->render(
					array_merge(
						[
							'class' => $attributes['className'],
						],
						! empty($wp_styles_css) || ! empty($colors_css) ? [
							'style' => $wp_styles_css . $colors_css
						] : []
					)
				);
			}
		];

		register_block_type('blocksy/breadcrumbs', $block_data);
	}
}
