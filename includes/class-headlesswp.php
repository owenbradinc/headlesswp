<?php
/**
 * The main plugin class.
 *
 * This is the main class that coordinates all functionality of the plugin.
 *
 * @since      0.1.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * The main plugin class.
 */
class HeadlessWP {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * The settings class instance.
	 *
	 * @var HeadlessWP_Settings
	 */
	protected $settings;

	/**
	 * The admin class instance.
	 *
	 * @var HeadlessWP_Admin
	 */
	protected $admin;

	/**
	 * The frontend class instance.
	 *
	 * @var HeadlessWP_Frontend
	 */
	protected $frontend;

	/**
	 * The API class instance.
	 *
	 * @var HeadlessWP_API
	 */
	protected $api;

	/**
	 * The CORS class instance.
	 *
	 * @var HeadlessWP_Security
	 */
	protected $security;

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		// Load plugin options with default values
		$this->options = get_option('headlesswp_options', [
			'disable_themes' => false,
			'disable_frontend' => false,
			'enable_cors' => true,
			'allow_all_origins' => false,
			'cors_origins' => [],
			'custom_endpoints' => [],
			'openapi' => [
				'enable_try_it' => true,
				'enable_callback_discovery' => true
			]
		]);

		// Load required files
		$this->load_dependencies();

		// Initialize components
		$this->settings = new HeadlessWP_Settings($this->options);
		$this->admin = new HeadlessWP_Admin($this->options);
		$this->frontend = new HeadlessWP_Frontend($this->options);
		$this->api = new HeadlessWP_API($this->options);
		$this->security = new HeadlessWP_Security($this->options);
	}

	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {


		// Include class files
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-settings.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-admin.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-api.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-cors.php';
	}

	/**
	 * Run the plugin - hook into WordPress.
	 */
	public function run() {
		// Load internationalization
		add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

		// Initialize components
		$this->settings->init();
		$this->admin->init();
		$this->frontend->init();
		$this->api->init();
		$this->security->init();
		$this->init_openapi();
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'headlesswp',
			false,
			dirname(HEADLESSWP_PLUGIN_BASENAME) . '/lang/'
		);
	}

	/**
	 * Initialize OpenAPI functionality
	 */
	private function init_openapi() {
		// Base OpenAPI classes
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-openapi.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Callback.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/CallbackFinder.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Filters.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/SchemaGenerator.php';

		// Config
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Config/ApiGroups.php';

		// Filters
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Filters/AddCallbackInfoToDescription.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Filters/InfoFilter.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Filters/OperationsFilter.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Filters/TagsFilter.php';

		// Spec
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Contact.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Info.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/License.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Operation.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Parameter.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Path.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Response.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/ResponseContent.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Server.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/openapi/Spec/Tag.php';
		
		// Register filters
		HeadlessWP\OpenAPI\Filters\TagsFilter::register();
		HeadlessWP\OpenAPI\Filters\OperationsFilter::register();
		HeadlessWP\OpenAPI\Filters\InfoFilter::register();
		
		$openapi = new HeadlessWP_OpenAPI($this->options['openapi'] ?? []);
		$openapi->init();
	}
}