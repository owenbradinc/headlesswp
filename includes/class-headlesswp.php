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
	 * The single instance of the class.
	 *
	 * @var HeadlessWP
	 */
	protected static $instance = null;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Plugin security options.
	 *
	 * @var array
	 */
	protected $security_options;

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
	 * @var HeadlessWP_CORS
	 */
	protected $cors;

	/**
	 * The API authentication class instance.
	 *
	 * @var HeadlessWP_API_Auth
	 */
	protected $api_auth;

	/**
	 * The API keys handler instance.
	 *
	 * @var HeadlessWP_API_Keys
	 */
	protected $api_keys;

	/**
	 * Get the singleton instance.
	 *
	 * @return HeadlessWP
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		// Load plugin options with default values
		$this->options = get_option('headlesswp_options', [
			'disable_themes' => false,
			'disable_frontend' => false,
			'custom_endpoints' => [],
			'openapi' => [
				'enable_try_it' => true,
				'enable_callback_discovery' => true
			]
		]);

		// Load security options with default values
		$this->security_options = get_option('headlesswp_security_options', [
			'enable_cors' => true,
			'allow_all_origins' => false,
			'cors_origins' => []
		]);

		// Load required files
		$this->load_dependencies();

		// Initialize components
		$this->settings = new HeadlessWP_Settings($this->options);
		$this->admin = new HeadlessWP_Admin($this->options);
		$this->frontend = new HeadlessWP_Frontend($this->options);
		$this->cors = new HeadlessWP_CORS($this->security_options);  // Pass security options to CORS handler
		$this->api_auth = new HeadlessWP_API_Auth($this->options);
		$this->api_keys = new HeadlessWP_API_Keys();
	}

	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {
		// Include class files
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-settings.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-admin.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-cors.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-api-auth.php';
		require_once HEADLESSWP_PLUGIN_DIR . 'includes/class-api-keys.php';
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
		$this->cors->init();
		$this->api_auth->init();
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

	/**
	 * Get all API keys
	 *
	 * @return array
	 */
	public function get_api_keys() {
		return $this->api_keys->get_keys();
	}

	/**
	 * Add a new API key
	 *
	 * @param string $name Key name
	 * @param string $description Key description
	 * @param string $permissions Key permissions
	 * @param array $origins Allowed origins
	 * @return array|WP_Error The new key data or WP_Error on failure
	 */
	public function add_api_key($name, $description = '', $permissions = 'read', $origins = []) {
		return $this->api_keys->add_key($name, $description, $permissions, $origins);
	}

	/**
	 * Revoke an API key
	 *
	 * @param string $key_id The key ID to revoke
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function revoke_api_key($key_id) {
		return $this->api_keys->revoke_key($key_id);
	}
}