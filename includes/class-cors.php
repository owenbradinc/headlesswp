<?php
/**
 * CORS handling class.
 *
 * This class handles Cross-Origin Resource Sharing (CORS) functionality.
 *
 * @since      0.1.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * CORS handling class.
 */
class HeadlessWP_Security {

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
		remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
		add_filter('rest_pre_serve_request', [$this, 'handle_cors_headers'], 10, 4);
	}

	/**
	 * Handle CORS headers for REST API requests.
	 *
	 * @param bool           $served  Whether the request has already been served.
	 * @param WP_REST_Response $result  Result to send to the client.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @param WP_REST_Server   $server  Server instance.
	 * @return bool Whether the request has already been served.
	 */
	public function handle_cors_headers($served, $result, $request, $server) {
		$origin = get_http_origin();

		// Debug logging
		error_log('HeadlessWP CORS Debug:');
		error_log('Origin: ' . ($origin ? $origin : 'none'));
		error_log('Options: ' . print_r($this->options, true));
		error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);

		if (!$origin) {
			error_log('HeadlessWP CORS: No origin found, returning served');
			return $served;
		}

		// Get allowed origins from options
		$allowed_origins = !empty($this->options['cors_origins']) ? $this->options['cors_origins'] : [];
		$all_origins_allowed = !empty($this->options['allow_all_origins']);

		// Debug logging
		error_log('All origins allowed: ' . ($all_origins_allowed ? 'yes' : 'no'));
		error_log('Allowed origins: ' . print_r($allowed_origins, true));

		// Check if the origin is allowed
		$origin_is_allowed = $all_origins_allowed || $this->is_origin_allowed($origin, $allowed_origins);
		error_log('Origin is allowed: ' . ($origin_is_allowed ? 'yes' : 'no'));

		if ($origin_is_allowed) {
			// If all origins are allowed, use the wildcard
			if ($all_origins_allowed) {
				header('Access-Control-Allow-Origin: *');
				error_log('HeadlessWP CORS: Setting Access-Control-Allow-Origin: *');
			} else {
				header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
				error_log('HeadlessWP CORS: Setting Access-Control-Allow-Origin: ' . esc_url_raw($origin));
			}
			header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-WP-API-Key');
			header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');

			if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
				status_header(200);
				header('Access-Control-Max-Age: 86400');
				error_log('HeadlessWP CORS: Handling OPTIONS request');
				exit;
			}
		} else {
			error_log('HeadlessWP CORS: Origin not allowed');
		}

		return $served;
	}

	/**
	 * Check if the origin is in the list of allowed origins.
	 *
	 * @param string $origin The origin to check.
	 * @param array $allowed_origins List of allowed origins.
	 * @return bool Whether the origin is allowed.
	 */
	private function is_origin_allowed($origin, $allowed_origins) {
		if (empty($allowed_origins)) {
			return false;
		}

		// Remove protocol and trailing slash for comparison
		$normalized_origin = rtrim(preg_replace('(^https?://)', '', $origin), '/');

		foreach ($allowed_origins as $allowed_origin) {
			// Normalize the allowed origin for comparison
			$normalized_allowed = rtrim(preg_replace('(^https?://)', '', $allowed_origin['origin']), '/');

			if ($normalized_origin === $normalized_allowed) {
				return true;
			}
		}

		return false;
	}
}