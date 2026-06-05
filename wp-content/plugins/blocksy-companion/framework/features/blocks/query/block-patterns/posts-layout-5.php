<?php

$pattern = [
	'title'      => __( 'Posts - Layout 5', 'blocksy-companion' ),
	'categories' => ['blocksy'],
	'blockTypes' => ['blocksy/query'],

	'content' => '<!-- wp:blocksy/query {"uniqueId":"da90b624","limit":6} -->
	<div class="wp-block-blocksy-query"><!-- wp:blocksy/post-template {"layout":{"type":"grid","columnCount":3}} -->
	<!-- wp:blocksy/dynamic-data {"field":"wp:featured_image","viewType":"cover","style":{"elements":{"overlay":{"color":{"background":"var:preset|color|black"}}},"border":{"radius":"5px"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"customGradient":"linear-gradient(0deg,rgb(0,0,0) 21%,rgba(0,0,0,0) 100%)","contentPosition":"bottom center","minimumHeight":"400px"} -->
	<!-- wp:blocksy/dynamic-data {"tagName":"h2","align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"},":hover":{"color":{"text":"var:preset|color|palette-color-1"}}}}},"has_field_link":"yes","fontSize":"medium"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:date","align":"center","style":{"typography":{"fontSize":"12px","textTransform":"uppercase"}}} /-->
	<!-- /wp:blocksy/dynamic-data -->
	<!-- /wp:blocksy/post-template --></div>
	<!-- /wp:blocksy/query -->'
];