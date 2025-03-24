<?php
/**
 * Extensions management class.
 *
 * This class handles extensions management for the HeadlessWP plugin.
 *
 * @since      1.0.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Extensions management class.
 */
class HeadlessWP_Extensions {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Available extensions.
	 *
	 * @var array
	 */
	protected $extensions;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct($options) {
		$this->options = $options;
		$this->load_extensions();
	}

	/**
	 * Initialize extensions functionality.
	 */
	public function init() {
		// Handle extension activation and deactivation
		add_action('admin_init', [$this, 'handle_extension_actions']);
	}

	/**
	 * Load available extensions.
	 */
	private function load_extensions() {
		// Define available extensions
		$this->extensions = array(
			'custom-endpoints' => array(
				'name' => __('Custom Endpoints', 'headlesswp'),
				'description' => __('Create and manage custom REST API endpoints without writing code.', 'headlesswp'),
				'icon' => 'dashicons-rest-api',
				'version' => '1.0.0',
				'author' => 'HeadlessWP',
				'premium' => false,
				'docs_url' => 'https://headlesswp.net/docs/extensions/custom-endpoints',
				'status' => isset($this->options['extensions']['custom-endpoints']) ? $this->options['extensions']['custom-endpoints'] : 'inactive',
			),
			'webhook-notifier' => array(
				'name' => __('Webhook Notifier', 'headlesswp'),
				'description' => __('Send webhook notifications to your frontend when content changes in WordPress.', 'headlesswp'),
				'icon' => 'dashicons-share-alt',
				'version' => '1.0.0',
				'author' => 'HeadlessWP',
				'premium' => false,
				'docs_url' => 'https://headlesswp.net/docs/extensions/webhook-notifier',
				'status' => isset($this->options['extensions']['webhook-notifier']) ? $this->options['extensions']['webhook-notifier'] : 'inactive',
			),
			'api-cache' => array(
				'name' => __('API Cache', 'headlesswp'),
				'description' => __('Cache REST API responses for improved performance.', 'headlesswp'),
				'icon' => 'dashicons-performance',
				'version' => '1.0.0',
				'author' => 'HeadlessWP',
				'premium' => false,
				'docs_url' => 'https://headlesswp.net/docs/extensions/api-cache',
				'status' => isset($this->options['extensions']['api-cache']) ? $this->options['extensions']['api-cache'] : 'inactive',
			),
			'access-control' => array(
				'name' => __('Advanced Access Control', 'headlesswp'),
				'description' => __('Fine-grained access control for API endpoints based on roles and permissions.', 'headlesswp'),
				'icon' => 'dashicons-lock',
				'version' => '1.1.0',
				'author' => 'HeadlessWP Pro',
				'premium' => true,
				'docs_url' => 'https://headlesswp.net/docs/premium/access-control',
				'status' => 'premium',
			),
			'media-optimizer' => array(
				'name' => __('Media Optimizer', 'headlesswp'),
				'description' => __('Optimize and transform media on-the-fly for your headless frontend.', 'headlesswp'),
				'icon' => 'dashicons-format-image',
				'version' => '1.2.0',
				'author' => 'HeadlessWP Pro',
				'premium' => true,
				'docs_url' => 'https://headlesswp.net/docs/premium/media-optimizer',
				'status' => 'premium',
			),
			'analytics' => array(
				'name' => __('API Analytics', 'headlesswp'),
				'description' => __('Track and analyze API usage, performance, and errors.', 'headlesswp'),
				'icon' => 'dashicons-chart-bar',
				'version' => '1.0.0',
				'author' => 'HeadlessWP Pro',
				'premium' => true,
				'docs_url' => 'https://headlesswp.net/docs/premium/analytics',
				'status' => 'premium',
			),
		);
	}

	/**
	 * Get all available extensions.
	 *
	 * @return array
	 */
	public function get_extensions() {
		return $this->extensions;
	}

	/**
	 * Get active extensions.
	 *
	 * @return array
	 */
	public function get_active_extensions() {
		$active = array();
		foreach ($this->extensions as $id => $extension) {
			if ($extension['status'] === 'active') {
				$active[$id] = $extension;
			}
		}
		return $active;
	}

	/**
	 * Check if an extension is active.
	 *
	 * @param string $extension_id Extension ID.
	 * @return bool
	 */
	public function is_active($extension_id) {
		return isset($this->extensions[$extension_id]) && $this->extensions[$extension_id]['status'] === 'active';
	}

	/**
	 * Handle extension activation and deactivation.
	 */
	public function handle_extension_actions() {
		if (!isset($_GET['page']) || $_GET['page'] !== 'headlesswp-extensions') {
			return;
		}

		if (!current_user_can('manage_options')) {
			return;
		}

		// Handle extension activation
		if (isset($_GET['activate']) && isset($_GET['_wpnonce'])) {
			$extension_id = sanitize_text_field($_GET['activate']);

			// Verify nonce
			if (!wp_verify_nonce($_GET['_wpnonce'], 'activate-extension-' . $extension_id)) {
				wp_die(__('Security check failed. Please try again.', 'headlesswp'));
			}

			// Check if extension exists and is not already active
			if (isset($this->extensions[$extension_id]) &&
			    $this->extensions[$extension_id]['status'] !== 'active' &&
			    !$this->extensions[$extension_id]['premium']) {

				// Update extension status
				$this->options['extensions'][$extension_id] = 'active';
				update_option('headlesswp_options', $this->options);

				// Redirect to remove query args
				wp_redirect(admin_url('admin.php?page=headlesswp-extensions&activated=true'));
				exit;
			}
		}

		// Handle extension deactivation
		if (isset($_GET['deactivate']) && isset($_GET['_wpnonce'])) {
			$extension_id = sanitize_text_field($_GET['deactivate']);

			// Verify nonce
			if (!wp_verify_nonce($_GET['_wpnonce'], 'deactivate-extension-' . $extension_id)) {
				wp_die(__('Security check failed. Please try again.', 'headlesswp'));
			}

			// Check if extension exists and is active
			if (isset($this->extensions[$extension_id]) && $this->extensions[$extension_id]['status'] === 'active') {
				// Update extension status
				$this->options['extensions'][$extension_id] = 'inactive';
				update_option('headlesswp_options', $this->options);

				// Redirect to remove query args
				wp_redirect(admin_url('admin.php?page=headlesswp-extensions&deactivated=true'));
				exit;
			}
		}
	}
}