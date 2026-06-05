<?php

namespace Blocksy;

class EntityIdPicker {
	public function mount_ajax_action() {
		add_action('wp_ajax_blocksy_get_all_entities', function () {
			if (! current_user_can('manage_options')) {
				wp_send_json_error();
			}

			$maybe_input = json_decode(file_get_contents('php://input'), true);

			if (
				! $maybe_input
				||
				! isset($maybe_input['entity'])
			) {
				wp_send_json_error();
			}

			$result = false;

			if ($maybe_input['entity'] === 'posts') {
				$result = $this->get_all_posts($maybe_input);
			}

			if ($maybe_input['entity'] === 'users') {
				$result = $this->get_all_users($maybe_input);
			}

			if ($maybe_input['entity'] === 'taxonomies') {
				$result = $this->get_all_taxonomies($maybe_input);
			}

			if (! $result) {
				wp_send_json_error();
			}

			wp_send_json_success($result);
		});
	}

	public function get_default_value($args = []) {
		$args = wp_parse_args($args, [
			// posts | users | taxonomies
			'entity' => 'posts',

			'post_type' => 'post',

			// ID | entity
			'return' => 'ID'
		]);

		$input = [
			'post_type' => $args['post_type']
		];

		$result = null;

		if ($args['entity'] === 'posts') {
			$result = $this->get_all_posts($input, [
				'limit' => 1
			]);
		}

		if ($args['entity'] === 'users') {
			$result = $this->get_all_users($input, [
				'limit' => 1
			]);
		}

		if ($args['entity'] === 'taxonomies') {
			$result = $this->get_all_taxonomies($input, [
				'limit' => 1
			]);
		}

		if ($result && ! empty($result['entities'])) {
			if ($args['return'] === 'ID') {
				return $result['entities'][0]['id'];
			}

			return $result['entities'][0];
		}

		return null;
	}

	private function get_all_taxonomies($maybe_input, $args = []) {
		$args = wp_parse_args($args, [
			'limit' => 20
		]);

		$cpts = blocksy_manager()->post_types->get_supported_post_types();

		$cpts[] = 'post';
		$cpts[] = 'page';
		// $cpts[] = 'product';

		$taxonomies = [];

		if (
			isset($maybe_input['post_type'])
			&&
			$maybe_input['post_type'] === 'product'
		) {
			$cpts = ['product'];
		}

		foreach ($cpts as $cpt) {
			$taxonomies = array_merge($taxonomies, array_values(array_diff(
				get_object_taxonomies($cpt),
				['post_format']
			)));
		}

		$terms = [];
		$terms_query_args = [
			'number' => $args['limit'],
			'hide_empty' => false,
		];

		if (
			isset($maybe_input['search_query'])
			&&
			! empty($maybe_input['search_query'])
		) {
			$terms_query_args['search'] = $maybe_input['search_query'];
		}

		foreach ($taxonomies as $taxonomy) {
			$taxonomy_object = get_taxonomy($taxonomy);

			if (! $taxonomy_object->public) {
				continue;
			}

			$local_terms = array_map(function ($tax) {
				return [
					'id' => $tax->term_id,
					'label' => htmlspecialchars_decode($tax->name),
					'group' => get_taxonomy($tax->taxonomy)->label,
					'post_types' => get_taxonomy($tax->taxonomy)->object_type
				];
			}, blocksy_get_terms(
				array_merge(
					[
						'taxonomy' => $taxonomy,
					],
					$terms_query_args
				),
				[
					'all_languages' => true
				]
			));

			if (empty($local_terms)) {
				continue;
			}

			$terms = array_merge($terms, $local_terms);
		}

		if (isset($maybe_input['alsoInclude'])) {
			$maybe_term = get_term($maybe_input['alsoInclude']);

			if ($maybe_term) {
				$terms[] = [
					'id' => $maybe_term->term_id,
					'label' => htmlspecialchars_decode($maybe_term->name),
					'group' => get_taxonomy($maybe_term->taxonomy)->label,
					'post_types' => get_taxonomy($maybe_term->taxonomy)->object_type
				];
			}
		}

		return [
			'entities' => $this->with_uniq_ids($terms)
		];
	}

	private function get_all_users($maybe_input, $args = []) {
		$args = wp_parse_args($args, [
			'limit' => 10
		]);

		$users = [];

		$user_query_args = [
			'number' => $args['limit'],
			'fields' => ['ID', 'user_nicename'],
		];

		if (
			isset($maybe_input['search_query'])
			&&
			! empty($maybe_input['search_query'])
		) {
			$user_query_args['search'] = '*' . $maybe_input['search_query'] . '*';
		}

		$user_query = new \WP_User_Query($user_query_args);

		foreach ($user_query->get_results() as $user) {
			$users[] = [
				'id' => $user->ID,
				'label' => $user->user_nicename
			];
		}

		if (isset($maybe_input['alsoInclude'])) {
			$maybe_user = get_user_by('id', $maybe_input['alsoInclude']);

			if ($maybe_user) {
				$users[] = [
					'id' => $maybe_user->ID,
					'label' => $maybe_user->user_nicename
				];
			}
		}

		return [
			'entities' => $this->with_uniq_ids($users)
		];
	}

	private function get_all_posts($maybe_input, $args = []) {
		$args = wp_parse_args($args, [
			'limit' => 10
		]);

		if (! isset($maybe_input['post_type'])) {
			return false;
		}

		$query_args = [
			'posts_per_page' => $args['limit'],
			'post_type' => $maybe_input['post_type'],
			'suppress_filters' => true,
			'lang' => '',
		];

		if (
			isset($maybe_input['search_query'])
			&&
			! empty($maybe_input['search_query'])
		) {
			if (intval($maybe_input['search_query'])) {
				$query_args['p'] = intval($maybe_input['search_query']);
			} else {
				$query_args['s'] = $maybe_input['search_query'];
			}
		}

		$initial_query_args_post_type = $query_args['post_type'];

		if (strpos($initial_query_args_post_type, 'ct_cpt') !== false) {
			$query_args['post_type'] = blocksy_manager()->post_types->get_all([
				'exclude_built_in' => true,
				'exclude_woo' => true
			]);
		}

		if (strpos($initial_query_args_post_type, 'ct_all_posts') !== false) {
			$query_args['post_type'] = blocksy_manager()->post_types->get_all();
		}

		if (! is_array($query_args['post_type'])) {
			$query_args['post_type'] = [$query_args['post_type']];
		}

		$query = new \WP_Query($query_args);

		$posts_result = $query->posts;

		if (isset($maybe_input['alsoInclude'])) {
			$maybe_post = get_post($maybe_input['alsoInclude'], 'display');

			if (
				$maybe_post
				&&
				in_array($maybe_post->post_type, $query_args['post_type'])
			) {
				$posts_result[] = $maybe_post;
			}
		}

		$posts_result = array_map(function ($post) {
			return [
				'id' => $post->ID,
				'label' => htmlspecialchars_decode($post->post_title),
				'post_type' => $post->post_type
			];
		}, $posts_result);

		return [
			'entities' => $this->with_uniq_ids($posts_result)
		];
	}

	private function with_uniq_ids($items = []) {
		$uniq_ids = [];

		return array_filter($items, function ($item) use (&$uniq_ids) {
			if (in_array($item['id'], $uniq_ids)) {
				return false;
			}

			$uniq_ids[] = $item['id'];

			return true;
		});
	}
}

