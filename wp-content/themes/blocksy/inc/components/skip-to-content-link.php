<?php

add_action(
	'wp_body_open',
	function () {
		echo blocksy_html_tag(
			'a',
			[
				'class' => 'skip-link screen-reader-text',
				'href' => apply_filters('blocksy:head:skip-to-content:href', '#main'),
			],
			__('Skip to content', 'blocksy')
		);
	},
	50
);
