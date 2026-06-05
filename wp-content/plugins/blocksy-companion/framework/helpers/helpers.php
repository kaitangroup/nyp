<?php

if (! defined('ABSPATH')) {
	exit;
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
	if (! function_exists('get_plugin_data')) {
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	// Prevent early translation call by setting $translate to false.
	$plugin_data = get_plugin_data(
		BLOCKSY__FILE__,
		false,
		/* $translate */ false
	);

	return $plugin_data['Version'];
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

function blocksy_companion_get_variables_from_file(
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

function blocksy_companion_debug_log($message, $object = null) {
	if (
		! defined('WP_DEBUG')
		||
		! WP_DEBUG
	) {
		return;
	}

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
