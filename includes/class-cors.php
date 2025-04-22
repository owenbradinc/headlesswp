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
		if (!empty($this->options['enable_cors'])) {
			// Remove WordPress default CORS headers
			remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
			
			// Add our CORS handling
			add_filter('rest_pre_serve_request', [$this, 'handle_cors_headers'], 10, 4);
			
			// Handle preflight requests
			add_action('rest_api_init', [$this, 'handle_preflight_requests'], 0);
		}
	}

	/**
	 * Handle preflight requests.
	 */
	public function handle_preflight_requests() {
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			$origin = $this->get_request_origin();
			
			if (empty($origin)) {
				$this->send_error_response(400, 'Missing Origin header');
				exit;
			}

			if (!$this->is_origin_allowed($origin)) {
				$this->send_error_response(403, 'Origin not allowed: ' . $origin);
				exit;
			}

			$this->send_cors_headers($origin);
			exit;
		}
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
		$origin = $this->get_request_origin();
		
		if (empty($origin)) {
			$this->send_error_response(400, 'Missing Origin header');
			return true;
		}

		if (!$this->is_origin_allowed($origin)) {
			$this->send_error_response(403, 'Origin not allowed: ' . $origin);
			return true;
		}

		$this->send_cors_headers($origin);
		return $served;
	}

	/**
	 * Get the request origin from headers.
	 *
	 * @return string|null The request origin or null if not found.
	 */
	protected function get_request_origin() {
		// First try to get origin from Origin header
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			return $_SERVER['HTTP_ORIGIN'];
		}
		
		// If no origin, try to get it from the referer
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = parse_url($_SERVER['HTTP_REFERER']);
			if (isset($referer['scheme']) && isset($referer['host'])) {
				$origin = $referer['scheme'] . '://' . $referer['host'];
				if (isset($referer['port'])) {
					$origin .= ':' . $referer['port'];
				}
				return $origin;
			}
		}

		// Check if this is a localhost request
		if (isset($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
			if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
				$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
				return $scheme . '://' . $host;
			}
		}

		return null;
	}

	/**
	 * Send CORS headers.
	 *
	 * @param string $origin The request origin.
	 */
	protected function send_cors_headers($origin) {
		header('Access-Control-Allow-Origin: ' . $origin);
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: Content-Type, X-WP-API-Key, Origin, Accept');
		header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');
	}

	/**
	 * Send an error response.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $message     Error message.
	 */
	protected function send_error_response($status_code, $message) {
		status_header($status_code);
		header('Content-Type: application/json');
		echo json_encode([
			'code' => $status_code,
			'message' => $message,
			'data' => [
				'status' => $status_code,
				'details' => [
					'origin' => $this->get_request_origin(),
					'allowed_origins' => $this->options['cors_origins'],
					'allow_all_origins' => !empty($this->options['allow_all_origins'])
				]
			]
		]);
	}

	/**
	 * Check if an origin is allowed.
	 *
	 * @param string $origin The request origin.
	 * @return bool Whether the origin is allowed.
	 */
	protected function is_origin_allowed($origin) {
		// Always allow localhost origins
		if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
			return true;
		}

		// Allow all origins if enabled
		if (!empty($this->options['allow_all_origins'])) {
			return true;
		}

		// Check against allowed origins list
		$normalized_origin = $this->normalize_origin($origin);
		foreach ($this->options['cors_origins'] as $allowed_origin) {
			if ($this->normalize_origin($allowed_origin) === $normalized_origin) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize an origin for comparison.
	 *
	 * @param string $origin The origin to normalize.
	 * @return string The normalized origin.
	 */
	protected function normalize_origin($origin) {
		$parsed = parse_url($origin);
		$normalized = $parsed['scheme'] . '://' . $parsed['host'];
		if (isset($parsed['port'])) {
			$normalized .= ':' . $parsed['port'];
		}
		return $normalized;
	}
}