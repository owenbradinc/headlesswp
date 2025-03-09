<?php
/**
 * CORS handling class.
 *
 * This class handles Cross-Origin Resource Sharing (CORS) functionality.
 *
 * @since      1.0.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * CORS handling class.
 */
class HeadlessWP_CORS {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct($options) {
		$this->options = $options;
	}

	/**
	 * Initialize CORS functionality.
	 */
	public function init() {
		// Enable CORS if enabled
		if (!empty($this->options['enable_cors'])) {
			add_action('rest_api_init', [$this, 'add_cors_support'], 15);
		}
	}

	/**
	 * Add CORS support to the REST API.
	 */
	public function add_cors_support() {
		$allowed_origins = $this->options['allowed_origins'];

		remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
		add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) use ($allowed_origins) {
			$origin = get_http_origin();

			if ($origin && ($allowed_origins === '*' || in_array($origin, explode(',', $allowed_origins)))) {
				header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
				header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
				header('Access-Control-Allow-Credentials: true');
				header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');

				if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
					header('Access-Control-Max-Age: 86400');
					exit;
				}
			}

			return $served;
		}, 10, 4);
	}
}