<?php

namespace Blocksy;

class WooImportExport {
	private $export_type = 'product';
	private $blocksy_column_id = 'blocksy_custom_data';

	private $custom_data = [];

	private static $data_cache = null;

	public function __construct() {
		add_filter(
			"woocommerce_product_export_{$this->export_type}_default_columns",
			[$this, 'export_column_name']
		);

		add_filter(
			'woocommerce_product_export_column_names',
			[$this, 'export_column_name']
		);

		add_filter(
			"woocommerce_product_export_product_column_{$this->blocksy_column_id}",
			[$this, 'export_custom_data'],
			10, 2
		);

		add_filter(
			'woocommerce_csv_product_import_mapping_options',
			[$this, 'export_column_name']
		);

		add_filter(
			'woocommerce_csv_product_import_mapping_default_columns',
			[$this, 'default_import_column_name']
		);

		add_filter('woocommerce_product_export_product_query_args', function($args) {
			if (
				! empty($args['page'])
				&&
				intval($args['page']) === 1
			) {
				$this->custom_data = [];
				delete_option('blocksy_woo_export_data');
			} else {
				$this->custom_data = get_option('blocksy_woo_export_data', []);
			}

			return $args;
		});

		add_filter('woocommerce_product_export_rows', function ($data, $exporter) {
			if (
				$exporter->get_percent_complete() >= 100
				&&
				! empty($data)
			) {
				$this->custom_data = apply_filters(
					'blocksy_woo_product_export:finalize',
					$this->custom_data,
					$exporter
				);

				$pos = strrpos($data, "BLOCKSY_CUSTOM_DATA_PLACEHOLDER");

				if ($pos !== false) {
					$replacement = json_encode($this->custom_data);
					$replacement = '"' . str_replace('"', '""', $replacement) . '"';

					$data = substr_replace(
						$data,
						$replacement,
						$pos,
						strlen("BLOCKSY_CUSTOM_DATA_PLACEHOLDER")
					);
				}
			}

			$data = str_replace("BLOCKSY_CUSTOM_DATA_PLACEHOLDER", '', $data);

			return $data;
		}, 10, 2);
	}

	public function set_custom_data($data) {
		$this->custom_data = $data;
	}

	public function get_custom_data() {
		return $this->custom_data;
	}

	public static function parse_data($data) {
		$cleaned_string = str_replace('\\', '', $data);

		// Convert JSON string to PHP array
		$array = json_decode("[" . $cleaned_string . "]", true);

		return $array;
	}

	public function export_custom_data($value, $product) {
		return 'BLOCKSY_CUSTOM_DATA_PLACEHOLDER';
	}

	public function export_column_name($columns) {
		$columns[$this->blocksy_column_id] = __('Blocksy Custom Data', 'blocksy');

		return $columns;
	}

	public function default_import_column_name($columns) {
		$columns[__('Blocksy Custom Data', 'blocksy')] = $this->blocksy_column_id;

		return $columns;
	}

	public static function get_attachment_id_from_url($url, $product_id) {
		if (empty($url)) {
			return 0;
		}

		$id = 0;
		$upload_dir = wp_upload_dir(null, false);
		$base_url   = $upload_dir['baseurl'] . '/';

		// Check first if attachment is inside the WordPress uploads directory, or we're given a filename only.
		if (false !== strpos($url, $base_url) || false === strpos($url, '://')) {
			// Search for yyyy/mm/slug.extension or slug.extension - remove the base URL.
			$file = str_replace($base_url, '', $url);
			$args = [
				'post_type' => 'attachment',
				'post_status' => 'any',
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'OR',
					[
						'key' => '_wp_attached_file',
						'value' => '^' . $file,
						'compare' => 'REGEXP'
					],
					[
						'key' => '_wp_attached_file',
						'value' => '/' . $file,
						'compare' => 'LIKE',
					],
					[
						'key' => '_wc_attachment_source',
						'value' => '/' . $file,
						'compare' => 'LIKE',
					]
				],
			];
		} else {
			// This is an external URL, so compare to source.
			$args = [
				'post_type' => 'attachment',
				'post_status' => 'any',
				'fields' => 'ids',
				'meta_query' => [
					[
						'value' => $url,
						'key' => '_wc_attachment_source',
					]

				]
			];
		}

		$ids = get_posts($args);

		if ($ids) {
			$id = current($ids);
		}

		// Upload if attachment does not exists.
		if (
			! $id
			&&
			stristr($url, '://')
		) {
			$upload = wc_rest_upload_image_from_url($url);

			if (is_wp_error($upload)) {
				throw new \Exception(
					$upload->get_error_message(),
					400
				);
			}

			$id = wc_rest_set_uploaded_image_as_attachment($upload, $product_id);

			if (! wp_attachment_is_image($id)) {
				throw new \Exception(
					blocksy_safe_sprintf(__('Not able to attach "%s".', 'woocommerce'), $url),
					400
				);
			}

			// Save attachment source for future reference.
			update_post_meta($id, '_wc_attachment_source', $url);
		}

