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
	 * Logger instance.
	 *
	 * @var HeadlessWP_Logger
	 */
	protected $logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $options Plugin options.
	 * @param HeadlessWP_Logger $logger Logger instance.
	 */
	public function __construct($options, $logger) {
		$this->options = $options;
		$this->logger = $logger;
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
				$this->logger->log(
					'Missing Origin header in preflight request',
					'error',
					['request' => $_SERVER],
					'cors'
				);
				$this->send_error_response(400, 'Missing Origin header');
				exit;
			}

			if (!$this->is_origin_allowed($origin)) {
				$this->logger->log(
					'Origin not allowed in preflight request',
					'warning',
					[
						'origin' => $origin,
						'allowed_origins' => $this->options['cors_origins']
					],
					'cors'
				);
				$this->send_error_response(403, 'Origin not allowed: ' . $origin);
				exit;
			}

			$this->logger->log(
				'Preflight request handled successfully',
				'info',
				[
					'origin' => $origin,
					'method' => $_SERVER['REQUEST_METHOD'],
					'headers' => getallheaders()
				],
				'cors'
			);
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
			$this->logger->log(
				'Missing Origin header in REST API request',
				'error',
				[
					'request' => $_SERVER,
					'route' => $request->get_route()
				],
				'cors'
			);
			$this->send_error_response(400, 'Missing Origin header');
			return true;
		}

		if (!$this->is_origin_allowed($origin)) {
			$this->logger->log(
				'Origin not allowed in REST API request',
				'warning',
				[
					'origin' => $origin,
					'route' => $request->get_route(),
					'allowed_origins' => $this->options['cors_origins']
				],
				'cors'
			);
			$this->send_error_response(403, 'Origin not allowed: ' . $origin);
			return true;
		}

		$this->logger->log(
			'CORS headers added to REST API response',
			'info',
			[
				'origin' => $origin,
				'route' => $request->get_route(),
				'method' => $request->get_method()
			],
			'cors'
		);
		$this->send_cors_headers($origin);
		return $served;
	}

	/**
	 * Get the request origin from headers.
	 *
	 * @return string|null The request origin or null if not found.
	 */
	protected function get_request_origin() {
		$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
		
		if (!$origin && isset($_SERVER['HTTP_REFERER'])) {
			$referer = parse_url($_SERVER['HTTP_REFERER']);
			$origin = $referer['scheme'] . '://' . $referer['host'];
			if (isset($referer['port'])) {
				$origin .= ':' . $referer['port'];
			}
		}

		$this->logger->log(
			'Retrieved request origin',
			'debug',
			[
				'origin' => $origin,
				'headers' => getallheaders()
			],
			'cors'
		);

		return $origin;
	}

	/**
	 * Check if an origin is allowed.
	 *
	 * @param string $origin The origin to check.
	 * @return bool
	 */
	protected function is_origin_allowed($origin) {
		// If allow all origins is enabled, allow all
		if (!empty($this->options['allow_all_origins'])) {
			$this->logger->log(
				'Origin allowed (all origins allowed)',
				'debug',
				['origin' => $origin],
				'cors'
			);
			return true;
		}

		// Get allowed origins from options
		$allowed_origins = !empty($this->options['cors_origins']) ? $this->options['cors_origins'] : [];

		// Check if the origin is in the allowed list
		foreach ($allowed_origins as $allowed) {
			// Normalize origins for comparison
			$normalized_origin = rtrim(preg_replace('(^https?://)', '', $origin), '/');
			$normalized_allowed = rtrim(preg_replace('(^https?://)', '', $allowed['origin']), '/');

			if ($normalized_origin === $normalized_allowed) {
				$this->logger->log(
					'Origin allowed (matches allowed origin)',
					'debug',
					[
						'origin' => $origin,
						'allowed_origin' => $allowed['origin']
					],
					'cors'
				);
				return true;
			}
		}

		$this->logger->log(
			'Origin not allowed',
			'debug',
			[
				'origin' => $origin,
				'allowed_origins' => $allowed_origins
			],
			'cors'
		);
		return false;
	}

	/**
	 * Send CORS headers.
	 *
	 * @param string $origin The allowed origin.
	 */
	protected function send_cors_headers($origin) {
		header('Access-Control-Allow-Origin: ' . $origin);
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: Content-Type, X-WP-API-Key, Authorization');
		header('Access-Control-Max-Age: 86400');

		$this->logger->log(
			'CORS headers sent',
			'debug',
			[
				'origin' => $origin,
				'headers' => headers_list()
			],
			'cors'
		);
	}

	/**
	 * Send an error response.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $message     Error message.
	 */
	protected function send_error_response($status_code, $message) {
		status_header($status_code);
		wp_send_json_error(['message' => $message], $status_code);
	}
}