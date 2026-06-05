<?php

/**
 * Search form
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

$placeholder = esc_attr_x('Search', 'placeholder', 'blocksy');

if (isset($args['search_placeholder'])) {
	$placeholder = $args['search_placeholder'];
}

if (isset($args['ct_product_price'])) {
	$show_product_price = $args['ct_product_price'];
} else {
	$show_product_price = blocksy_get_theme_mod('searchProductPrice', 'no') === 'yes';
}

if (isset($args['search_through_taxonomy'])) {
	$search_through_taxonomy = $args['search_through_taxonomy'] === 'yes';
} else {
	$search_through_taxonomy = false;
}

if (isset($args['ct_product_status'])) {
	$show_product_status = $args['ct_product_status'];
} else {
	$show_product_status = blocksy_get_theme_mod('searchProductPrice', 'no') === 'yes';
}

if (isset($args['search_live_results'])) {
	$has_live_results = $args['search_live_results'];
} else {
	$has_live_results = blocksy_get_theme_mod('search_enable_live_results', 'yes');
}

$search_live_results_output = '';

if ($has_live_results === 'yes') {
	if (! isset($args['live_results_attr'])) {
		$args['live_results_attr'] = 'thumbs';
	}

	$live_results_attr = ! empty($args['live_results_attr']) ? [$args['live_results_attr']] : [];

	if ($show_product_price) {
		array_push($live_results_attr, 'product_price');
	}

	if ($show_product_status) {
		array_push($live_results_attr, 'product_status');
	}

	$search_live_results_output = 'data-live-results="' . implode(':', $live_results_attr) . '"';
}

$class_output = '';

if (
	isset($args['enable_search_field_class'])
	&&
	$args['enable_search_field_class']
) {
	$class_output = 'class="modal-field"';
}

$home_url = apply_filters(
	'blocksy:search-form:home-url',
	home_url('/')
);

$icon = apply_filters(
	'blocksy:search-form:icon',
	'<svg class="ct-icon ct-search-button-content" aria-hidden="true" width="15" height="15" viewBox="0 0 15 15"><path d="M14.8,13.7L12,11c0.9-1.2,1.5-2.6,1.5-4.2c0-3.7-3-6.8-6.8-6.8S0,3,0,6.8s3,6.8,6.8,6.8c1.6,0,3.1-0.6,4.2-1.5l2.8,2.8c0.1,0.1,0.3,0.2,0.5,0.2s0.4-0.1,0.5-0.2C15.1,14.5,15.1,14,14.8,13.7z M1.5,6.8c0-2.9,2.4-5.2,5.2-5.2S12,3.9,12,6.8S9.6,12,6.8,12S1.5,9.6,1.5,6.8z"/></svg>'
);

if (isset($args['icon'])) {
	$icon = $args['icon'];
}

$allowed_post_types = [];

if (get_query_var('post_type') && ! is_array(get_query_var('post_type'))) {
	$allowed_post_types = explode(':', get_query_var('post_type'));
}

if (
	isset($_GET['ct_post_type'])
	&&
	$_GET['ct_post_type']
) {
	$allowed_post_types = explode(':', $_GET['ct_post_type']);
}

if (isset($args['ct_post_type'])) {
	$allowed_post_types = $args['ct_post_type'];
}

$has_taxonomy_filter = false;

if (isset($args['has_taxonomy_filter'])) {
	$selected_cat = 0;

	if (isset($_GET['ct_tax_query']) && ! empty($_GET['ct_tax_query'])) {
		$tax_params = explode(':', $_GET['ct_tax_query']);
		$selected_cat = $tax_params[1];
	}

	$has_taxonomy_filter = $args['has_taxonomy_filter'];
}

if ($has_taxonomy_filter) {
	$options = [];

	$options[] = blocksy_html_tag(
		'option',
		[
			'value' => '',
		],
		blocksy_akg('taxonomy_filter_label', $args, __('Select Category', 'blocksy'))
	);

	$skip_tax = [
		'product_brands',
		'product_brand'
	];

	$taxonomy_filter_visibility = ' ' . blocksy_visibility_classes(
		blocksy_akg(
			'taxonomy_filter_visibility',
			$args,
			[
				'desktop' => true,
				'tablet' => true,
				'mobile' => false,
			]
		)
	);

	if (empty($allowed_post_types)) {
		$allowed_post_types[] = 'post';
		$allowed_post_types[] = 'page';

		if (class_exists('WooCommerce')) {
			$allowed_post_types[] = 'product';
		}

		$all_cpts = blocksy_manager()->post_types->get_supported_post_types();

		if (function_exists('is_bbpress')) {
			$allowed_post_types[] = 'forum';
			$allowed_post_types[] = 'topic';
			$allowed_post_types[] = 'reply';
		}

		if (class_exists('Tribe__Events__Main')) {
			$allowed_post_types[] = 'tribe_events';
		}

		foreach ($all_cpts as $single_cpt) {
			$allowed_post_types[] = $single_cpt;
		}
	}

	foreach ($allowed_post_types as $post_type) {
		$terms_els = [];

		$taxonomy_names = get_object_taxonomies($post_type);

		if ( ! count($taxonomy_names) ) {
			continue;
		}

		$has_taxonomy_children = blocksy_akg('has_taxonomy_children', $args, true);

		foreach ($taxonomy_names as $tax) {
			if (
				in_array($tax, $skip_tax)
				||
				! is_taxonomy_hierarchical($tax)
			) {
				continue;
			}

			$terms_args = [
				'taxonomy' => $tax,
				'hide_empty' => true,
				'hierarchical' => false,
				'parent' => 0
			];

			$terms = get_terms($terms_args);

			if ( ! count($terms) ) {
				continue;
			}

			foreach ($terms as $term) {
				$terms_els[] = blocksy_html_tag(
					'option',
					array_merge(
						[
							'value' => $tax . ':' . $term->term_id,
						],
						$selected_cat == $term->term_id ? ['selected' => 'selected'] : []
					),
					$term->name
				);

				if ($has_taxonomy_children) {
					$terms_els = array_merge(
						$terms_els,
						blocksy_reqursive_taxonomy($tax, $term->term_id, 0, $selected_cat)
					);
				}
			}
		}

		if ( ! empty($terms_els) ) {
			if (count($allowed_post_types) === 1) {
				$options[] = join('', $terms_els);
			} else {
				$options[] = blocksy_html_tag(
					'optgroup',
					[
						'value' => $post_type,
						'label' => get_post_type_object($post_type)->labels->name
					],
					join('', $terms_els)
				);
			}
		}
	}
}

$html_atts = [
	'data-form-controls' => 'inside',
	'data-taxonomy-filter' => 'false',
	'data-submit-button' => 'icon'
];

if (isset($args['html_atts'])) {
	$html_atts = array_merge(
		$html_atts,
		$args['html_atts']
	);
}

if (isset($args['override_html_atts'])) {
	$html_atts = $args['override_html_atts'];
}

if (
	! isset($args['override_html_atts'])
	&&
	($html_atts['data-form-controls'] ?? 'inside') === 'inside'
	&&
	($html_atts['data-submit-button'] ?? 'icon') === 'icon'
) {
	$html_atts['data-submit-button'] = 'minimal:icon';
}

$button_html_atts = array_merge(
	[
		'aria-label' => __('Search button', 'blocksy')
	],
	isset($args['button_html_atts']) ? $args['button_html_atts'] : []
);

$search_box_min_length = apply_filters('blocksy:search-form:min-length', 1);
$results_id = '';
$status_id = '';

if ($has_live_results === 'yes') {
	$uid = substr(blocksy_rand_md5(), 0, 3);
	$results_id = 'ct-search-results-' . $uid;
	$status_id = 'ct-search-status-' . $uid;
}

$has_visible_taxonomy_filter = $has_taxonomy_filter && count($options) > 1;
$form_controls_type = isset($html_atts['data-form-controls']) ? $html_atts['data-form-controls'] : 'inside';
$inner_wrapper_atts = [];
$inner_wrapper_classes = ['ct-search-form-inner'];

if ($form_controls_type === 'inside') {
	$inner_wrapper_classes[] = 'ct-pseudo-input';
}

$inner_wrapper_class = implode(' ', $inner_wrapper_classes);

?>


<form role="search" method="get" class="ct-search-form" <?php echo blocksy_attr_to_html($html_atts); ?> action="<?php echo esc_url($home_url); ?>" aria-haspopup="listbox" <?php echo wp_kses_post($search_live_results_output) ?>>

	<div class="<?php echo esc_attr($inner_wrapper_class); ?>" <?php echo blocksy_attr_to_html($inner_wrapper_atts); ?>>
		<?php if (
			$form_controls_type === 'outside'
			&&
			($html_atts['data-taxonomy-filter'] ?? 'false') === 'true'
			&&
			$has_visible_taxonomy_filter
		) { ?>
			<div class="ct-pseudo-input">
		<?php } ?>

			<input
				type="search" <?php echo $class_output ?>
				placeholder="<?php echo $placeholder; ?>"
				value="<?php echo get_search_query(); ?>"
				name="s"
				autocomplete="off"
				title="<?php echo __('Search for...', 'blocksy') ?>"
				aria-label="<?php echo __('Search for...', 'blocksy') ?>"
				<?php if ($has_live_results === 'yes') { ?>
					aria-autocomplete="list"
					aria-controls="<?php echo esc_attr($results_id); ?>"
					aria-describedby="<?php echo esc_attr($status_id); ?>"
					aria-expanded="false"
				<?php } ?>
				<?php
				if (
					isset($search_box_min_length)
					&&
					$search_box_min_length > 1
				) {
					echo 'data-min-length="' . esc_attr($search_box_min_length) . '"';
				}
				?>
			>

			<?php if ($has_visible_taxonomy_filter) {
				echo blocksy_html_tag(
					'select',
					[
						'class' => 'ct-select-taxonomy' . (! empty(trim($taxonomy_filter_visibility) ) ? $taxonomy_filter_visibility : ''),
						'name' => 'ct_tax_query',
						'aria-label' => __('Search in category', 'blocksy')
					],
					implode('', $options)
				);
				?>
			<?php } ?>

		<?php if (
			$form_controls_type === 'outside'
			&&
			($html_atts['data-taxonomy-filter'] ?? 'false') === 'true'
			&&
			$has_visible_taxonomy_filter
		) { ?>
			</div>
		<?php } ?>

		<button type="submit" class="wp-element-button" <?php echo blocksy_attr_to_html($button_html_atts); ?>>
			<?php
				/**
				 * Note to code reviewers: This line doesn't need to be escaped.
				 * The value used here escapes the value properly.
				 * It contains an inline SVG, which is safe.
				 */
				echo $icon
			?>

			<span class="ct-ajax-loader">
				<svg viewBox="0 0 24 24">
					<circle cx="12" cy="12" r="10" opacity="0.2" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="2"/>

					<path d="m12,2c5.52,0,10,4.48,10,10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2">
						<animateTransform
							attributeName="transform"
							attributeType="XML"
							type="rotate"
							dur="0.6s"
							from="0 12 12"
							to="360 12 12"
							repeatCount="indefinite"
							begin="indefinite"
						/>
					</path>
				</svg>
			</span>
		</button>

		<?php if (count($allowed_post_types) === 1) { ?>
			<input type="hidden" name="post_type" value="<?php echo esc_attr($allowed_post_types[0]) ?>">
		<?php } ?>

		<?php if (count($allowed_post_types) > 1) { ?>
			<input type="hidden" name="ct_post_type" value="<?php echo esc_attr(implode(':', $allowed_post_types)) ?>">
		<?php } ?>

		<?php if ($search_through_taxonomy) { ?>
			<input type="hidden" name="ct_search_taxonomies" value="yes">
		<?php } ?>


		<?php
			// Only output the nonce for logged-in users. This allows page cache
			// for non-logged-in users to have a longer TTL since there's no
			// user-specific nonce in the HTML. The REST API works without a
			// nonce for public endpoints.
			if (
				$has_live_results === 'yes'
				&&
				is_user_logged_in()
			) {
				echo blocksy_html_tag(
					'input',
					[
						'type' => 'hidden',
						'value' => wp_create_nonce('wp_rest'),
						'class' => 'ct-live-results-nonce'
					]
				);
			}
		?>
	</div>

	<?php if ($has_live_results === 'yes') { ?>
		<div
			class="screen-reader-text"
			id="<?php echo esc_attr($status_id); ?>"
			aria-live="polite"
			role="status">
			<?php echo __('No results', 'blocksy') ?>
		</div>
	<?php } ?>

</form>
