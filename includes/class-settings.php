<?php
/**
 * Settings handling class.
 *
 * This class handles plugin settings and options.
 *
 * @since      0.1.0
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
		// Add JavaScript for settings page
		add_action('admin_footer', [$this, 'add_settings_js']);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'headlesswp_options',
			'headlesswp_options',
			[$this, 'validate_options']
		);

		// API Settings section
		add_settings_section(
			'headlesswp_api',
			__('API Settings', 'headlesswp'),
			[$this, 'render_api_section'],
			'headlesswp'
		);

		// Add API URL structure field
		add_settings_field(
			'api_url_structure',
			__('API URL Structure', 'headlesswp'),
			[$this, 'render_api_url_structure'],
			'headlesswp',
			'headlesswp_api',
			[
				'id' => 'api_url_structure',
				'description' => __('Choose the URL structure for your API endpoints.', 'headlesswp')
			]
		);

		// Add custom API URL field
		add_settings_field(
			'custom_api_url',
			__('Custom API URL', 'headlesswp'),
			[$this, 'render_custom_api_url'],
			'headlesswp',
			'headlesswp_api',
			[
				'id' => 'custom_api_url',
				'description' => __('Enter a custom API URL if you selected "Custom URL" above.', 'headlesswp')
			]
		);

		// Redirect Settings section
		add_settings_section(
			'headlesswp_redirect',
			__('Redirect Settings', 'headlesswp'),
			[$this, 'render_redirect_section'],
			'headlesswp'
		);

		// Add redirect URL field
		add_settings_field(
			'redirect_url',
			__('Redirect URL', 'headlesswp'),
			[$this, 'render_redirect_url'],
			'headlesswp',
			'headlesswp_redirect',
			[
				'id' => 'redirect_url',
				'description' => __('Choose where to redirect the frontend when headless mode is enabled.', 'headlesswp')
			]
		);

		// Add custom redirect URL field
		add_settings_field(
			'custom_redirect_url',
			__('Custom Redirect URL', 'headlesswp'),
			[$this, 'render_custom_redirect_url'],
			'headlesswp',
			'headlesswp_redirect',
			[
				'id' => 'custom_redirect_url',
				'description' => __('Enter a custom redirect URL if you selected "Custom URL" above.', 'headlesswp')
			]
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
	}

	/**
	 * Render the API settings section.
	 */
	public function render_api_section() {
		echo '<p>' . __('Configure the URL structure for your API endpoints.', 'headlesswp') . '</p>';
	}

	/**
	 * Render the redirect settings section.
	 */
	public function render_redirect_section() {
		echo '<p>' . __('Configure where to redirect the frontend when headless mode is enabled.', 'headlesswp') . '</p>';
	}

	/**
	 * Render the API URL structure field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_api_url_structure($args) {
		$id = $args['id'];
		$description = $args['description'];
		$value = isset($this->options[$id]) ? $this->options[$id] : 'wp/v2';

		$structures = [
			'wp/v2' => __('WordPress v2 API (wp/v2) (Recommended)', 'headlesswp'),
			'wp' => __('WordPress Old API (wp)', 'headlesswp'),
			'api' => __('Custom API (/api)', 'headlesswp'),
			'custom' => __('Custom URL', 'headlesswp'),
		];

		echo '<fieldset>';
		foreach ($structures as $structure => $label) {
			printf(
				'<label><input type="radio" name="headlesswp_options[%s]" value="%s" %s> %s</label><br>',
				esc_attr($id),
				esc_attr($structure),
				checked($value, $structure, false),
				esc_html($label)
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html($description) . '</p>';
	}

	/**
	 * Render the custom API URL field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_custom_api_url($args) {
		$id = $args['id'];
		$description = $args['description'];
		$value = isset($this->options[$id]) ? $this->options[$id] : '';
		$api_structure = isset($this->options['api_url_structure']) ? $this->options['api_url_structure'] : 'wp/v2';

		echo '<input type="text" id="' . esc_attr($id) . '" name="headlesswp_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html($description) . '</p>';
	}

	/**
	 * Render the redirect URL field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_redirect_url($args) {
		$id = $args['id'];
		$description = $args['description'];
		$value = isset($this->options[$id]) ? $this->options[$id] : 'api';

		$options = [
			'api' => __('API URL', 'headlesswp'),
			'custom' => __('Custom URL', 'headlesswp'),
		];

		echo '<fieldset>';
		foreach ($options as $option => $label) {
			printf(
				'<label><input type="radio" name="headlesswp_options[%s]" value="%s" %s> %s</label><br>',
				esc_attr($id),
				esc_attr($option),
				checked($value, $option, false),
				esc_html($label)
			);
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html($description) . '</p>';
	}

	/**
	 * Render the custom redirect URL field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_custom_redirect_url($args) {
		$id = $args['id'];
		$description = $args['description'];
		$value = isset($this->options[$id]) ? $this->options[$id] : '';
		$redirect_type = isset($this->options['redirect_url']) ? $this->options['redirect_url'] : 'api';

		echo '<input type="text" id="' . esc_attr($id) . '" name="headlesswp_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html($description) . '</p>';
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

	/**
	 * Validate plugin options.
	 *
	 * @param array $input The options to validate.
	 * @return array The validated options.
	 */
	public function validate_options($input) {
		$output = [];

		// Validate checkboxes
		$checkboxes = ['disable_themes', 'enable_cors', 'disable_frontend'];
		foreach ($checkboxes as $checkbox) {
			$output[$checkbox] = isset($input[$checkbox]) ? true : false;
		}

		// Validate API URL structure
		$valid_structures = ['wp/v2', 'wp', 'api', 'custom'];
		$output['api_url_structure'] = isset($input['api_url_structure']) && in_array($input['api_url_structure'], $valid_structures)
			? $input['api_url_structure']
			: 'wp/v2';

		// Validate custom API URL
		$output['custom_api_url'] = isset($input['custom_api_url']) ? esc_url_raw($input['custom_api_url']) : '';

		// Validate redirect URL type
		$valid_redirect_types = ['api', 'custom'];
		$output['redirect_url'] = isset($input['redirect_url']) && in_array($input['redirect_url'], $valid_redirect_types)
			? $input['redirect_url']
			: 'api';

		// Validate custom redirect URL
		$output['custom_redirect_url'] = isset($input['custom_redirect_url']) ? esc_url_raw($input['custom_redirect_url']) : '';

		// Validate text fields
		$output['allowed_origins'] = sanitize_text_field($input['allowed_origins']);

		// Preserve custom endpoints and disabled endpoints
		$output['custom_endpoints'] = isset($this->options['custom_endpoints']) ? $this->options['custom_endpoints'] : [];
		if (isset($input['custom_endpoints']) && is_array($input['custom_endpoints'])) {
			$output['custom_endpoints'] = $input['custom_endpoints'];
		}

		$output['disabled_endpoints'] = isset($this->options['disabled_endpoints']) ? $this->options['disabled_endpoints'] : [];

		return $output;
	}

	/**
	 * Add JavaScript for settings page.
	 */
	public function add_settings_js() {
		$screen = get_current_screen();
		if ($screen->id !== 'toplevel_page_headlesswp') {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Handle API URL input click
				$('#custom_api_url').on('focus', function() {
					$('input[name="headlesswp_options[api_url_structure]"][value="custom"]').prop('checked', true);
				});

				// Handle redirect URL input click
				$('#custom_redirect_url').on('focus', function() {
					$('input[name="headlesswp_options[redirect_url]"][value="custom"]').prop('checked', true);
				});
			});
		</script>
		<?php
	}
}