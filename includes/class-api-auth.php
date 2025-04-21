<?php
/**
 * API Authentication class.
 *
 * This class handles API key authentication for the REST API.
 *
 * @since      0.1.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * API Authentication class.
 */
class HeadlessWP_API_Auth {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * The current API key if authenticated.
	 *
	 * @var array|null
	 */
	protected $current_api_key = null;

	/**
	 * The API keys handler instance.
	 *
	 * @var HeadlessWP_API_Keys
	 */
	protected $api_keys;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct($options) {
		$this->options = $options;
		$this->api_keys = new HeadlessWP_API_Keys();
	}

	/**
	 * Initialize authentication functionality.
	 */
	public function init() {
		// Add our authentication filter
		add_filter('rest_authentication_errors', [$this, 'authenticate_api_key'], 1);
	}

	/**
	 * Disable REST API for non-admin users.
	 *
	 * @param WP_Error|null|bool $result Error from another authentication handler,
	 *                                    null if no handler has been applied, true if authentication succeeded.
	 * @return WP_Error|null|bool
	 */
	public function disable_rest_api($result) {
		// If there is already an error, just return it
		if (is_wp_error($result)) {
			return $result;
		}

		// If this is an admin request, allow it through
		if (is_user_logged_in() && current_user_can('manage_options')) {
			return $result;
		}

		// Return WP_Error object to disable REST API for everyone else
		return new WP_Error(
			'rest_disabled',
			__('REST API is disabled.', 'headlesswp'),
			['status' => 401]
		);
	}

	/**
	 * Authenticate API key for REST API requests.
	 *
	 * @param WP_Error|null|bool $result Error from another authentication handler,
	 *                                    null if no handler has been applied, true if authentication succeeded.
	 * @return WP_Error|null|bool
	 */
	public function authenticate_api_key($result) {
		// If there is already an error, just return it
		if (is_wp_error($result)) {
			return $result;
		}

		// If this is an admin request, allow it through
		if (is_user_logged_in() && current_user_can('manage_options')) {
			return $result;
		}

		// Check for API key in headers
		$api_key = $this->get_header('HTTP_X_WP_API_KEY');

		// If no API key was provided, block access
		if (empty($api_key)) {
			return new WP_Error(
				'rest_disabled',
				__('REST API is disabled. API key is required.', 'headlesswp'),
				['status' => 401]
			);
		}

		// Verify API key
		$key_data = $this->verify_api_key($api_key);

		if (is_wp_error($key_data)) {
			return $key_data;
		}

		// Check if origin is allowed for this API key
		if (!$this->verify_origin_for_key($key_data)) {
			return new WP_Error(
				'rest_forbidden_origin',
				__('The origin of this request is not allowed for this API key.', 'headlesswp'),
				['status' => 403]
			);
		}

		// Store the current API key for later use
		$this->current_api_key = $key_data;

		// Update last used timestamp
		$this->update_key_last_used($api_key);

		// Check role-based permissions
		$user = wp_get_current_user();
		if ($user->exists()) {
			// If user is logged in, check their role permissions
			if (!$this->check_user_role_permissions($user, $key_data)) {
				return new WP_Error(
					'rest_forbidden_role',
					__('Your user role does not have permission to access this endpoint.', 'headlesswp'),
					['status' => 403]
				);
			}
		}

		return true;
	}

	/**
	 * Check API key permissions for API requests.
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function check_api_permissions($result, $server, $request) {
		// If already handled or no current API key, pass through
		if (null !== $result || null === $this->current_api_key) {
			return $result;
		}

		// Get the request method
		$method = $request->get_method();

		// Check permissions based on the API key's permission level
		$permission_level = $this->current_api_key['permissions'];

		// If 'read' permission and trying to do write operations, block
		if ($permission_level === 'read' && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
			return new WP_Error(
				'rest_forbidden_method',
				__('This API key does not have permission to perform this action.', 'headlesswp'),
				['status' => 403]
			);
		}

		// If 'write' permission and trying to do admin operations, block
		// This is a simplified example - you might want to add more specific checks
		if ($permission_level === 'write' && $this->is_admin_endpoint($request->get_route())) {
			return new WP_Error(
				'rest_forbidden_endpoint',
				__('This API key does not have permission to access this endpoint.', 'headlesswp'),
				['status' => 403]
			);
		}

		// Permission granted, continue with the request
		return $result;
	}

	/**
	 * Add API key info to the response headers.
	 *
	 * @param WP_REST_Response $result  Response object.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return WP_REST_Response
	 */
	public function add_api_info_headers($result, $server, $request) {
		if (null !== $this->current_api_key) {
			$result->header('X-WP-API-Key-Name', $this->current_api_key['name']);
			$result->header('X-WP-API-Key-Permissions', $this->current_api_key['permissions']);
		}

		return $result;
	}

