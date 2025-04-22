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
define('HEADLESSWP_VERSION', '0.1.0');
define('HEADLESSWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HEADLESSWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HEADLESSWP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_headlesswp() {
	// Initialize default options if they don't exist
	$current_options = get_option('headlesswp_options');
	$current_security_options = get_option('headlesswp_security_options');
	
	if ($current_options === false) {
		// No options exist, create new ones
		$default_options = array(
			'disable_themes' => false,
			'disable_frontend' => false,
			'custom_endpoints' => array(),
			'openapi' => [
				'enable_try_it' => true,
				'enable_callback_discovery' => true
			]
		);
		update_option('headlesswp_options', $default_options);
	}

	if ($current_security_options === false) {
		// No security options exist, create new ones
		$default_security_options = array(
			'enable_cors' => true,
			'allow_all_origins' => false,
			'cors_origins' => array()
		);
		update_option('headlesswp_security_options', $default_security_options);
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

	// Create API keys table
	require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-api-keys.php';
	$api_keys = new HeadlessWP_API_Keys();
	$api_keys->create_table();
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
require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-graphql.php';

/**
 * Initialize GraphQL functionality
 */
function init_headlesswp_graphql() {
	if (class_exists('WPGraphQL')) {
		$graphql = new \HeadlessWP\GraphQL();
		$graphql->init();
	}
}
add_action('plugins_loaded', 'init_headlesswp_graphql');

/**
 * Begins execution of the plugin.
 */
function run_headlesswp() {
	$plugin = new HeadlessWP();
	$plugin->run();
}
run_headlesswp();