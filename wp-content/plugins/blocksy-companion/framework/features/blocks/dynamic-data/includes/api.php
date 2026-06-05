<?php

namespace Blocksy\Editor\Blocks;

if (! defined('ABSPATH')) {
	exit;
}

class DynamicDataAPI {
	public function __construct() {
		add_action('rest_api_init', function() {
			register_rest_field('attachment', 'has_video', [
				'get_callback' => function ($post, $field_name, $request) {
					if ($post['type'] !== 'attachment') {
						return null;
					}

					$maybe_new_video = blocksy_get_post_options($post['id']);

					$media_video_source = blocksy_akg(
						'media_video_source',
						$maybe_new_video,
						'upload'
					);

					$video_url = '';

					if ($media_video_source === 'upload') {
						$video_url = blocksy_akg(
							'media_video_upload',
							$maybe_new_video,
							''
						);
					}

					if ($media_video_source === 'youtube') {
						$video_url = blocksy_akg(
							'media_video_youtube_url',
							$maybe_new_video,
							''
						);
					}

					if ($media_video_source === 'vimeo') {
						$video_url = blocksy_akg(
							'media_video_vimeo_url',
							$maybe_new_video,
							''
						);
					}

					return ! empty($video_url);
				}
			]);
		});

		add_action(
			'wp_ajax_blocksy_blocks_retrieve_dynamic_data_descriptor',
			function () {
				if (! current_user_can('manage_options')) {
					wp_send_json_error();
				}

				$data = json_decode(file_get_contents('php://input'), true);

				if (! array_key_exists('context', $data)) {
					wp_send_json_error();
				}

				wp_send_json_success(
					apply_filters('blocksy:general:blocks:dynamic-data:data', [
						'fields' => $this->get_custom_fields_response($data)
					])
				);
			}
		);

		add_action(
			'wp_ajax_blocksy_dynamic_data_block_custom_field_data',
			function () {
				if (! current_user_can('manage_options')) {
					wp_send_json_error();
				}

				$data = json_decode(file_get_contents('php://input'), true);

				if (
					! $data
					||
					! isset($data['context'])
					||
					! isset($data['field_provider'])
					||
					! isset($data['field_id'])
				) {
					wp_send_json_error();
				}

				$object = $this->compute_context_object($data['context']);

				if (! $object) {
					wp_send_json_success([
						'field_data' => ''
					]);
				}

				if (
					$data['field_provider'] === 'woo'
					&&
					$data['field_id'] === 'brands'
				) {
					wp_send_json_success([
						'field_data' => $this->render_woo_brands_field($object)
					]);
				}

				if (
					$data['field_provider'] === 'woo'
					&&
					$data['field_id'] === 'attributes'
				) {
					if (! isset($data['attributes']['attribute'])) {
						wp_send_json_success([
							'field_data' => []
						]);
					}

					wp_send_json_success([
						'field_data' => $this->render_woo_attributes_field(
							$object,
							$data['attributes']['attribute']
						)
					]);
				}

				wp_send_json_success([
					'field_data' => $this->render_custom_field(
						$object,
						$data['field_provider'],
						$data['field_id']
					)
				]);
			}
		);
	}

	private function render_woo_brands_field($object) {
		if (! is_a($object, 'WP_Post')) {
			return [];
		}

		$brands = get_the_terms($object->ID, 'product_brand');

		if (! $brands || is_wp_error($brands)) {
			return [];
		}

		$brands_result = [];

		foreach ($brands as $term) {
			$term_atts = blocksy_get_taxonomy_options($term->term_id);

			$maybe_image_id = get_term_meta($term->term_id, 'thumbnail_id', true);

			if (! empty($maybe_image_id)) {
				$maybe_attachment_url = wp_get_attachment_image_url(
					$maybe_image_id,
					'full'
				);

				if ($maybe_attachment_url) {
					$term_atts['icon_image'] = [
						'attachment_id' => $maybe_image_id,
						'url' => $maybe_attachment_url
					];
				}
			}

			$maybe_image = blocksy_akg('icon_image', $term_atts, '');

			if (is_array($maybe_image)) {
				$attachment = $maybe_image;
			}

			$brands_result[] = [
				'term_id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'image' => $attachment
			];
		}

		return $brands_result;
	}

	private function render_woo_attributes_field($object, $attribute) {
		if (! is_a($object, 'WP_Post')) {
			return [];
		}

		$product = wc_get_product($object->ID);

		if (! $product) {
			return [];
		}

		$attributes = $product->get_attributes();
		$taxonomy_name = wc_attribute_taxonomy_name($attribute);

		if (
			! $attributes
			||
			! isset($attributes[sanitize_title($taxonomy_name)])
		) {
			return [];
		}

		$attribute = $attributes[sanitize_title($taxonomy_name)];

		if (! $attribute) {
			return [];
		}

		$terms = $attribute->get_terms();

		if (! $terms) {
			return [];
		}

		$terms_result = [];

		foreach ($terms as $term) {
			$terms_result[] = [
				'term_id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			];
		}

		return $terms_result;
	}

	private function render_custom_field($object, $field_provider, $field_id) {
		if (
			! blocksy_companion_get_ext('post-types-extra')
			||
			! blocksy_companion_get_ext('post-types-extra')->dynamic_data
			||
			! blocksy_companion_get_ext('post-types-extra')->dynamic_data->custom_fields_manager
		) {
			return '';
		}

		$field_render = blocksy_companion_get_ext('post-types-extra')
			->dynamic_data
			->custom_fields_manager
			->render_field(
				$field_id,
				[
					'provider' => $field_provider,

					'allow_images' => true,

					'context_object' => $object
				]
			);

		if (! $field_render) {
			return '';
		}

		return $field_render['value'];
	}

	private function get_custom_fields_response($data) {
		if (
			! blocksy_companion_get_ext('post-types-extra')
			||
			! blocksy_companion_get_ext('post-types-extra')->dynamic_data
			||
			! blocksy_companion_get_ext('post-types-extra')->dynamic_data->custom_fields_manager
		) {
			return [];
		}

		$fields = blocksy_companion_get_ext('post-types-extra')
			->dynamic_data
			->custom_fields_manager
			->get_fields(
				$data['context'],
				[
					'provider' => 'all',
					'allow_images' => true,
				]
			);

		$fields_response = [];

		foreach ($fields as $fields_descriptor) {
			$provider_data = [
				'provider' => $fields_descriptor['provider'],
				'provider_label' => $fields_descriptor['label'],
				'fields' => []
			];

			foreach ($fields_descriptor['fields'] as $field) {
				$provider_data['fields'][] = [
					'id' => $field->get_id(),
					'label' => $field->get_label(),
					'type' => $field->get_type()
				];
			}

			$fields_response[] = $provider_data;
		}

		return $fields_response;
	}

	public function compute_context_object($context) {
		if (! isset($context['type'])) {
			return null;
		}

		if ($context['type'] === 'post') {
			if (
				! isset($context['post_id'])
				||
				! isset($context['post_type'])
			) {
				return null;
			}

			return get_post($context['post_id']);
		}

		if ($context['type'] === 'term') {
			if (! isset($context['term_id'])) {
				return null;
			}

			return get_term($context['term_id']);
		}

		return null;
	}
}