		if (! $id) {
			throw new \Exception(
				blocksy_safe_sprintf(__('Unable to use image "%s".', 'woocommerce'), $url),
				400
			);
		}

		return $id;
	}

	public static function upload_video_from_url($video_url) {
		if (! filter_var($video_url, FILTER_VALIDATE_URL)) {
			return new WP_Error('invalid_url', 'Invalid video URL.');
		}

		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		// Get the video file extension and MIME type
		$file_info = wp_check_filetype(basename($video_url));

		$allowed_mime_types = [
			'video/mp4'  => 'mp4',
			'video/webm' => 'webm',
			'video/ogg'  => 'ogg',
		];

		if (! array_key_exists($file_info['type'], $allowed_mime_types)) {
			return new \WP_Error('file_type_error', 'This file type is not allowed for video uploads.');
		}

		// Download the video from the URL
		$response = wp_remote_get($video_url);

		if (is_wp_error($response)) {
			return new \WP_Error('download_error', 'Failed to download the video from the URL.');
		}

		// Get the video content and temporary file name
		$video_content = wp_remote_retrieve_body($response);
		$temp_file = wp_tempnam($video_url);

		if (! $temp_file) {
			return new \WP_Error('temp_file_error', 'Could not create a temporary file for the video.');
		}

		// Save the downloaded video content to the temporary file
		file_put_contents($temp_file, $video_content);

		// Prepare the file array for WordPress
		$file = [
			'name' => basename($video_url), // Set the file name
			'tmp_name' => $temp_file,
			'error' => 0,
			'size' => filesize($temp_file),
		];

		// Handle the file upload using wp_handle_sideload
		$upload_overrides = ['test_form' => false];
		$uploaded_file = wp_handle_sideload($file, $upload_overrides);

		if (isset($uploaded_file['error'])) {
			return new WP_Error('upload_error', $uploaded_file['error']);
		}

		// Insert the attachment into the media library
		$attachment = [
			'post_mime_type' => $file_info['type'],
			'post_title' => sanitize_file_name($file['name']),
			'post_content'=> '',
			'post_status' => 'inherit'
		];

		$attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);

		if (is_wp_error($attachment_id)) {
			return new \WP_Error('attachment_error', 'There was an error attaching the file.');
		}

		// Generate attachment metadata
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
		wp_update_attachment_metadata($attachment_id, $attachment_data);

		// Clean up the temporary file
		@unlink($temp_file);

		return $attachment_id;
	}

	public static function get_import_file_data() {
		if (!isset($_POST['file'])) {
			return [];
		}

		if (self::$data_cache) {
			return self::$data_cache;
		}

		$file = wc_clean(wp_unslash($_POST['file'] ?? ''));

		if (!file_exists($file)) {
			return [];
		}

		$data = self::get_header_and_last_line($file);

		if (!$data || empty($data['last_line']) || empty($data['header'])) {
			return [];
		}

		$delimiter = !empty($_POST['delimiter'])
			? wc_clean(wp_unslash($_POST['delimiter']))
			: ',';

		$header_columns = str_getcsv($data['header'], $delimiter);

		if (!$header_columns) {
			return [];
		}

		$columns_map = array_flip($header_columns);

		// find Blocksy Custom Data in $columns_map
		$index_of_custom_data = array_search('Blocksy Custom Data', $header_columns, true);

		if ($index_of_custom_data === false) {
			return [];
		}

		$columns = str_getcsv($data['last_line'], $delimiter);

		if (!isset($columns[$index_of_custom_data])) {
			return [];
		}

		$value = $columns[$index_of_custom_data];

		$parsed_data = self::parse_data($value)[0] ?? null;

		if (!$parsed_data) {
			return [];
		}

		self::$data_cache = $parsed_data;

		return $parsed_data;
	}

	private static function get_header_and_last_line($file) {
		$fh = fopen($file, 'r');
		if (!$fh) {
			return false;
		}

		$header = fgets($fh);

		$cursor = -1;
		$line = '';

		fseek($fh, $cursor, SEEK_END);
		$char = fgetc($fh);

		while ($char === "\n" || $char === "\r") {
			fseek($fh, $cursor--, SEEK_END);
			$char = fgetc($fh);
		}

		while ($char !== false && $char !== "\n") {
			$line = $char . $line;
			fseek($fh, $cursor--, SEEK_END);
			$char = fgetc($fh);
		}

		fclose($fh);

		return [
			'header'    => $header,
			'last_line' => $line
		];
	}

	public static function implode_values($values) {
		$values_to_implode = [];

		foreach ($values as $value) {
			$value = (string) is_scalar($value) ? html_entity_decode($value, ENT_QUOTES) : '';
			$values_to_implode[] = str_replace(',', '\\,', $value);
		}

		return implode(', ', $values_to_implode);
	}
}

