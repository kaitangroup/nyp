<?php

$pattern = [
	'title'      => __( 'Posts - Layout 2', 'blocksy-companion' ),
	'categories' => ['blocksy'],
	'blockTypes' => ['blocksy/query'],

	'content' => '<!-- wp:blocksy/query {"uniqueId":"84732fe0","limit":6} -->
	<div class="wp-block-blocksy-query"><!-- wp:blocksy/post-template {"layout":{"type":"grid","columnCount":3}} -->
	<!-- wp:group {"style":{"spacing":{"blockGap":"0"},"dimensions":{"minHeight":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","flexWrap":"nowrap"}} -->
	<div class="wp-block-group" style="min-height:100%"><!-- wp:blocksy/dynamic-data {"field":"wp:featured_image","style":{"spacing":{"margin":{"bottom":"0"}}},"has_field_link":"yes"} /-->

	<!-- wp:group {"style":{"dimensions":{"minHeight":"100%"},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"palette-color-8","layout":{"type":"constrained"}} -->
	<div class="wp-block-group has-palette-color-8-background-color has-background" style="min-height:100%"><!-- wp:blocksy/dynamic-data {"tagName":"h2","style":{"typography":{"fontSize":"18px"}},"has_field_link":"yes"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:date","style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"500"}},"fontSize":"small"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:excerpt","excerpt_length":20} /--></div>
	<!-- /wp:group --></div>
	<!-- /wp:group -->
	<!-- /wp:blocksy/post-template --></div>
	<!-- /wp:blocksy/query -->'
];
