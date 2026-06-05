<?php

$pattern = [
	'title'      => __( 'Posts - Layout 1', 'blocksy-companion' ),
	'categories' => ['blocksy'],
	'blockTypes' => ['blocksy/query'],

	'content' => '<!-- wp:blocksy/query {"uniqueId":"33e9b2f4"} -->
	<div class="wp-block-blocksy-query"><!-- wp:blocksy/post-template {"layout":{"type":"default","columnCount":3}} -->
	<!-- wp:columns -->
	<div class="wp-block-columns"><!-- wp:column {"width":"25%"} -->
	<div class="wp-block-column" style="flex-basis:25%"><!-- wp:blocksy/dynamic-data {"field":"wp:featured_image","aspectRatio":"1","has_field_link":"yes"} /--></div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center","width":"75%"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:75%"><!-- wp:blocksy/dynamic-data {"tagName":"h2","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"has_field_link":"yes","fontSize":"medium"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:date","has_field_link":"yes"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:excerpt","excerpt_length":20} /--></div>
	<!-- /wp:column --></div>
	<!-- /wp:columns -->
	<!-- /wp:blocksy/post-template --></div>
	<!-- /wp:blocksy/query -->'
];
