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

		$css = $args['css'];
		$tablet_css = $args['tablet_css'];
		$mobile_css = $args['mobile_css'];

		$styles = [
			'desktop' => '',
			'tablet' => '',
			'mobile' => ''
		];

		$styles['desktop'] .= $css->build_css_structure();
		$styles['tablet'] .= $tablet_css->build_css_structure();
		$styles['mobile'] .= $mobile_css->build_css_structure();

		$final_css = '';

		if (! empty($styles['desktop'])) {
			$final_css .= $styles['desktop'];
		}

		if (! empty(trim($styles['tablet']))) {
			$final_css .= '@media (max-width: 999.98px) {' . $styles['tablet'] . '}';
		}

		if (! empty(trim($styles['mobile']))) {
			$final_css .= '@media (max-width: 689.98px) {' . $styles['mobile'] . '}';
		}

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
		$styles = [
			'desktop' => '',
			'tablet' => '',
			'mobile' => ''
		];

		if ($args['css']) {
			$styles['desktop'] .= $args['css']->build_css_structure();
		}

		if ($args['tablet_css']) {
			$styles['tablet'] .= $args['tablet_css']->build_css_structure();
		}

		if ($args['mobile_css']) {
			$styles['mobile'] .= $args['mobile_css']->build_css_structure();
		}

		// TODO: maybe extract media queries
		$final_css = $styles['desktop'];

		if (! empty(trim($styles['tablet']))) {
			$final_css .= '@media (max-width: 999.98px) {' . $styles['tablet'] . '}';
		}

		if (! empty(trim($styles['mobile']))) {
			$final_css .= '@media (max-width: 689.98px) {' . $styles['mobile'] . '}';
		}

		if (! empty($final_css)) {
			$this->css .= $final_css;
		}
	}
}
