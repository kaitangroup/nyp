<?php

if (! defined('ABSPATH')) {
	exit;
}

$brands = get_the_terms(get_the_ID(), 'product_brand');

if (
	! $brands
	||
	!is_array($brands)
) {
    return;
}

if (! count($brands)) {
	return;
}

blocksy_html_tag_e(
	'div',
	[
		'class' => 'ct-product-brands',
		'style' => '--product-brand-logo-size:' . $attributes['brands_size'] . 'px;--product-brands-gap:' . $attributes['brands_gap'] . 'px;'
	],
	join('', array_map(
		function ($brand) {
			$output = '';

			$label = blocksy_html_tag(
				'a',
				[
					'href' => esc_url(get_term_link($brand)),
				],
				$brand->name
			);

			$term_atts = get_term_meta(
				$brand->term_id,
				'blocksy_taxonomy_meta_options'
			);

			if (empty($term_atts)) {
				$term_atts = [[]];
			}

			$term_atts = $term_atts[0];

			$maybe_image_id = isset($brand->term_id) ? get_term_meta($brand->term_id, 'thumbnail_id', true) : '';

			if (! empty($maybe_image_id)) {
				$term_atts['icon_image'] = [
					'attachment_id' => $maybe_image_id,
					'url' => wp_get_attachment_image_url($maybe_image_id, 'full')
				];
			}

			$maybe_image = blocksy_akg('icon_image', $term_atts, '');

			if (
				$maybe_image
				&&
				is_array($maybe_image)
				&&
				isset($maybe_image['attachment_id'])
			) {
				$attachment_id = $maybe_image['attachment_id'];

				$label = blocksy_media([
					'attachment_id' => $maybe_image['attachment_id'],
					'size' => 'medium',
					'ratio' => 'original',
					'tag_name' => 'a',
					'html_atts' => [
						'href' => get_term_link($brand),
						'aria-label' => $brand->name
					]
				]);
			}

			$output .= $label;

			return $output;
		},
		$brands
	))
);
