<?php
/**
 * REST API functionality class.
 *
 * This class handles REST API functionality and custom endpoints.
 *
 * @since      1.0.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * REST API functionality class.
 */
class HeadlessWP_API {

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
	 * Initialize API functionality.
	 */
	public function init() {
		// Add REST API endpoints
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	/**
	 * Register custom REST API routes.
	 */
	public function register_rest_routes() {
		// Register a route to get plugin settings
		register_rest_route('headlesswp/v1', '/settings', [
			'methods' => 'GET',
			'callback' => [$this, 'get_plugin_settings'],
			'permission_callback' => function () {
				return current_user_can('manage_options');
			}
		]);

		// Register a route to get all content types
		register_rest_route('headlesswp/v1', '/content-types', [
			'methods' => 'GET',
			'callback' => [$this, 'get_content_types'],
			'permission_callback' => '__return_true'
		]);

		// Register a route to get site info
		register_rest_route('headlesswp/v1', '/site-info', [
			'methods' => 'GET',
			'callback' => [$this, 'get_site_info'],
			'permission_callback' => '__return_true'
		]);
	}

	/**
	 * Get plugin settings for the REST API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_plugin_settings() {
		return rest_ensure_response([
			'version' => HEADLESSWP_VERSION,
			'settings' => $this->options
		]);
	}

	/**
	 * Get all content types for the REST API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_content_types() {
		$post_types = get_post_types(['show_in_rest' => true], 'objects');
		$taxonomies = get_taxonomies(['show_in_rest' => true], 'objects');

		$data = [
			'post_types' => [],
			'taxonomies' => []
		];

		foreach ($post_types as $post_type) {
			$data['post_types'][] = [
				'name' => $post_type->name,
				'label' => $post_type->label,
				'rest_base' => $post_type->rest_base ?? $post_type->name,
				'endpoint' => rest_url('wp/v2/' . ($post_type->rest_base ?? $post_type->name))
			];
		}

		foreach ($taxonomies as $taxonomy) {
			$data['taxonomies'][] = [
				'name' => $taxonomy->name,
				'label' => $taxonomy->label,
				'rest_base' => $taxonomy->rest_base ?? $taxonomy->name,
				'endpoint' => rest_url('wp/v2/' . ($taxonomy->rest_base ?? $taxonomy->name))
			];
		}

		return rest_ensure_response($data);
	}

	/**
	 * Get site information for the REST API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_site_info() {
		return rest_ensure_response([
			'name' => get_bloginfo('name'),
			'description' => get_bloginfo('description'),
			'url' => get_bloginfo('url'),
			'admin_email' => get_bloginfo('admin_email'),
			'language' => get_bloginfo('language'),
			'version' => get_bloginfo('version'),
			'rest_url' => rest_url(),
			'headless' => true
		]);
	}
}