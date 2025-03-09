<?php
/**
 * Plugin Name: HeadlessWP
 * Plugin URI: https://github.com/yourusername/headlesswp
 * Description: Turn your WordPress site into a headless CMS with API endpoint management, theme disabling, and more.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: headlesswp
 * Domain Path: /languages
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
			'disable_frontend' => false
		]);
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