	/**
	 * Get header value.
	 *
	 * @param string $header Header name.
	 * @return string|null
	 */
	protected function get_header($header) {
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
			// Convert to uppercase for comparison
			$header_mapped = str_replace('HTTP_', '', $header);
			$header_mapped = str_replace('_', '-', $header_mapped);

			// Try both formats
			if (isset($headers[$header_mapped])) {
				return $headers[$header_mapped];
			}

			// Try case-insensitive search
			foreach ($headers as $key => $value) {
				if (strtolower($key) === strtolower($header_mapped)) {
					return $value;
				}
			}
		}

		// Fallback to $_SERVER
		if (isset($_SERVER[$header])) {
			return $_SERVER[$header];
		}

		return null;
	}

	/**
	 * Verify API key.
	 *
	 * @param string $api_key API key.
	 * @return array|WP_Error API key data or error.
	 */
	protected function verify_api_key($api_key) {
		return $this->api_keys->verify_key($api_key);
	}

	/**
	 * Update the "last used" timestamp for an API key.
	 *
	 * @param string $api_key API key.
	 */
	protected function update_key_last_used($api_key) {
		$key_data = $this->verify_api_key($api_key);
		if (!is_wp_error($key_data)) {
			$this->api_keys->update_last_used($key_data['id']);
		}
	}

	/**
	 * Verify if the origin is allowed for this API key.
	 *
	 * @param array $key_data API key data.
	 * @return bool
	 */
	protected function verify_origin_for_key($key_data) {
		// If no origins specified for the key, allow all allowed origins
		if (empty($key_data['origins'])) {
			return true;
		}

		// Get the request origin
		$origin = $this->get_header('HTTP_ORIGIN');
		if (empty($origin)) {
			// If no origin header, allow the request (might be a server-to-server API call)
			return true;
		}

		// If allow all origins is enabled, allow all
		if (!empty($this->options['allow_all_origins'])) {
			return true;
		}

		// Get allowed origins from options
		$allowed_origins = !empty($this->options['cors_origins']) ? $this->options['cors_origins'] : [];

		// Check if the origin is in the allowed list for this key
		foreach ($allowed_origins as $allowed) {
			if (in_array($allowed['id'], $key_data['origins'])) {
				// Normalize origins for comparison
				$normalized_origin = rtrim(preg_replace('(^https?://)', '', $origin), '/');
				$normalized_allowed = rtrim(preg_replace('(^https?://)', '', $allowed['origin']), '/');

				if ($normalized_origin === $normalized_allowed) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the endpoint is an admin endpoint.
	 *
	 * @param string $route API route.
	 * @return bool
	 */
	protected function is_admin_endpoint($route) {
		// Example: consider all settings endpoints as admin
		$admin_routes = [
			'/wp/v2/settings',
			'/wp/v2/users',
			'/wp/v2/plugins',
			'/wp/v2/themes',
		];

		foreach ($admin_routes as $admin_route) {
			if (strpos($route, $admin_route) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the user's role has permission to access the requested endpoint.
	 *
	 * @param WP_User $user The current user.
	 * @param array $key_data The API key data.
	 * @return bool
	 */
	protected function check_user_role_permissions($user, $key_data) {
		// Get the current route
		$route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$route = str_replace('/wp-json/', '', $route);

		// Define role-based permissions
		$role_permissions = [
			'administrator' => ['*'], // Access to all endpoints
			'editor' => [
				'wp/v2/posts',
				'wp/v2/pages',
				'wp/v2/media',
				'wp/v2/categories',
				'wp/v2/tags'
			],
			'author' => [
				'wp/v2/posts',
				'wp/v2/media'
			],
			'contributor' => [
				'wp/v2/posts'
			]
		];

		// Check if the user has any of the allowed roles
		foreach ($user->roles as $role) {
			if (isset($role_permissions[$role])) {
				// If the role has access to all endpoints
				if (in_array('*', $role_permissions[$role])) {
					return true;
				}

				// Check if the current route is allowed for this role
				foreach ($role_permissions[$role] as $allowed_route) {
					if (strpos($route, $allowed_route) === 0) {
						return true;
					}
				}
			}
		}

		return false;
	}
}