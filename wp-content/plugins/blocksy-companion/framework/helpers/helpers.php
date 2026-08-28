<?php

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Post name.
 */
function blocksy_companion_post_name() {
	return 'ct_options';
}

function blocksy_companion_call_gutenberg_function($original_function_name, $args = []) {
	$gutenberg_function_name = str_replace(
		'wp_',
		'gutenberg_',
		$original_function_name
	);

	$function_to_call = $original_function_name;

	if (function_exists($gutenberg_function_name)) {
		$function_to_call = $gutenberg_function_name;
	}

	return call_user_func_array($function_to_call, $args);
}

function blocksy_companion_get_gutenberg_class($class_name) {
	$gutenberg_class_name = $class_name . '_Gutenberg';

	if (class_exists($gutenberg_class_name)) {
		return $gutenberg_class_name;
	}

	return $class_name;
}

function blocksy_companion_get_version() {
	static $version = null;

	if ($version !== null) {
		return $version;
	}

	if (! function_exists('get_plugin_data')) {
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	// Prevent early translation call by setting $translate to false.
	$plugin_data = get_plugin_data(
		BLOCKSY__FILE__,
		false,
		/* $translate */ false
	);

	$version = $plugin_data['Version'];

	return $version;
}

/**
 * Assemble desktop + responsive dynamic CSS into a single string, wrapping the
 * tablet/mobile output in Blocksy's breakpoint media queries. The css/tablet_css/
 * mobile_css args are CSS-structure objects (respond to build_css_structure())
 * or null.
 *
 * Centralizes the desktop + 999.98px + 689.98px pattern that was copied across
 * the popups renderer, the mega-menu AJAX renderer, the dynamic-data block and
 * the inline styles collector.
 */
function blocksy_companion_assemble_dynamic_css($args = []) {
	$args = wp_parse_args($args, [
		'css' => null,
		'tablet_css' => null,
		'mobile_css' => null,
	]);

	$result = $args['css'] ? trim($args['css']->build_css_structure()) : '';

	$tablet = $args['tablet_css'] ? trim($args['tablet_css']->build_css_structure()) : '';
	$mobile = $args['mobile_css'] ? trim($args['mobile_css']->build_css_structure()) : '';

	if (! empty($tablet)) {
		$result .= '@media (max-width: 999.98px) {' . $tablet . '}';
	}

	if (! empty($mobile)) {
		$result .= '@media (max-width: 689.98px) {' . $mobile . '}';
	}

	return $result;
}

function blocksy_companion_get_capabilities() {
	static $capabilities = null;

	if ($capabilities === null) {
		$capabilities = new Blocksy\Capabilities();
	}

	return $capabilities;
}

function blocksy_companion_theme_functions() {
	static $theme_functions = null;

	if ($theme_functions === null) {
		$theme_functions = new Blocksy\ThemeFunctions();
	}

	return $theme_functions;
}

function blocksy_companion_can_use_premium_code() {
	return !! class_exists('Blocksy\Premium');
}

function blocksy_companion_site_has_feature($feature = 'base_pro') {
	return (
		blocksy_companion_can_use_premium_code()
		&&
		blocksy_companion_get_capabilities()->has_feature($feature)
	);
}

add_filter('blocksy:companion:has', function ($has, $id) {
	if ($id === 'base_pro') {
		return blocksy_companion_site_has_feature('base_pro');
	}

	return $has;
}, 10, 2);

function blocksy_companion_register_theme_bridge($feature, $function_name) {
	$method = str_replace('blocksy_companion_', '', $function_name);

	add_filter('blocksy:companion:has', function ($has, $id) use ($feature) {
		if ($id === $feature) {
			return true;
		}

		return $has;
	}, 10, 2);

	add_filter('blocksy:companion:' . $method, function ($default, $atts) use ($function_name) {
		$result = call_user_func($function_name, $atts);

		if ($result) {
			return $result;
		}

		return $default;
	}, 10, 2);
}

/**
 * Activate a companion extension by id. Bridged so the theme's db migrations can
 * request activation through blocksy_manager()->companion->activate_extension([...])
 * instead of reaching \Blocksy\Plugin::instance()->extensions directly (which fatals
 * when the companion is inactive or mid-update).
 */
function blocksy_companion_activate_extension($args = []) {
	$args = wp_parse_args($args, [
		'id' => null
	]);

	if (! $args['id']) {
		return false;
	}

	\Blocksy\Plugin::instance()->extensions->activate_extension($args['id']);

	return true;
}

blocksy_companion_register_theme_bridge('extensions', 'blocksy_companion_activate_extension');

// https://developer.wordpress.org/reference/functions/is_ssl/
function blocksy_companion_maybe_is_ssl() {
	// cloudflare
	if (! empty($_SERVER['HTTP_CF_VISITOR'])) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cfo = json_decode(wp_unslash($_SERVER['HTTP_CF_VISITOR']));

		if (isset($cfo->scheme) && 'https' === $cfo->scheme) {
			return true;
		}
	}

	// other proxy
	if (
		! empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&&
		'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']
	) {
		return true;
	}

	// is_ssl() sometimes returns false when it should return true,
	// for example updates.
	if (strpos(strtolower(get_site_url()), 'https://') === 0) {
		return true;
	}

	return function_exists('is_ssl') ? is_ssl() : false;
}

