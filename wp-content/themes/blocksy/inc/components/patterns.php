<?php

namespace Blocksy;

class ThemePatterns {
	private $patterns = [
		'pattern-1',
		'pattern-2',
		'pattern-3',
		'pattern-4',
		'pattern-5',
		'pattern-6',
		'pattern-7',
		'pattern-8',
		'pattern-9',
		'pattern-10',
		'pattern-11',
		'pattern-12',
	];

	public function __construct() {
		add_action('init', [$this, 'register_patterns']);
	}

	public function register_patterns() {
		if (! function_exists('register_block_pattern')) {
			return;
		}

		register_block_pattern_category('blocksy', [
			'label' => _x('Blocksy', 'Block pattern category', 'blocksy'),
			'description' => __('Patterns that contain buttons and call to actions.', 'blocksy'),
		]);

		foreach ($this->patterns as $pattern) {
			register_block_pattern(
				'blocksy/' . $pattern,
				require __DIR__ . '/patterns/' . $pattern . '.php'
			);
		}
	}
}
