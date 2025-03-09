<?php
/**
 * Settings handling class.
 *
 * This class handles plugin settings and options.
 *
 * @since      1.0.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings handling class.
 */
class HeadlessWP_Settings {

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
	 * Initialize settings functionality.
	 */
	public function init() {
		// Register settings
		add_action('admin_init', [$this, 'register_settings']);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// Main settings
		register_setting(
			'headlesswp_options',
			'headlesswp_options',
			[$this, 'validate_options']
		);

		// General settings section
		add_settings_section(
			'headlesswp_general',
			__('General Settings', 'headlesswp'),
			[$this, 'render_general_section'],
			'headlesswp'
		);

		// Add settings fields
		add_settings_field(
			'disable_themes',
			__('Disable Themes Section', 'headlesswp'),
			[$this, 'render_checkbox_field'],
			'headlesswp',
			'headlesswp_general',
			[
				'id' => 'disable_themes',
				'description' => __('Hide the Themes section in the WordPress admin.', 'headlesswp')
			]
		);

		add_settings_field(
			'disable_frontend',
			__('Disable Frontend', 'headlesswp'),
			[$this, 'render_checkbox_field'],
			'headlesswp',
			'headlesswp_general',
			[
				'id' => 'disable_frontend',
				'description' => __('Redirect all frontend requests to the REST API.', 'headlesswp')
			]
		);

		// Security settings (formerly CORS)
		register_setting(
			'headlesswp_security_options',
			'headlesswp_options',
			[$this, 'validate_options']
		);

		// Security settings section
		add_settings_section(
			'headlesswp_security_cors',
			__('CORS Settings', 'headlesswp'),
			[$this, 'render_cors_section'],
			'headlesswp_security'
		);

		add_settings_field(
			'enable_cors',
			__('Enable CORS', 'headlesswp'),
			[$this, 'render_checkbox_field'],
			'headlesswp_security',
			'headlesswp_security_cors',
			[
				'id' => 'enable_cors',
				'description' => __('Enable Cross-Origin Resource Sharing for the REST API.', 'headlesswp')
			]
		);

		add_settings_field(
			'allowed_origins',
			__('Allowed Origins', 'headlesswp'),
			[$this, 'render_text_field'],
			'headlesswp_security',
			'headlesswp_security_cors',
			[
				'id' => 'allowed_origins',
				'description' => __('Comma-separated list of allowed origins, or * for all origins.', 'headlesswp')
			]
		);
	}

	/**
	 * Validate plugin options.
	 *
	 * @param array $input The options to validate.
	 * @return array The validated options.
	 */
	public function validate_options($input) {
		$output = [];

		// Validate checkboxes
		$checkboxes = ['disable_themes', 'disable_frontend', 'enable_cors'];
		foreach ($checkboxes as $checkbox) {
			$output[$checkbox] = isset($input[$checkbox]) ? true : false;
		}

		// Validate text fields
		$output['allowed_origins'] = sanitize_text_field($input['allowed_origins']);

		// Preserve custom endpoints
		$output['custom_endpoints'] = isset($this->options['custom_endpoints']) ? $this->options['custom_endpoints'] : [];
		if (isset($input['custom_endpoints']) && is_array($input['custom_endpoints'])) {
			$output['custom_endpoints'] = $input['custom_endpoints'];
		}

		return $output;
	}

	/**
	 * Render the general settings section.
	 */
	public function render_general_section() {
		echo '<p>' . __('Configure the general settings for your headless WordPress installation.', 'headlesswp') . '</p>';
	}

	/**
	 * Render the CORS settings section.
	 */
	public function render_cors_section() {
		echo '<p>' . __('Configure Cross-Origin Resource Sharing (CORS) for your REST API.', 'headlesswp') . '</p>';
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field($args) {
		$id = $args['id'];
		$description = $args['description'];
		$checked = isset($this->options[$id]) ? $this->options[$id] : false;

		echo '<input type="checkbox" id="' . esc_attr($id) . '" name="headlesswp_options[' . esc_attr($id) . ']" ' . checked($checked, true, false) . ' />';
		echo '<label for="' . esc_attr($id) . '">' . esc_html($description) . '</label>';
	}

	/**
	 * Render a text field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field($args) {
		$id = $args['id'];
		$description = $args['description'];
		$value = isset($this->options[$id]) ? $this->options[$id] : '';

		echo '<input type="text" id="' . esc_attr($id) . '" name="headlesswp_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html($description) . '</p>';
	}
}