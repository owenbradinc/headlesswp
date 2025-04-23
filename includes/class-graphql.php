<?php
/**
 * GraphQL functionality for HeadlessWP
 *
 * @package HeadlessWP
 */

namespace HeadlessWP;

/**
 * Class GraphQL
 */
class GraphQL {

	/**
	 * Initialize the GraphQL functionality
	 */
	public function init() {
		// Register custom GraphQL types and fields
		add_action('graphql_register_types', array($this, 'register_graphql_types'));
		
		// Add custom GraphQL queries
		add_action('graphql_register_types', array($this, 'register_graphql_queries'));
		
		// Add custom GraphQL mutations
		add_action('graphql_register_types', array($this, 'register_graphql_mutations'));

		// Add API key authentication for GraphQL requests
		add_filter('graphql_access_control_allow_headers', array($this, 'add_api_key_header'));
		add_filter('graphql_response_headers', array($this, 'add_api_key_header'));
		add_action('graphql_before_resolve_field', array($this, 'check_api_key_authentication'));
	}

	/**
	 * Register custom GraphQL types
	 */
	public function register_graphql_types() {
		// Example: Register a custom type
		register_graphql_object_type('HeadlessWPSettings', [
			'description' => __('HeadlessWP plugin settings', 'headlesswp'),
			'fields' => [
				'disableThemes' => [
					'type' => 'Boolean',
					'description' => __('Whether themes are disabled', 'headlesswp'),
				],
				'disableFrontend' => [
					'type' => 'Boolean',
					'description' => __('Whether frontend is disabled', 'headlesswp'),
				],
			],
		]);
	}

	/**
	 * Register custom GraphQL queries
	 */
	public function register_graphql_queries() {
		// Example: Add a query to get plugin settings
		register_graphql_field('RootQuery', 'headlessWPSettings', [
			'type' => 'HeadlessWPSettings',
			'description' => __('Get HeadlessWP plugin settings', 'headlesswp'),
			'resolve' => function() {
				$options = get_option('headlesswp_options');
				return [
					'disableThemes' => $options['disable_themes'] ?? false,
					'disableFrontend' => $options['disable_frontend'] ?? false,
				];
			},
		]);
	}

	/**
	 * Register custom GraphQL mutations
	 */
	public function register_graphql_mutations() {
		// Example: Add a mutation to update plugin settings
		register_graphql_mutation('updateHeadlessWPSettings', [
			'inputFields' => [
				'disableThemes' => [
					'type' => 'Boolean',
					'description' => __('Whether to disable themes', 'headlesswp'),
				],
				'disableFrontend' => [
					'type' => 'Boolean',
					'description' => __('Whether to disable frontend', 'headlesswp'),
				],
			],
			'outputFields' => [
				'success' => [
					'type' => 'Boolean',
					'description' => __('Whether the settings were updated successfully', 'headlesswp'),
				],
			],
			'mutateAndGetPayload' => function($input) {
				$options = get_option('headlesswp_options');
				$options['disable_themes'] = $input['disableThemes'] ?? $options['disable_themes'];
				$options['disable_frontend'] = $input['disableFrontend'] ?? $options['disable_frontend'];
				
				$success = update_option('headlesswp_options', $options);
				
				return [
					'success' => $success,
				];
			},
		]);
	}

	/**
	 * Add API key header to allowed headers
	 */
	public function add_api_key_header($headers) {
		$headers[] = 'X-WP-API-Key';
		return $headers;
	}

	/**
	 * Check API key authentication for GraphQL requests
	 */
	public function check_api_key_authentication() {
		// Skip authentication check for admin users
		if (is_user_logged_in() && current_user_can('manage_options')) {
			return;
		}

		// Get security options
		$security_options = get_option('headlesswp_security_options', []);
		
		// If API key requirement is enabled
		if (!empty($security_options['require_api_key'])) {
			// Get API key from headers
			$api_key = $this->get_header('HTTP_X_WP_API_KEY');

			// If no API key was provided, block access
			if (empty($api_key)) {
				throw new \GraphQL\Error\UserError(__('API key is required for GraphQL requests.', 'headlesswp'));
			}

			// Verify API key
			$api_auth = new \HeadlessWP_API_Auth([]);
			$key_data = $api_auth->verify_api_key($api_key);

			if (is_wp_error($key_data)) {
				throw new \GraphQL\Error\UserError($key_data->get_error_message());
			}

			// Check if origin is allowed for this API key
			if (!$api_auth->verify_origin_for_key($key_data)) {
				throw new \GraphQL\Error\UserError(__('The origin of this request is not allowed for this API key.', 'headlesswp'));
			}

			// Update last used timestamp
			$api_auth->update_key_last_used($api_key);
		}
	}

	/**
	 * Get header value
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
} 