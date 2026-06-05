<?php

$pattern = [
	'title'      => __( 'Posts - Layout 4', 'blocksy-companion' ),
	'categories' => ['blocksy'],
	'blockTypes' => ['blocksy/query'],

	'content' => '<!-- wp:blocksy/query {"uniqueId":"f6e8491a","limit":3} -->
	<div class="wp-block-blocksy-query"><!-- wp:blocksy/post-template {"layout":{"type":"default","columnCount":3},"style":{"spacing":{"blockGap":"var:preset|spacing|80"}}} -->
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns"><!-- wp:column {"width":"45%","style":{"spacing":{"blockGap":"0"}}} -->
	<div class="wp-block-column" style="flex-basis:45%"><!-- wp:blocksy/dynamic-data {"field":"wp:featured_image","aspectRatio":"1","style":{"border":{"radius":"25px"}},"has_field_link":"yes"} /--></div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center","width":"75%"} -->
	<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:75%"><!-- wp:blocksy/dynamic-data {"field":"wp:terms","taxonomy":"category","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"15px"},"spacing":{"margin":{"bottom":"0"}}},"has_field_link":"yes"} /-->

	<!-- wp:blocksy/dynamic-data {"tagName":"h2","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50","top":"var:preset|spacing|50"}}},"has_field_link":"yes","fontSize":"medium"} /-->

	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-group"><!-- wp:blocksy/dynamic-data {"field":"wp:author_avatar","avatar_size":30,"style":{"border":{"radius":"100%"}}} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:author","style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"15px"}},"has_field_link":"yes"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:date","before":"/ ","style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"15px"}}} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:comments","before":"/ ","style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"15px"}}} /--></div>
	<!-- /wp:group --></div>
	<!-- /wp:column --></div>
	<!-- /wp:columns -->
	<!-- /wp:blocksy/post-template --></div>
	<!-- /wp:blocksy/query -->'
];
