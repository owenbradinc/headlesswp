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
		// Disable theme functionality if enabled
		if (!empty($this->options['disable_themes'])) {
			add_action('admin_menu', [$this, 'disable_themes_menu'], 999);
			add_action('admin_init', [$this, 'disable_theme_customizer']);
		}

		// Disable frontend if enabled
		if (!empty($this->options['disable_frontend'])) {
			add_action('template_redirect', [$this, 'redirect_frontend_to_api']);
		}
	}

	/**
	 * Disable the Themes menu in the admin.
	 */
	public function disable_themes_menu() {
		remove_menu_page('themes.php');
		remove_submenu_page('themes.php', 'themes.php');
		remove_submenu_page('themes.php', 'theme-editor.php');
		remove_submenu_page('themes.php', 'customize.php');
	}

	/**
	 * Disable the Theme Customizer.
	 */
	public function disable_theme_customizer() {
		global $pagenow;

		// Redirect from customizer page
		if ($pagenow === 'customize.php') {
			wp_redirect(admin_url());
			exit;
		}

		// Remove customize support
		remove_action('admin_menu', 'customize_admin_menu');
		remove_action('wp_before_admin_bar_render', 'wp_customize_support_script');
	}

	/**
	 * Redirect frontend requests to the REST API.
	 */
	public function redirect_frontend_to_api() {
		// Don't redirect admin or REST API requests
		if (is_admin() || defined('REST_REQUEST')) {
			return;
		}

		// Don't redirect WP-JSON requests
		if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
			return;
		}

		// Redirect to the REST API root
		wp_redirect(rest_url());
		exit;
	}
}