<?php

if (! function_exists('blocksy_eventkoi_content_close')) {
	function blocksy_eventkoi_content_close() {
		echo '</article>';

		get_sidebar();

		echo '</div>';

		remove_filter('render_block', 'blocksy_remove_eventkoi_native_title', 10, 2);
		remove_filter('render_block_data', 'blocksy_reset_eventkoi_content_width', 10, 2);
	}
}

if (! function_exists('blocksy_remove_eventkoi_native_title')) {
	function blocksy_remove_eventkoi_native_title($block_content, $block) {
		if (
			$block['blockName'] === 'core/heading'
			&&
			isset($block['attrs']['metadata']['bindings']['content']['args']['key'])
			&&
			$block['attrs']['metadata']['bindings']['content']['args']['key'] === 'event_title'
		) {
			return '';
		}

		return $block_content;
	}
}

if (! function_exists('blocksy_reset_eventkoi_content_width')) {
	function blocksy_reset_eventkoi_content_width($block, $source_block) {
		if (
			$block['blockName'] === 'core/group'
			&&
			isset($block['attrs']['className'])
			&&
			strpos($block['attrs']['className'], 'eventkoi-front') !== false
		) {
			$block['attrs']['layout'] = [
				'contentSize' => 'var(--wp--style--global--content-size)'
			];

			if (is_tax('event_cal')) {
				$str_from_replace = '/eventkoi-front" style="max-width:[^;]+;margin-left:auto;margin-right:auto;"/';
				$str_to_replace = '/eventkoi-front"/';

				if (isset($block['innerHTML'])) {
					$block['innerHTML'] = preg_replace(
						$str_from_replace,
						$str_to_replace,
						$block['innerHTML']
					);
				}

				if (isset($block['innerContent'])) {
					$block['innerContent'] = preg_replace(
						$str_from_replace,
						$str_to_replace,
						$block['innerContent']
					);
				}
			}
		}

		return $block;
	}
}

if (! function_exists('blocksy_eventkoi_content_open')) {
	function blocksy_eventkoi_content_open() {
		$page_structure = blocksy_get_page_structure();

		if (! is_singular()) {
			$page_structure = blocksy_get_theme_mod('eventkoi_event_archive_structure', 'type-4');

			switch ($page_structure) {
				case 'type-3':
					$page_structure = 'narrow';
					break;
				case 'type-4':
				case 'type-5':
					$page_structure = 'normal';
					break;
				default:
					$page_structure = 'none';
					break;
			}
		}

		$attr = [
			'class' => 'ct-container-full'
		];

		if ($page_structure === 'none' || blocksy_post_uses_vc()) {
			$attr['class'] = 'ct-container';

			if ($page_structure === 'narrow') {
				$attr['class'] = 'ct-container-narrow';
			}
		} else {
			$attr['data-content'] = $page_structure;
		}

		$attr = array_merge($attr, blocksy_sidebar_position_attr([
			'array' => true
		]));

		$attr = array_merge($attr, blocksy_get_v_spacing([
			'array' => true
		]));

		if (apply_filters('blocksy:single:has-default-hero', true)) {
			$resulting_hero = blocksy_output_hero_section([
				'type' => 'type-2'
			]);

			if (! empty($resulting_hero)) {
				add_filter('render_block', 'blocksy_remove_eventkoi_native_title', 10, 2);
				echo $resulting_hero;
			}
		}

		echo '<div ' . blocksy_attr_to_html($attr) . '>';

		add_filter('render_block_data', 'blocksy_reset_eventkoi_content_width', 10, 2);

		if (is_singular()) {
			echo '<article class="' . implode(' ', get_post_class()) . '">';
		} else {
			echo '<article class="ct-post-eventkoi-wrapper">';
		}

		$resulting_hero = blocksy_output_hero_section([
			'type' => 'type-1'
		]);

		if (! empty($resulting_hero)) {
			add_filter('render_block', 'blocksy_remove_eventkoi_native_title', 10, 2);
			echo $resulting_hero;
		}
	}
}

add_action('eventkoi_before_calendar_content', 'blocksy_eventkoi_content_open');
add_action('eventkoi_after_calendar_content', 'blocksy_eventkoi_content_close');

add_action('eventkoi_before_event_content', 'blocksy_eventkoi_content_open');
add_action('eventkoi_after_event_content', 'blocksy_eventkoi_content_close');

add_filter('blocksy:customizer:sync:whole-page', function ($args) {
	$prefix = '';

	if (isset($args['prefix'])) {
		$prefix = rtrim($args['prefix'], '_');
	}

	if ($prefix === 'eventkoi_event_archive') {
		return 'refresh';
	}

	return $args;
});

add_action('init', function() {
	if (
		! class_exists('\EventKoi\Init')
		||
		! is_tax('event_cal')
		||
		! is_singular('eventkoi_event')
		||
		! is_singular('evnt')
	) {
		return;
	}

	add_filter('get_the_archive_title', function($title, $original_title) {
		return $original_title;
	}, 10, 2);
});