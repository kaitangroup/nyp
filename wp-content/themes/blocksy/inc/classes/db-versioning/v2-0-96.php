<?php

namespace Blocksy\DbVersioning;

class V2096 {
	public function migrate() {
		$this->migrate_brands_slug();
		$this->migrate_product_brand_terms();

		flush_rewrite_rules();
	}

	private function migrate_brands_slug() {
		if (! class_exists('\Blocksy\Extensions\WoocommerceExtra\Storage')) {
			return;
		}

		$storage = new \Blocksy\Extensions\WoocommerceExtra\Storage();
		$settings = $storage->get_settings();

		if (
			isset($settings['product-brands-slug'])
			&&
			$settings['product-brands-slug'] !== 'brand'
			&&
			get_option('woocommerce_product_brand_slug', '__empty__') === '__empty__'
		) {
			update_option(
				'woocommerce_product_brand_slug',
				$settings['product-brands-slug']
			);
		}
	}

	private function process_site() {
		$taxonomy = 'product_brands';

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT t.term_id, t.name, t.slug
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s
				",
				$taxonomy
			)
		);

		if (
			! $results
			||
			empty($results)
		) {
			return;
		}

		foreach ($results as $term) {
			$native_brand = get_term_by(
				'slug',
				$term->slug,
				'product_brand'
			);

			if ($native_brand) {
				wp_delete_term(
					$native_brand->term_id,
					'product_brand'
				);
			}

			$options = blocksy_get_taxonomy_options($term->term_id);

			if (
				! $options
				||
				empty($options)
			) {
				continue;
			}

			update_term_meta(
				$term->term_id,
				'thumbnail_id',
				sanitize_text_field(wp_unslash(
					isset($options['icon_image']['attachment_id']) ? $options['icon_image']['attachment_id'] : ''
				))
			);

			unset($options['icon_image']);

			update_term_meta(
				$term->term_id,
				'blocksy_taxonomy_meta_options',
				$options
			);
		}

		$wpdb->update(
			$wpdb->term_taxonomy,
			[
				'taxonomy' => 'product_brand'
			],
			[
				'taxonomy' => 'product_brands'
			]
		);

		$wpdb->update(
			$wpdb->prefix . 'blocksy_product_taxonomies_lookup',
			[
				'taxonomy' => 'product_brand'
			],
			[
				'taxonomy' => 'product_brands'
			]
		);
	}

	private function migrate_product_brand_terms() {
		
		if (! is_multisite()) {
			$this->process_site();
			return;
		}

		if ( ! function_exists( 'get_sites' ) ) {
			require_once ABSPATH . 'wp-includes/class-wp-site-query.php';
			require_once ABSPATH . 'wp-includes/ms-site.php';
		}

		$blog_list = get_sites();

		if (
			! $blog_list
			||
			empty($blog_list)
		) {
			return;
		}

		foreach ($blog_list as $blog) {
			switch_to_blog($blog->blog_id);
			$this->process_site();

			restore_current_blog();
		}
	}
}

