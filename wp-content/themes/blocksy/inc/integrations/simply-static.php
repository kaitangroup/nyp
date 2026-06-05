<?php

add_action(
	'ss_after_extract_and_replace_urls_in_html',
	function ($html_content, $url_extractor) {
		if ($html_content instanceof \DOMDocument) {
			$html_content = $html_content->saveHTML();
		}

		$pattern = '/<script[^>]*id=[\'"]ct-scripts-js-extra[\'"][^>]*>(.*?)<\/script>/is';

		$blocksy_scripts = [];

		if (preg_match_all($pattern, $html_content, $matches)) {
			foreach ($matches[0] as $match) {
				$script_content = preg_replace('/<script[^>]*id=[\'"]ct-scripts-js-extra[\'"][^>]*>/', '', $match);
				$script_content = preg_replace('/<\/script>/', '', $script_content);

				$blocksy_scripts[] = $script_content;
			}
		}

		foreach ($blocksy_scripts as $single_script) {
			$content = $single_script;

			$all_components = explode('};', $content);

			$ct_localizations = str_replace(
				'var ct_localizations = ', '',
				array_shift($all_components)
			) . '}';

			$decoded = json_decode($ct_localizations, true);

			$decoded['ajax_url'] = $url_extractor->add_to_extracted_urls(
				$decoded['ajax_url']
			);

			$decoded['public_url'] = str_replace(
				'index.html',
				'',
				$url_extractor->add_to_extracted_urls($decoded['public_url'])
			);

			$decoded['rest_url'] = $url_extractor->add_to_extracted_urls(
				$decoded['rest_url']
			);

			$decoded['dynamic_styles']['lazy_load'] = $url_extractor->add_to_extracted_urls(
				$decoded['dynamic_styles']['lazy_load']
			);

			$decoded['dynamic_styles']['search_lazy'] = $url_extractor->add_to_extracted_urls(
				$decoded['dynamic_styles']['search_lazy']
			);

			foreach ($decoded['dynamic_js_chunks'] as $index => $single_chunk) {
				$decoded['dynamic_js_chunks'][$index]['url'] = $url_extractor
					->add_to_extracted_urls(
						$decoded['dynamic_js_chunks'][$index]['url']
					);
			}

			foreach ($decoded['dynamic_styles_selectors'] as $index => $single_chunk) {
				$decoded['dynamic_styles_selectors'][$index]['url'] = $url_extractor
					->add_to_extracted_urls(
						$decoded['dynamic_styles_selectors'][$index]['url']
					);
			}

			$decoded['dynamic_styles_selectors'][0]['url'] = $url_extractor
				->add_to_extracted_urls(
					$decoded['dynamic_styles_selectors'][0]['url']
				);

			$result = 'var ct_localizations = ' . json_encode($decoded, JSON_UNESCAPED_UNICODE) . ';' .  implode(
				'};',
				$all_components
			);

			$single_script = $result;
		}
	},
	10, 2
);

// old version
// add_action(
// 	'ss_after_setup_task',
// 	function () {
// 		\Simply_Static\Setup_Task::add_additional_files_to_db(
// 			get_template_directory() . '/static/bundle'
// 		);

// 		if (defined('BLOCKSY_PATH')) {
// 			\Simply_Static\Setup_Task::add_additional_files_to_db(
// 				BLOCKSY_PATH . '/static/bundle'
// 			);

// 			\Simply_Static\Setup_Task::add_additional_files_to_db(
// 				BLOCKSY_PATH . '/framework/premium/static/bundle'
// 			);
// 		}
// 	}
// );

// new version
add_filter(
	'ss_additional_files',
	function ($additional_files) {
		$additional_files[] = get_template_directory() . '/static/bundle';

		if (defined('BLOCKSY_PATH')) {
			$additional_files[] = BLOCKSY_PATH . '/static/bundle';
			$additional_files[] = BLOCKSY_PATH . '/framework/premium/static/bundle';
		}

		return $additional_files;
	}
);


