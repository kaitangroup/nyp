<?php

namespace Blocksy\Editor\Blocks;

/**
 * Adapter class that wraps WP_Term_Query to provide WP_Query-like
 * pagination properties (found_posts, max_num_pages, paged).
 *
 * This allows term queries to work with pagination functions that
 * expect a WP_Query-compatible interface.
 */
class TermQueryPaginationAdapter {
	/**
	 * Total number of terms found (without pagination limits).
	 *
	 * @var int
	 */
	public $found_posts = 0;

	/**
	 * Maximum number of pages.
	 *
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * The terms returned by the query.
	 *
	 * @var array
	 */
	public $terms = [];

	/**
	 * Query variables.
	 *
	 * @var array
	 */
	private $query_vars = [];

	/**
	 * Constructor.
	 *
	 * @param array $args Query arguments compatible with get_terms().
	 * @param int $current_page Current page number.
	 */
	public function __construct($args = [], $current_page = 1) {
		$this->query_vars = wp_parse_args($args, [
			'taxonomy' => '',
			'number' => 0,
			'offset' => 0,
		]);

		$this->query_vars['paged'] = max(1, intval($current_page));

		$this->query();
	}

	/**
	 * Execute the term query and calculate pagination values.
	 */
	private function query() {
		$args = $this->query_vars;

		$per_page = isset($args['number']) ? intval($args['number']) : 0;
		$paged = $this->query_vars['paged'];

		// First, get the total count (without limit/offset)
		$count_args = $args;
		$count_args['fields'] = 'count';
		$count_args['number'] = 0;
		$count_args['offset'] = 0;

		$this->found_posts = intval(get_terms($count_args));

		// Calculate max pages
		if ($per_page > 0 && $this->found_posts > 0) {
			$this->max_num_pages = ceil($this->found_posts / $per_page);
		} else {
			$this->max_num_pages = 1;
		}

		// The offset is already calculated in get_term_query_args(),
		// so we just get the terms with the provided args
		$this->terms = get_terms($args);

		if (is_wp_error($this->terms)) {
			$this->terms = [];
		}
	}

	/**
	 * Get a query variable value.
	 * Mimics WP_Query::get() method.
	 *
	 * Maps WP_Query parameter names to WP_Term_Query equivalents:
	 * - posts_per_page -> number
	 *
	 * @param string $key Query variable key.
	 * @param mixed $default Default value if key doesn't exist.
	 * @return mixed
	 */
	public function get($key, $default = '') {
		// Map WP_Query parameter names to term query equivalents
		if ($key === 'posts_per_page') {
			$key = 'number';
		}

		if (isset($this->query_vars[$key])) {
			return $this->query_vars[$key];
		}

		return $default;
	}

	/**
	 * Set a query variable.
	 * Mimics WP_Query::set() method.
	 *
	 * @param string $key Query variable key.
	 * @param mixed $value Value to set.
	 */
	public function set($key, $value) {
		$this->query_vars[$key] = $value;
	}

	/**
	 * Check if there are terms.
	 *
	 * @return bool
	 */
	public function have_posts() {
		return ! empty($this->terms);
	}

	/**
	 * Get the terms.
	 *
	 * @return array
	 */
	public function get_terms() {
		return $this->terms;
	}
}
