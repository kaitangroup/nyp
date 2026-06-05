<?php

add_action('blocksy:rest_api:live_search:fields', function () {
	register_rest_field('search-result', 'placeholder_image', [
		'get_callback' => function ($post, $field_name, $request) {
			if ($post['type'] !== 'product') {
				return null;
			}

			return wc_placeholder_img_src('thumbnail');
		}
	]);
});

