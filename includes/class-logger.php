<?php
/**
 * Logger class for HeadlessWP.
 *
 * This class handles logging functionality for the plugin.
 *
 * @since      0.1.0
 * @package    HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class.
 */
class HeadlessWP_Logger {
    /**
     * The single instance of the class.
     *
     * @var HeadlessWP_Logger
     */
    protected static $instance = null;

    /**
     * The log table name.
     *
     * @var string
     */
    protected $table_name;

    /**
     * Get the singleton instance.
     *
     * @return HeadlessWP_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the class.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'headlesswp_logs';
    }

    /**
     * Initialize logger functionality.
     */
    public function init() {
        // Create the logs table if it doesn't exist
        $this->create_table();

        // Add admin menu after the main menu is created
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
    }

    /**
     * Create the logs table.
     */
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            component varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY component (component),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add the logs page to the admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'headlesswp',
            __('Logs', 'headlesswp'),
            __('Logs', 'headlesswp'),
            'manage_options',
            'headlesswp-logs',
            [$this, 'render_logs_page']
        );
    }

    /**
     * Log a message.
     *
     * @param string $message The log message.
     * @param string $level The log level (error, warning, info, debug).
     * @param array $context Additional context data.
     * @param string $component The component that generated the log.
     */
    public function log($message, $level = 'info', $context = [], $component = 'general') {
        global $wpdb;

        $data = [
            'level' => sanitize_text_field($level),
            'message' => sanitize_text_field($message),
            'context' => maybe_serialize($context),
            'component' => sanitize_text_field($component),
            'user_id' => get_current_user_id()
        ];

        $wpdb->insert($this->table_name, $data);
    }

    /**
     * Get logs with optional filtering.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_logs($args = []) {
        global $wpdb;

        $defaults = [
            'level' => '',
            'component' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $values = [];

        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $values[] = $args['level'];
        }

        if (!empty($args['component'])) {
            $where[] = 'component = %s';
            $values[] = $args['component'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            array_merge($values, [$args['limit'], $args['offset']])
        );

        $logs = $wpdb->get_results($query, ARRAY_A);

        foreach ($logs as &$log) {
            $log['context'] = maybe_unserialize($log['context']);
        }

        return $logs;
    }

    /**
     * Get log levels.
     *
     * @return array
     */
    public function get_levels() {
        return ['error', 'warning', 'info', 'debug'];
    }

    /**
     * Get components that have generated logs.
     *
     * @return array
     */
    public function get_components() {
        global $wpdb;
        return $wpdb->get_col("SELECT DISTINCT component FROM {$this->table_name}");
    }

    /**
     * Clear logs.
     *
     * @param array $args Filter arguments.
     * @return int|false Number of rows affected or false on error.
     */
    public function clear_logs($args = []) {
        global $wpdb;

        $where = [];
        $values = [];

        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $values[] = $args['level'];
        }

        if (!empty($args['component'])) {
            $where[] = 'component = %s';
            $values[] = $args['component'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($values)) {
            $query = $wpdb->prepare("DELETE FROM {$this->table_name} {$where_clause}", $values);
        } else {
            $query = "DELETE FROM {$this->table_name}";
        }

        return $wpdb->query($query);
    }

    /**
     * Render the logs page.
     */
    public function render_logs_page() {
        // Process form submissions
        if (isset($_POST['headlesswp_clear_logs']) && current_user_can('manage_options')) {
            check_admin_referer('headlesswp_clear_logs', 'headlesswp_nonce');

            $filter = [
                'level' => isset($_POST['level']) ? sanitize_text_field($_POST['level']) : '',
                'component' => isset($_POST['component']) ? sanitize_text_field($_POST['component']) : ''
            ];

            $cleared = $this->clear_logs($filter);

            if ($cleared !== false) {
                add_settings_error(
                    'headlesswp_logs',
                    'logs_cleared',
                    sprintf(__('Cleared %d log entries.', 'headlesswp'), $cleared),
                    'updated'
                );
            }
        }

        // Get filter values
        $current_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        $current_component = isset($_GET['component']) ? sanitize_text_field($_GET['component']) : '';
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;

        // Get logs
        $args = [
            'level' => $current_level,
            'component' => $current_component,
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page
        ];

        $logs = $this->get_logs($args);
        $total_logs = $this->get_total_logs($args);
        $total_pages = ceil($total_logs / $per_page);

        // Get available levels and components
        $levels = $this->get_levels();
        $components = $this->get_components();

        // Include the template
        include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/logs.php';
    }

    /**
     * Get total number of logs matching the filter.
     *
     * @param array $args Filter arguments.
     * @return int
     */
    private function get_total_logs($args) {
        global $wpdb;

        $where = [];
        $values = [];

        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $values[] = $args['level'];
        }

        if (!empty($args['component'])) {
            $where[] = 'component = %s';
            $values[] = $args['component'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($values)) {
            $query = $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} {$where_clause}", $values);
        } else {
            $query = "SELECT COUNT(*) FROM {$this->table_name}";
        }

        return (int) $wpdb->get_var($query);
    }
} 