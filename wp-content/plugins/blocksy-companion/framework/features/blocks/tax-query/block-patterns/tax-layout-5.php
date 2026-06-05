<?php

$pattern = [
	'title'      => __( 'Taxonomies - Layout 5', 'blocksy-companion' ),
	'categories' => ['blocksy'],
	'blockTypes' => ['blocksy/tax-query'],

	'content' => '<!-- wp:blocksy/tax-query {"uniqueId":"d27d7623"} -->
	<div class="wp-block-blocksy-tax-query"><!-- wp:blocksy/tax-template {"layout":{"type":"grid","columnCount":3}} -->
	<!-- wp:blocksy/dynamic-data {"field":"wp:term_image","viewType":"cover","style":{"border":{"radius":"5px"},"elements":{"overlay":{"color":{"background":"var:preset|color|black"}}},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"customGradient":"linear-gradient(0deg,rgb(0,0,0) 17%,rgba(0,0,0,0) 100%)","contentPosition":"bottom center","minimumHeight":"400px","has_field_link":"yes"} -->
	<!-- wp:blocksy/dynamic-data {"tagName":"h5","field":"wp:term_title","align":"center","style":{"spacing":{"margin":{"bottom":"0px"}},"elements":{"link":{"color":{"text":"var:preset|color|palette-color-8"}}}},"has_field_link":"yes"} /-->

	<!-- wp:blocksy/dynamic-data {"field":"wp:term_count","after":" items","align":"center"} /-->
	<!-- /wp:blocksy/dynamic-data -->
	<!-- /wp:blocksy/tax-template --></div>
	<!-- /wp:blocksy/tax-query -->'
];