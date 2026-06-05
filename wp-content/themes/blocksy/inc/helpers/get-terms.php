<?php

function blocksy_get_terms($get_terms_args, $args = []) {
	$args = wp_parse_args($args, [
		'all_languages' => false
	]);

	if (! $args['all_languages']) {
		return get_terms($get_terms_args);
	}

	global $sitepress;

	if (function_exists('PLL')) {
		remove_filter(
			'terms_clauses',
			[PLL()->terms, 'terms_clauses'],
			10, 3
		);
	}

	if ($sitepress) {
		remove_filter('get_terms_args', array($sitepress, 'get_terms_args_filter'), 10, 2);
		remove_filter('get_term', array($sitepress, 'get_term_adjust_id'), 1, 1);
		remove_filter('terms_clauses', array($sitepress, 'terms_clauses'), 10, 3);

		$all_terms = get_terms($get_terms_args);

		add_filter('terms_clauses', array($sitepress, 'terms_clauses'), 10, 3);
		add_filter('get_term', array($sitepress, 'get_term_adjust_id'), 1, 1);
		add_filter('get_terms_args', array($sitepress, 'get_terms_args_filter' ), 10, 2);
	} else {
		$all_terms = get_terms($get_terms_args);
	}

	if (function_exists('PLL')) {
		add_filter(
			'terms_clauses',
			[PLL()->terms, 'terms_clauses'],
			10, 3
		);
	}

	return $all_terms;
}

