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
        add_action('admin_init', [$this, 'register_settings']);
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
        add_settings_section(
            'headlesswp_redirect',
            __('Redirect Settings', 'headlesswp'),
            [$this, 'render_redirect_section'],
            'headlesswp'
        );
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
        add_settings_section(
            'headlesswp_general',
            __('General Settings', 'headlesswp'),
            [$this, 'render_general_section'],
            'headlesswp'
        );
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
     * Render the redirect settings section.
     */
    public function render_redirect_section() {
        echo '<p>' . __('Configure where to redirect the frontend when headless mode is enabled.', 'headlesswp') . '</p>';
    }
    /**
     * Render the general settings section.
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general plugin settings.', 'headlesswp') . '</p>';
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
     * Validate plugin options.
     *
     * @param array $input The options to validate.
     * @return array The validated options.
     */
    public function validate_options($input) {
        $output = [];
        $output['disable_themes'] = isset($input['disable_themes']) ? true : false;

        $valid_redirect_types = ['api', 'custom'];
        $output['redirect_url'] = isset($input['redirect_url']) && in_array($input['redirect_url'], $valid_redirect_types)
            ? $input['redirect_url']
            : 'api';
        $output['custom_redirect_url'] = isset($input['custom_redirect_url']) ? esc_url_raw($input['custom_redirect_url']) : '';

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
                $('#custom_redirect_url').on('focus click', function() {
                    $('input[name="headlesswp_options[redirect_url]"][value="custom"]').prop('checked', true);
                });

                function toggleCustomFields() {
                    var redirectType = $('input[name="headlesswp_options[redirect_url]"]:checked').val();

                    if (redirectType === 'custom') {
                        $('#custom_redirect_url').closest('tr').show();
                    } else {
                        $('#custom_redirect_url').closest('tr').hide();
                    }
                }

                toggleCustomFields();

                $('input[name="headlesswp_options[redirect_url]"]').on('change', toggleCustomFields);
            });
        </script>
        <?php
    }
}