<?php
/**
 * OpenAPI functionality class.
 *
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

use HeadlessWP\OpenAPI\SchemaGenerator;
use HeadlessWP\OpenAPI\Filters;

class HeadlessWP_OpenAPI {
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
    public function __construct($options = []) {
        $this->options = wp_parse_args($options, [
            'enable_try_it' => true,
            'enable_callback_discovery' => true
        ]);
    }

    /**
     * Initialize OpenAPI functionality.
     */
    public function init() {
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        register_rest_route('headlesswp/v1', '/openapi', [
            'methods' => 'GET',
            'callback' => [$this, 'get_openapi_spec'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Get OpenAPI specification.
     *
     * @return WP_REST_Response
     */
    public function get_openapi_spec() {

         
        $siteInfo = [
            'admin_email' => get_option('admin_email'),
            'blogname' => get_bloginfo('name'),
            'blogdescription' => get_bloginfo('description'),
            'home' => home_url(),
            'wp_version' => get_bloginfo('version')
        ];
        
        $schemaGenerator = new SchemaGenerator(
            Filters::getInstance(),
            $siteInfo,
            rest_get_server()
        );
        
        return rest_ensure_response($schemaGenerator->generate('all'));

    }

    /**
     * Convert WordPress route to OpenAPI path.
     *
     * @param string $route WordPress route.
     * @return string
     */
    private function convert_route_to_path($route) {
        // Match WordPress parameter pattern: (?P<parameter_name>pattern)
        return preg_replace('/\(\?P<(\w+)>[^)]+\)/', '{$1}', $route);
    }

    /**
     * Get tag from route for grouping endpoints.
     */
    private function get_tag_from_route($route) {
        $parts = explode('/', trim($route, '/'));
        return !empty($parts[0]) ? $parts[0] : 'default';
    }

    /**
     * Get parameters for endpoint.
     */
    private function get_parameters_for_endpoint($handler) {
        $parameters = [];
        
        if (isset($handler['args'])) {
            foreach ($handler['args'] as $name => $arg) {
                $parameter = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => !empty($arg['required']),
                    'schema' => [
                        'type' => isset($arg['type']) ? $arg['type'] : 'string'
                    ]
                ];

                if (isset($arg['description'])) {
                    $parameter['description'] = $arg['description'];
                }

                if (isset($arg['enum'])) {
                    $parameter['schema']['enum'] = $arg['enum'];
                }

                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * Get response schema for endpoint.
     */
    private function get_response_schema($handler) {
        if (isset($handler['schema']) && is_callable($handler['schema'])) {
            $schema = call_user_func($handler['schema']);
            if (is_array($schema)) {
                return $schema;
            }
        }

        return [
            'type' => 'object',
            'properties' => []
        ];
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'headlesswp_page_headlesswp-openapi') {
            return;
        }

        // Enqueue Stoplight Elements from CDN
        wp_enqueue_script(
            'stoplight-elements',
            'https://unpkg.com/@stoplight/elements/web-components.min.js',
            [],
            '7.16.0',
            true
        );

        wp_enqueue_style(
            'stoplight-elements-styles',
            'https://unpkg.com/@stoplight/elements/styles.min.css',
            [],
            '7.16.0'
        );

        // Enqueue our built assets
        wp_enqueue_script(
            'headlesswp-openapi',
            HEADLESSWP_PLUGIN_URL . 'build/js/openapi.js',
            ['wp-element', 'react', 'react-dom', 'stoplight-elements'],
            HEADLESSWP_VERSION,
            true
        );

        wp_enqueue_style(
            'headlesswp-openapi',
            HEADLESSWP_PLUGIN_URL . 'build/css/openapi.css',
            ['stoplight-elements-styles'],
            HEADLESSWP_VERSION
        );

        wp_localize_script('headlesswp-openapi', 'openapi', [
            'endpoint' => rest_url('headlesswp/v1/openapi'),
            'nonce' => wp_create_nonce('wp_rest'),
            'options' => [
                'hideTryIt' => !$this->options['enable_try_it']
            ]
        ]);
     }
}
