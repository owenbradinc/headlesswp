<?php
/**
 * Frontend functionality class.
 *
 * This class handles frontend modifications for headless mode.
 *
 * @since      0.1.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Frontend functionality class.
 */
class HeadlessWP_Frontend {

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
	 * Initialize frontend functionality.
	 */
	public function init() {
		// Apply headless functionality based on settings
		$this->apply_headless_functionality();
	}

	/**
	 * Apply headless functionality based on settings.
	 */
	private function apply_headless_functionality() {
		// Disable frontend if enabled
		if (!empty($this->options['disable_frontend'])) {
			add_action('template_redirect', [$this, 'redirect_frontend_to_api']);
		}
	}

	/**
	 * Redirect frontend requests to the REST API.
	 */
	public function redirect_frontend_to_api() {
		// Don't redirect admin or REST API requests
		if (is_admin() || defined('REST_REQUEST')) {
			return;
		}

		// Don't redirect if it's an API request
		if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
			return;
		}

		// Get redirect settings
		$redirect_type = isset($this->options['redirect_url']) ? $this->options['redirect_url'] : 'api';
		$custom_redirect_url = isset($this->options['custom_redirect_url']) ? $this->options['custom_redirect_url'] : '';

		// Determine redirect URL
		if ($redirect_type === 'custom' && !empty($custom_redirect_url)) {
			$redirect_url = $custom_redirect_url;
		} else {
			// Get API URL structure
			$api_structure = isset($this->options['api_url_structure']) ? $this->options['api_url_structure'] : 'wp/v2';
			$redirect_url = rest_url($api_structure);
		}

		// Redirect to the selected URL
		wp_redirect($redirect_url);
		exit;
	}
}