// Don't use protocol relative URL, it's an anti pattern.
// https://www.paulirish.com/2010/the-protocol-relative-url/
function blocksy_companion_normalize_site_url($url) {
	$parsed_url = wp_parse_url($url);

	$protocol = 'http';

	if (blocksy_companion_maybe_is_ssl()) {
		$protocol .= 's';
	}

	$result = $protocol . '://' . $parsed_url['host'];

	if (isset($parsed_url['port'])) {
		$result = $result . ':' . $parsed_url['port'];
	}

	if (isset($parsed_url['path'])) {
		$result = $result . $parsed_url['path'];
	}

	return $result;
}

if (! function_exists('blocksy_companion_load_xml_file')) {
	function blocksy_companion_load_xml_file($url, $args = []) {
		$args = wp_parse_args($args, [
			'user_agent' => ''
		]);

		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		set_time_limit(300);

		if (ini_get('allow_url_fopen') && ini_get('allow_url_fopen') !== 'Off') {
			$context_options = [
				"ssl" => [
					"verify_peer" => false,
					"verify_peer_name" => false,
				]
			];

			if (! empty($args['user_agent'])) {
				$context_options['http'] = [
					'user_agent' => $args['user_agent']
				];
			}

			return file_get_contents(
				$url,
				false,
				stream_context_create($context_options)
			);
		} else if (function_exists('curl_init')) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
			$curl = curl_init($url);

			if (! empty($args['user_agent'])) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
				curl_setopt($curl, CURLOPT_USERAGENT, $args['user_agent']);
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
			$result = curl_exec($curl);
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
			curl_close($curl);

			return $result;
		} else {
			throw new Exception("Can't load data.");
		}
	}
}

function blocksy_companion_stringify_url($parsed_url) {
	$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
	$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
	$pass = ($user || $pass) ? "$pass@" : '';
	$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	$query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

	return "$scheme$user$pass$host$port$path$query$fragment";
}

function blocksy_companion_is_xhr() {
	return (
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		isset($_REQUEST['blocksy_ajax'])
		&&
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		strtolower(sanitize_text_field(wp_unslash($_REQUEST['blocksy_ajax']))) === 'yes'
	);
}

function blocksy_companion_get_option_from_db($option, $default = '') {
	try {
		global $wpdb;

		$suppress = $wpdb->suppress_errors();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
				$option
			)
		);

		$wpdb->suppress_errors($suppress);

		if (is_object($row)) {
			return maybe_unserialize($row->option_value);
		}
	} catch (Exception $e) {
	}

	return $default;
}

function blocksy_companion_get_network_option_from_db($network_id, $option, $default = '') {
	if ($network_id && ! is_numeric($network_id)) {
		return false;
	}

	$network_id = (int) $network_id;

	// Fallback to the current network if a network ID is not specified.
	if (! $network_id) {
		$network_id = get_current_network_id();
	}

	try {
		global $wpdb;

		$suppress = $wpdb->suppress_errors();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d",
				$option,
				$network_id
			)
		);

		$wpdb->suppress_errors($suppress);

		if (is_object($row)) {
			return maybe_unserialize($row->meta_value);
		}
	} catch (Exception $e) {
	}

	return $default;
}

function blocksy_companion_safe_sprintf($format, ...$args) {
	$result = $format;

	$is_error = false;

	// vsprintf() triggers a warning on PHP < 8 and throws an exception on PHP 8+
	// We need to handle both.
	// https://www.php.net/manual/en/function.vsprintf.php#refsect1-function.vsprintf-errors

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
	set_error_handler(function () use (&$is_error) {
		$is_error = true;
	});

	if (interface_exists('Throwable')) {
		try {
			$result = vsprintf($format, $args);
		} catch (\Throwable $e) {
			$is_error = true;
		}
	} else {
		$result = vsprintf($format, $args);
	}

	restore_error_handler();

	if ($is_error) {
		// TODO: maybe cleanup format from %s, %d, etc
		return $format;
	}

	return $result;
}

function blocksy_companion_request_remote_url($url, $args = []) {
	$request = new \Blocksy\RequestRemoteUrl();
	return $request->request($url, $args);
}

