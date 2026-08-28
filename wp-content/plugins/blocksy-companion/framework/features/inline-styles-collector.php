<?php

namespace Blocksy;

if (! defined('ABSPATH')) {
	exit;
}

class InlineStylesCollector {
	private $css = '';

	public function __construct() {
		add_action('wp_footer', [$this, 'output_css'], 5);
	}

	public function output_css() {
		if (empty($this->css)) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<style id="ct-main-styles-footer-inline-css">' . $this->css . '</style>';
	}

	public function get_style_tag($args = []) {
		$args = wp_parse_args($args, [
			'css' => null,
			'tablet_css' => null,
			'mobile_css' => null
		]);

		$final_css = blocksy_companion_assemble_dynamic_css($args);

		if (empty($final_css)) {
			return '';
		}

		return blocksy_html_tag('style', [], $final_css);
	}

	public function add($args = []) {
		$args = wp_parse_args($args, [
			'css' => null,
			'tablet_css' => null,
			'mobile_css' => null
		]);

		$strategy = 'core-block-supports';

		if ($strategy === 'core-block-supports') {
			$this->process_core_block_supports($args);
		}

		if ($strategy === 'top-of-footer') {
			$this->process_top_of_footer($args);
		}
	}

	private function process_core_block_supports($args) {
		$styles = [];

		if ($args['css']) {
			$styles = array_merge(
				$styles,
				$args['css']->get_wp_style_engine_rules([
					'device' => 'desktop'
				])
			);
		}

		if ($args['tablet_css']) {
			$styles = array_merge(
				$styles,
				$args['tablet_css']->get_wp_style_engine_rules([
					'device' => 'tablet'
				])
			);

		}

		if ($args['mobile_css']) {
			$styles = array_merge(
				$styles,
				$args['mobile_css']->get_wp_style_engine_rules([
					'device' => 'mobile'
				])
			);
		}

		blocksy_companion_call_gutenberg_function(
			'wp_style_engine_get_stylesheet_from_css_rules',
			[
				$styles,
				[
					'context'  => 'block-supports',
					'prettify' => false,
					'optimize' => true
				]
			]
		);
	}

	private function process_top_of_footer($args) {
		$final_css = blocksy_companion_assemble_dynamic_css($args);

		if (! empty($final_css)) {
			$this->css .= $final_css;
		}
	}
}
