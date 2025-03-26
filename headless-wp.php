<?php
/**
 * Plugin Name: HeadlessWP
 * Plugin URI: https://headlesswp.net
 * Description: Turn your WordPress site into a headless CMS with API endpoint and keys management and more.
 * Version: 0.1.0
 * Author: Weekend Labs
 * Author URI: https://weekendlabs.net
 * Text Domain: headlesswp
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('HEADLESSWP_VERSION', '1.0.0');
define('HEADLESSWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HEADLESSWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HEADLESSWP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_headlesswp() {
	// Initialize default options if they don't exist
	if (!get_option('headlesswp_options')) {
		update_option('headlesswp_options', [
			'disable_themes' => false,
			'disable_frontend' => false,
			'enable_cors' => true,
			'allow_all_origins' => false,
			'cors_origins' => [],
			'custom_endpoints' => [],
			'api_keys' => []
		]);
	} else {
		// If options exist but api_keys is missing, add it
		$options = get_option('headlesswp_options');
		if (!isset($options['api_keys'])) {
			$options['api_keys'] = [];
			update_option('headlesswp_options', $options);
		}
	}

	// Create required directories
	$assets_dir = HEADLESSWP_PLUGIN_DIR . 'admin/assets';
	if (!file_exists($assets_dir)) {
		wp_mkdir_p($assets_dir);
	}

	$css_dir = $assets_dir . '/css';
	if (!file_exists($css_dir)) {
		wp_mkdir_p($css_dir);
	}
}
register_activation_hook(__FILE__, 'activate_headlesswp');

/**
 * Redirect to setup page after activation.
 */
function headlesswp_activation_redirect() {
	// Check if we should redirect
	if (get_transient('headlesswp_activation_redirect')) {
		// Delete the transient
		delete_transient('headlesswp_activation_redirect');

		// Only redirect if we're activating the plugin (not on bulk activation)
		if (!isset($_GET['activate-multi']) && isset($_GET['activate'])) {
			wp_redirect(admin_url('admin.php?page=headlesswp-setup'));
			exit;
		}
	}
}
add_action('admin_init', 'headlesswp_activation_redirect');

/**
 * Load plugin dependencies.
 */
require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-headlesswp.php';

/**
 * Begins execution of the plugin.
 */
function run_headlesswp() {
	$plugin = new HeadlessWP();
	$plugin->run();
}
run_headlesswp();