function blocksy_companion_get_jed_locale_data($domain) {
	static $locale = [];

	if (isset($locale[$domain])) {
		return $locale[$domain];
	}

	$translations = get_translations_for_domain($domain);

	$locale[$domain] = [
		'' => [
			'domain' => $domain,
			'lang' => get_user_locale(),
		]
	];

	if (! empty($translations->headers['Plural-Forms'])) {
		$locale[$domain]['']['plural_forms'] = $translations->headers['Plural-Forms'];
	}

	foreach (blocksy_companion_get_json_translation_files($domain) as $file_path) {
		$parsed_json = json_decode(
			call_user_func(
				'file' . '_get_contents',
				$file_path
			),
			true
		);

		if (
			! $parsed_json
			||
			! isset($parsed_json['locale_data']['messages'])
		) {
			continue;
		}

		foreach ($parsed_json['locale_data']['messages'] as $msgid => $entry) {
			if (empty($msgid)) {
				continue;
			}

			$locale[$domain][$msgid] = $entry;
		}
	}

	foreach ($translations->entries as $msgid => $entry) {
		$locale[$domain][$entry->key()] = [$translations->translate($entry->key())];
	}

	return $locale[$domain];
}

/**
 * Contain a fatal thrown while a view/options file is being `require`d. A missing
 * function/class throws \Error (a \Throwable) since PHP 7, so the require can be
 * wrapped to keep one broken file from white-screening the whole request — the
 * #5212 "theme/companion mid-swap" failure mode.
 *
 * Logs through blocksy_companion_debug_log(), passing the \Throwable itself as the
 * object so the full detail — message, file:line and the backtrace — is captured:
 * print_r'd into the error_log, and handed live to the `blocksy:companion:debug-log`
 * action for any listener. In development it re-throws so the bug isn't silently
 * swallowed; the re-throw is filterable via `blocksy:companion:contained-fatal:rethrow`.
 *
 * PARITY: mirrored by the theme's blocksy_handle_contained_fatal() (inc/helpers.php)
 * — keep both in sync.
 *
 * @param \Throwable $e       The contained error (carries the backtrace).
 * @param string     $context The file being loaded when it threw.
 *
 * @return void
 */
function blocksy_companion_handle_contained_fatal(\Throwable $e, $context = '') {
	blocksy_companion_debug_log(
		sprintf(
			'[Blocksy Companion] Contained fatal while loading %s: %s in %s:%d',
			$context,
			$e->getMessage(),
			$e->getFile(),
			$e->getLine()
		),
		$e
	);

	/**
	 * Filters whether a contained fatal is re-thrown after being logged.
	 *
	 * Defaults to true under WP_DEBUG so bugs surface in development, and
	 * false otherwise so production stays contained. Return true to always
	 * re-throw, or false to always swallow.
	 *
	 * @since 2.1.47
	 *
	 * @param bool $should_rethrow Whether to re-throw the contained error.
	 */
	$should_rethrow = apply_filters(
		'blocksy:companion:contained-fatal:rethrow',
		defined('WP_DEBUG') && WP_DEBUG
	);

	if ($should_rethrow) {
		throw $e;
	}
}

/**
 * Safe render a view and return html
 * In view will be accessible only passed variables
 * Use this function to not include files directly and to not give access to current context variables (like $this)
 *
 * PARITY: mirrored by the theme's blocksy_render_view() (inc/helpers.php) — keep
 * both in sync.
 *
 * @param string $file_path File path.
 * @param array  $view_variables Variables to pass into the view.
 * @param string $default_value Returned when the file is missing or it fatals.
 *
 * @return string HTML.
 */
function blocksy_companion_render_view($file_path, $view_variables = [], $default_value = '') {
	if (! is_file($file_path)) {
		return $default_value;
	}

	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract($view_variables, EXTR_REFS);
	unset($view_variables);

	ob_start();

	try {
		require $file_path;
	} catch (\Throwable $e) {
		ob_end_clean();
		blocksy_companion_handle_contained_fatal($e, $file_path);
		return $default_value;
	}

	return ob_get_clean();
}

/**
 * Echo the result of blocksy_companion_render_view().
 *
 * PARITY: mirrored by the theme's blocksy_render_view_e() (inc/helpers.php) — keep
 * both in sync.
 *
 * @param string $file_path File path.
 * @param array  $view_variables Variables to pass into the view.
 * @param string $default_value Echoed when the file is missing.
 *
 * @return void
 */
function blocksy_companion_render_view_e($file_path, $view_variables = [], $default_value = '') {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo blocksy_companion_render_view($file_path, $view_variables, $default_value);
}

/**
 * Extract variable from a file.
 *
 * PARITY: mirrored by the theme's blocksy_get_variables_from_file() (inc/helpers.php)
 * — keep both in sync.
 *
 * @param string $file_path path to file.
 * @param array  $_extract_variables variables to return.
 * @param array  $_set_variables variables to pass into the file.
 *
 * @return array The requested variables (defaults when the file is missing/fatals).
 */
