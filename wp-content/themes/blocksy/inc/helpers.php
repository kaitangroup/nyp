<?php
/**
 * General purpose helpers
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Blocksy
 */

// Usage:
//
// $raii = blocksy_raii(function() { /* destruct code */ });
//
// When the $raii object goes out of scope, the callback will be called
// automatically.
function blocksy_raii($callback) {
	return new \Blocksy\RaiiPattern($callback);
}

function blocksy_assert_args($args, $fields = []) {
	foreach ($fields as $single_field) {
		if (
			! isset($args[$single_field])
			||
			! $args[$single_field]
		) {
			throw new Error($single_field . ' missing in args!');
		}
	}
}

function blocksy_sync_whole_page($args = []) {
	$args = wp_parse_args(
		$args,
		[
			'prefix_custom' => ''
		]
	);

	$selector = 'main#main';

	return apply_filters(
		'blocksy:customizer:sync:whole-page',
		array_merge(
			[
				'selector' => $selector,
				'container_inclusive' => true,
				'render' => function () {
					echo blocksy_replace_current_template();
				}
			],
			$args
		)
	);
}

function blocksy_get_with_percentage($id, $value) {
	$val = blocksy_get_theme_mod($id, $value);

	if (strpos($value, '%') !== false && is_numeric($val)) {
		$val .= '%';
	}

	return str_replace('%%', '%', $val);
}

/**
 * Link to menus editor for every empty menu.
 *
 * @param array  $args Menu args.
 */
if (! function_exists('blocksy_link_to_menu_editor')) {
	function blocksy_link_to_menu_editor($args) {
		if (! current_user_can('manage_options')) {
			return;
		}

		// see wp-includes/nav-menu-template.php for available arguments
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($args);

		$output = '<a class="ct-create-menu" href="' . admin_url('nav-menus.php') . '" target="_blank">' . $before . __('You don\'t have a menu yet, please create one here &rarr;', 'blocksy') . $after . '</a>';

		if (! empty($container)) {
			$output = "<$container>$output</$container>";
		}

		if ($echo) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_kses_post($output);
		}

		return $output;
	}
}

/**
 * Extract variable from a file.
 *
 * @param string $file_path path to file.
 * @param array  $_extract_variables variables to return.
 * @param array  $_set_variables variables to pass into the file.
 */
function blocksy_get_variables_from_file(
	$file_path,
	array $_extract_variables,
	array $_set_variables = array()
) {
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract($_set_variables, EXTR_REFS);
	unset($_set_variables);

	if (is_file($file_path)) {
		require $file_path;
	}

	foreach ($_extract_variables as $variable_name => $default_value) {
		if (isset($$variable_name) ) {
			$_extract_variables[$variable_name] = $$variable_name;
		}
	}

	return $_extract_variables;
}

/**
 * Safe render a view and return html
 * In view will be accessible only passed variables
 * Use this function to not include files directly and to not give access to current context variables (like $this)
 *
 * @param string $file_path File path.
 * @param array  $view_variables Variables to pass into the view.
 *
 * @return string HTML.
 */
