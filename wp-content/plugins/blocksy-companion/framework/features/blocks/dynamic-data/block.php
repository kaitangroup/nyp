<?php

namespace Blocksy\Editor\Blocks;

if (! defined('ABSPATH')) {
	exit;
}

class DynamicData {
	public function __construct() {
		new DynamicDataAPI();

		register_block_type(
			BLOCKSY_PATH . '/static/js/editor/blocks/dynamic-data/block.json',
			[
				'render_callback' => [$this, 'render'],
			]
		);

		add_filter('blocksy:block-editor:localized_data', function ($data) {
			$options = blocksy_akg(
				'options',
				blocksy_companion_theme_functions()->blocksy_get_variables_from_file(
					dirname(__FILE__) . '/options.php',
					['options' => []]
				)
			);

			$options_name = 'dynamic-data';

			$data[$options_name] = $options;

			return $data;
		});

		add_filter(
			'render_block_data',
			function ($parsed_block) {
				if ($parsed_block['blockName'] !== 'blocksy/dynamic-data') {
					return $parsed_block;
				}

				$element_block_styles = [
					'overlay' => [
						'color' => [
							'background' => '#000000'
						]
					],
				];

				if (isset($parsed_block['attrs']['style']['elements'])) {
					$element_block_styles = $parsed_block['attrs']['style']['elements'];
				}

				if (! $element_block_styles) {
					return $parsed_block;
				}

				$class_name = wp_get_elements_class_name($parsed_block);

				$updated_class_name = $class_name;

				if (isset($parsed_block['attrs']['className'])) {
					$updated_class_name = $parsed_block['attrs']['className'] . " $class_name";
				}

				_wp_array_set(
					$parsed_block,
					['attrs', 'className'],
					$updated_class_name
				);

				$overlayOpacity = intval(blocksy_akg(
					'dimRatio',
					$parsed_block['attrs'],
					50
				)) / 100;

				$element_types = [
					'link' => [
						'selector'       => ".$class_name a",
						'hover_selector' => ".$class_name a:hover"
					],

					'overlay' => [
						'selector' => ".$class_name .wp-block-cover__background",

						'additional_styles' => [
							'opacity' => $overlayOpacity
						]
					]
				];

				foreach ($element_types as $element_type => $element_config) {
					$element_style_object = null;

					if (isset($element_block_styles[$element_type])) {
						$element_style_object = $element_block_styles[$element_type];
					}

					// Process primary element type styles.
					if ($element_style_object) {
						blocksy_companion_call_gutenberg_function(
							'wp_style_engine_get_styles',
							[
								$element_style_object,
								[
									'selector' => $element_config['selector'],
									'context' => 'block-supports',
								]
							]
						);

						if (isset($element_config['additional_styles'])) {
							blocksy_companion_get_gutenberg_class('\WP_Style_Engine')::store_css_rule(
								'block-supports',
								$element_config['selector'],
								$element_config['additional_styles']
							);
						}

						if (isset($element_style_object[':hover'])) {
							blocksy_companion_call_gutenberg_function(
								'wp_style_engine_get_styles',[
									$element_style_object[':hover'],
									[
										'selector' => $element_config['hover_selector'],
										'context' => 'block-supports',
									]
								]
							);
						}
					}
				}

				return $parsed_block;
			},
			10,
			1
		);
	}

	public function render($attributes, $content, $block) {
		if (
			isset($attributes['lightbox'])
			&&
			$attributes['lightbox'] === 'yes'
		) {
			if (wp_script_is('wp-block-image-view', 'registered')) {
				wp_enqueue_script('wp-block-image-view');
			}

			if (function_exists('wp_scripts_get_suffix')) {
				wp_register_script_module(
					'@wordpress/block-library/image',
					includes_url("blocks/image/view.min.js"),
					array('@wordpress/interactivity'),
					defined('GUTENBERG_VERSION') ? GUTENBERG_VERSION : get_bloginfo('version')
				);

				wp_enqueue_script_module('@wordpress/block-library/image');
			}
		}

		$post_id = get_the_ID();

		$maybe_special_post_id = blocksy_get_special_post_id([
			'context' => 'local',
			'block_context' => $block->context,
		]);

		$old_post = null;

		if ($maybe_special_post_id && $post_id !== $maybe_special_post_id) {
			global $post;

			$old_post = $post;

			$post = get_post($maybe_special_post_id);
			setup_postdata($post);
		}

		$content = blocksy_render_view(
			dirname(__FILE__) . '/view.php',
			[
				'attributes' => $attributes,
				'content' => $content,
				'block_instance' => $this,
				'block' => $block
			]
		);

		if ($old_post !== null) {
			wp_reset_postdata();
			$post = $old_post;
		}

		return $content;
	}

	public function get_dynamic_styles_for() {
		if (
			! function_exists('blocksy_companion_get_ext')
			||
			! blocksy_companion_get_ext('post-types-extra')
			||
			! blocksy_companion_get_ext('post-types-extra')->taxonomies_customization
		) {
			return '';
		}

		$styles = [
			'desktop' => '',
			'tablet' => '',
			'mobile' => ''
		];

		$css = new \Blocksy_Css_Injector();
		$tablet_css = new \Blocksy_Css_Injector();
		$mobile_css = new \Blocksy_Css_Injector();

		blocksy_companion_get_ext('post-types-extra')
			->taxonomies_customization
			->get_terms_dynamic_styles([
				'css' => $css,
				'tablet_css' => $tablet_css,
				'mobile_css' => $mobile_css,
				'context' => 'global',
				'chunk' => 'global'
			]);

		$styles['desktop'] .= $css->build_css_structure();
		$styles['tablet'] .= $tablet_css->build_css_structure();
		$styles['mobile'] .= $mobile_css->build_css_structure();

		$final_css = '';

		if (! empty($styles['desktop'])) {
			$final_css .= $styles['desktop'];
		}

		if (! empty(trim($styles['tablet']))) {
			$final_css .= '@media (max-width: 999.98px) {' . $styles['tablet'] . '}';
		}

		if (! empty(trim($styles['mobile']))) {
			$final_css .= '@media (max-width: 689.98px) {' . $styles['mobile'] . '}';
		}

		return $final_css;
	}
}