function blocksy_companion_get_variables_from_file(
	$file_path,
	array $_extract_variables,
	array $_set_variables = array()
) {
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract($_set_variables, EXTR_REFS);
	unset($_set_variables);

	if (is_file($file_path)) {
		try {
			require $file_path;
		} catch (\Throwable $e) {
			blocksy_companion_handle_contained_fatal($e, $file_path);
		}
	}

	foreach ($_extract_variables as $variable_name => $default_value) {
		if (isset($$variable_name) ) {
			$_extract_variables[$variable_name] = $$variable_name;
		}
	}

	return $_extract_variables;
}

/**
 * Load an options file and return its `$options` array.
 *
 * PARITY: mirror of the theme's blocksy_get_options() (inc/helpers.php) — keep both
 * in sync. A relative path still resolves against the active theme's inc/options
 * (the companion reuses theme option files); returns null when the file is absent.
 * Fires the SAME `blocksy:options:retrieve` filter as the theme.
 *
 * @param string $path        Options file path (relative to theme inc/options, or absolute when $relative is false).
 * @param array  $pass_inside Variables to pass into the file.
 * @param bool   $relative    Whether $path is relative to the theme's inc/options dir.
 *
 * @return array|null
 */
function blocksy_companion_get_options($path, $pass_inside = [], $relative = true) {
	if ($relative) {
		$path = get_template_directory() . '/inc/options/' . $path . '.php';
	}

	if (! file_exists($path)) {
		return null;
	}

	/**
	 * Filters the options array loaded from an options file.
	 *
	 * PARITY: the theme fires the same filter from blocksy_get_options()
	 * (admin/helpers/options.php) — keep both in sync.
	 *
	 * @since 1.7.30
	 *
	 * @param array  $options     The resolved options array from the file.
	 * @param string $path        Absolute path to the loaded options file.
	 * @param array  $pass_inside Variables passed into the file scope.
	 */
	return apply_filters('blocksy:options:retrieve', blocksy_akg(
		'options',
		blocksy_companion_get_variables_from_file(
			$path,
			['options' => []],
			$pass_inside
		)
	), $path, $pass_inside);
}

function blocksy_companion_get_json_translation_files($domain) {
	$cached_mofiles = [];

	$locations = [
		WP_LANG_DIR . '/themes',
		WP_LANG_DIR . '/plugins'
	];

	foreach ($locations as $location) {
		$mofiles = glob($location . '/*.json');

		if (! $mofiles) {
			continue;
		}

		$cached_mofiles = array_merge($cached_mofiles, $mofiles);
	}

	$locale = determine_locale();

	$result = [];

	foreach ($cached_mofiles as $single_file) {
		if (strpos($single_file, $locale) === false) {
			continue;
		}

		$result[] = $single_file;
	}

	return $result;
}

/**
 * Log a debug message (always — not gated by WP_DEBUG) and fire the
 * `blocksy:companion:debug-log` action so anything can observe the companion's
 * debug logs.
 *
 * PARITY: mirrored by the theme's blocksy_debug_log() (inc/helpers.php) — keep both
 * in sync.
 *
 * @param string $message The log message.
 * @param mixed  $object  Optional context appended via print_r (e.g. a \Throwable).
 *
 * @return void
 */
function blocksy_companion_debug_log($message, $object = null) {
	/**
	 * Fires for every companion debug log message.
	 *
	 * Lets anything (e.g. the theme or another feature) observe the companion's
	 * debug logs.
	 *
	 * @since 2.1.47
	 *
	 * @param string $message The log message.
	 * @param mixed  $object  Optional context (e.g. a \Throwable). Default null.
	 */
	do_action('blocksy:companion:debug-log', $message, $object);

	if (is_null($object)) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log($message);
	} else {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log($message . ': ' . print_r($object, true));
	}
}

// TODO: maybe automatically expand class
function blocksy_companion_parse_attributes_string($input) {
	$result = [];

	preg_match_all(
		'/([a-zA-Z0-9_-]+)=([\'"])(.*?)\2/',
		$input,
		$matches,
		PREG_SET_ORDER
	);

	foreach ($matches as $match) {
		$result[$match[1]] = $match[3];
	}

	$stripped = preg_replace('/([a-zA-Z0-9_-]+)=([\'"])(.*?)\2/', '', $input);

	$stripped = preg_replace('/([a-zA-Z0-9_-]+)=([^\s]+)/', '', $stripped);

	preg_match_all('/\b([a-zA-Z0-9_-]+)\b/', $stripped, $boolMatches);

	foreach ($boolMatches[1] as $key) {
		if (! array_key_exists($key, $result)) {
			$result[$key] = '';
		}
	}

	return $result;
}