if (! function_exists('blocksy_render_view')) {
	function blocksy_render_view(
		$file_path,
		$view_variables = [],
		$default_value = ''
	) {
		if (! is_file($file_path)) {
			return $default_value;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($view_variables, EXTR_REFS);
		unset($view_variables);

		ob_start();
		require $file_path;

		return ob_get_clean();
	}
}

function blocksy_render_view_e($file_path, $view_variables = [], $default_value = '') {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo blocksy_render_view($file_path, $view_variables, $default_value);
}

function blocksy_get_wp_theme() {
	return apply_filters('blocksy_get_wp_theme', wp_get_theme());
}

if (! function_exists('blocksy_get_wp_parent_theme')) {
	function blocksy_get_wp_parent_theme() {
		return apply_filters('blocksy_get_wp_theme', wp_get_theme(get_template()));
	}
}

function blocksy_current_url() {
	static $url = null;

	if ($url === null) {
		if (is_multisite() && !(defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL)) {
			switch_to_blog(1);
			$url = home_url();
			restore_current_blog();
		} else {
			$url = home_url();
		}

		//Remove the "//" before the domain name
		$url = ltrim(preg_replace('/^[^:]+:\/\//', '//', $url), '/');

		//Remove the ulr subdirectory in case it has one
		$split = explode('/', $url);

		//Remove end slash
		$url = rtrim($split[0], '/');

		$request_uri = blocksy_akg('REQUEST_URI', $_SERVER, '');

		$request_uri = apply_filters(
			'blocksy:current-url:request-uri',
			$request_uri
		);

		$url .= '/' . ltrim($request_uri, '/');

		$url = set_url_scheme('//' . $url); // https fix
	}

	return $url;
}

if (! function_exists('blocksy_get_all_image_sizes')) {
	function blocksy_get_all_image_sizes() {
		$titles = [
			'thumbnail' => __('Thumbnail', 'blocksy'),
			'medium' => __('Medium', 'blocksy'),
			'medium_large' => __('Medium Large', 'blocksy'),
			'large' => __('Large', 'blocksy'),
			'full' => __('Full Size', 'blocksy'),
			'woocommerce_thumbnail' => __('WooCommerce Thumbnail', 'blocksy'),
			'woocommerce_single' => __('WooCommerce Single', 'blocksy'),
			'woocommerce_gallery_thumbnail' => __(
				'WooCommerce Gallery Thumbnail',
				'blocksy'
			),
			'woocommerce_archive_thumbnail' => __(
				'WooCommerce Archive Thumbnail',
				'blocksy'
			)
		];

		$all_sizes = get_intermediate_image_sizes();

		$result = [
			'full' => __('Full Size', 'blocksy')
		];

		foreach ($all_sizes as $single_size) {
			if (isset($titles[$single_size])) {
				$result[$single_size] = $titles[$single_size];
			} else {
				$result[$single_size] = $single_size;
			}
		}

		return $result;
	}
}

if (! function_exists('blocksy_debug')) {
	function blocksy_debug_log($message, $object = null) {
		if (
			! defined('WP_DEBUG')
			||
			! WP_DEBUG
		) {
			return;
		}

		if (is_null($object)) {
			error_log($message);
		} else {
			error_log($message . ': ' . print_r($object, true));
		}
	}
}

function blocksy_sanitize_user_html($html) {
	// Just drop scripts from the html content, if user doesnt have
	// unfiltered_html capability.
	//
	// Should happen BEFORE do_shortcode() as shortcodes can contain inline
	// scripts but we should leave those in place, since those come from trusted
	// places.
	if (! current_user_can('unfiltered_html')) {
		$html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);

		// Remove any on*="…" or on*='…' or on*=… (unquoted) attributes
		// Matches: space + on + letters + optional whitespace = optional quotes + anything except > + optional closing quote
		$html = preg_replace(
			'#\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#is',
			'',
			$html
		);
	}

	return $html;
}

function blocksy_output_html_safely($html) {
	return do_shortcode($html);

	// Dont use wp_filter_post_kses() as it is very unstable as far as slashes go.
	// Just call wp_kses() directly.
	//
	// Context:
	//
	// https://github.com/WP-API/WP-API/issues/2848
	// https://github.com/WP-API/WP-API/issues/2788
	// https://core.trac.wordpress.org/ticket/38609
	// return wp_kses($html, 'post');
}

function blocksy_get_pricing_links() {
	return apply_filters('blocksy:modal:pricing-links', [
		'pricing' => 'https://creativethemes.com/blocksy/pricing/',
		'premium' => 'https://creativethemes.com/blocksy/premium/',
		'compare-plans' => 'https://creativethemes.com/blocksy/pricing/#comparison-free-vs-pro'
	]);
}
