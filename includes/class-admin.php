<?php
/**
 * Admin interface class.
 *
 * This class handles the admin interface for the plugin.
 *
 * @since      1.0.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin interface class.
 */
class HeadlessWP_Admin {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected array $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct($options) {
		$this->options = $options;
	}

	/**
	 * Initialize admin functionality.
	 */
	public function init() {
		// Add admin menu
		add_action('admin_menu', [$this, 'add_admin_menu']);

		// Enqueue admin scripts and styles
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_assets($hook) {
		// Only load on plugin pages
		if (! str_contains( $hook, 'headlesswp' ) ) {
			return;
		}

		// Enqueue jQuery for admin pages
		wp_enqueue_script('jquery');

		// Enqueue the stylesheet
		wp_enqueue_style(
			'headlesswp-admin-styles',
			HEADLESSWP_PLUGIN_URL . 'assets/css/admin-style.css',
			[],
			HEADLESSWP_VERSION
		);

		// Add inline CSS for specific overrides
		wp_add_inline_style('headlesswp-admin-styles', '
            /* Fix for WordPress admin compatibility */
            .headlesswp-admin-wrap .wrap h1 { 
                display: inline-block;
                margin: 0;
            }
            
            /* Styles for CORS origins table */
            .cors-origins-container {
                margin-top: 20px;
            }
            
            .cors-origins-container .hidden {
                display: none;
            }
            
            #cors-origins-table input[type="text"] {
                width: 100%;
            }
        ');
	}

	/**
	 * Add menu to WordPress admin.
	 */
	public function add_admin_menu() {
		// Add main menu item
		add_menu_page(
			__('HeadlessWP', 'headlesswp'),
			__('HeadlessWP', 'headlesswp'),
			'manage_options',
			'headlesswp',
			[$this, 'display_about_page'],
			'dashicons-superhero'
		);

		// Add submenu items
		add_submenu_page(
			'headlesswp',
			__('Dashboard', 'headlesswp'),
			__('Dashboard', 'headlesswp'),
			'manage_options',
			'headlesswp',
			[$this, 'display_about_page']
		);

		add_submenu_page(
			'headlesswp',
			__('Setup', 'headlesswp'),
			__('Setup', 'headlesswp'),
			'manage_options',
			'headlesswp-setup',
			[$this, 'display_setup_page']
		);

		add_submenu_page(
			'headlesswp',
			__('Endpoints', 'headlesswp'),
			__('Endpoints', 'headlesswp'),
			'manage_options',
			'headlesswp-api',
			[$this, 'display_endpoints_page']
		);


		add_submenu_page(
			'headlesswp',
			__('API Keys', 'headlesswp'),
			__('API Keys', 'headlesswp'),
			'manage_options',
			'headlesswp-api-keys',
			[$this, 'display_api_keys_page']
		);

		add_submenu_page(
			'headlesswp',
			__('Security', 'headlesswp'),
			__('Security', 'headlesswp'),
			'manage_options',
			'headlesswp-security',
			[$this, 'display_security_page']
		);

		add_submenu_page(
			'headlesswp',
			__('Settings', 'headlesswp'),
			__('Settings', 'headlesswp'),
			'manage_options',
			'headlesswp-settings',
			[$this, 'display_settings_page']
		);

		add_submenu_page(
			'headlesswp',
			__('Upgrade', 'headlesswp'),
			__('Upgrade', 'headlesswp'),
			'manage_options',
			'headlesswp-premium',
			function() {
				// This function will never be called as we're redirecting via JavaScript
				wp_redirect('https://headlesswp.net/pricing');
				exit;
			}
		);

		add_action('admin_head', function() {
			echo '<style>
        #adminmenu .wp-submenu a[href$="page=headlesswp-premium"] {
            font-weight: bold !important;
            color: gold !important;
        }
    </style>';
		});
	}

	/**
	 * Display the settings page.
	 */
	public function display_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Include the settings page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/settings.php';
	}

	public function display_setup_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Include the settings page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/setup.php';
	}

	public function display_about_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Include the settings page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/about.php';
	}

	/**
	 * Display the endpoints page.
	 */
	public function display_endpoints_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Get all registered REST routes
		$rest_server = rest_get_server();
		$routes = $rest_server->get_routes();
		ksort($routes);

		// Include the endpoints page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/api.php';
	}

	/**
	 * Display the API keys page.
	 */
	public function display_api_keys_page(): void {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Get all registered REST routes
		$rest_server = rest_get_server();
		$routes = $rest_server->get_routes();
		ksort($routes);

		// Include the endpoints page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/api-keys.php';
	}

	/**
	 * Display the security settings page.
	 */
	public function display_security_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		// Get the options for the template
		$options = $this->options;

		// Include the security settings page template
		include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/security.php';
	}
}