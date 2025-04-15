<?php
/**
 * Dashboard/About page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Process form submission
if (isset($_POST['headlesswp_dashboard_options']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_dashboard_options', 'headlesswp_dashboard_nonce');

	$options = get_option('headlesswp_options', array());
	$options['disable_frontend'] = isset($_POST['headlesswp_options']['disable_frontend']) ? true : false;

	update_option('headlesswp_options', $options);

	// Show notice
	add_settings_error(
		'headlesswp_dashboard_options',
		'headlesswp_settings_updated',
		__('Dashboard settings updated.', 'headlesswp'),
		'updated'
	);
}

// Get current options
$options = get_option('headlesswp_options', array());
$disable_frontend = isset($options['disable_frontend']) ? $options['disable_frontend'] : false;

// Get API statistics
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();
$total_endpoints = count($routes);

// Count namespaces
$namespaces = array();
foreach ($routes as $route => $route_data) {
	$parts = explode('/', ltrim($route, '/'));
	$namespace = !empty($parts[0]) ? $parts[0] : 'root';
	if (!isset($namespaces[$namespace])) {
		$namespaces[$namespace] = 0;
	}
	$namespaces[$namespace]++;
}
$total_namespaces = count($namespaces);

// Count disabled endpoints
$disabled_endpoints = isset($options['disabled_endpoints']) ? count($options['disabled_endpoints']) : 0;
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_dashboard_options'); ?>

        <!-- Quick Actions Panel -->
        <div class="headlesswp-card">
            <h2><?php _e('Headless Mode', 'headlesswp'); ?></h2>
            <p><?php _e('Toggle the headless mode for your WordPress site. When enabled, frontend requests will be redirected to the REST API.', 'headlesswp'); ?></p>

            <form method="post" action="">
				<?php wp_nonce_field('headlesswp_dashboard_options', 'headlesswp_dashboard_nonce'); ?>

                <div class="headlesswp-toggle-container">
                    <label class="headlesswp-toggle-switch">
                        <input type="checkbox" name="headlesswp_options[disable_frontend]" <?php checked($disable_frontend, true); ?>>
                        <span class="headlesswp-toggle-slider"></span>
                    </label>
                    <span class="headlesswp-toggle-label">
                        <?php _e('Disable Frontend', 'headlesswp'); ?>
                    </span>
                    <p class="description"><?php _e('When enabled, all frontend requests will be redirected to the REST API.', 'headlesswp'); ?></p>
                </div>

                <div class="headlesswp-status-box <?php echo $disable_frontend ? 'active' : 'inactive'; ?>">
                    <div class="headlesswp-status-icon">
                        <span class="dashicons <?php echo $disable_frontend ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span>
                    </div>
                    <div class="headlesswp-status-message">
						<?php if ($disable_frontend): ?>
                            <h4><?php _e('Headless Mode Active', 'headlesswp'); ?></h4>
                            <p><?php _e('Your site is currently in headless mode. Frontend requests are being redirected to the REST API.', 'headlesswp'); ?></p>
						<?php else: ?>
                            <h4><?php _e('Headless Mode Inactive', 'headlesswp'); ?></h4>
                            <p><?php _e('Your site is currently serving the frontend as normal. Enable headless mode to redirect frontend requests to the REST API.', 'headlesswp'); ?></p>
						<?php endif; ?>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="headlesswp_dashboard_options" class="button button-primary" value="<?php _e('Save Changes', 'headlesswp'); ?>">
                </p>
            </form>
        </div>

        <!-- API Statistics -->
        <div class="headlesswp-card">
            <h2><?php _e('API Statistics', 'headlesswp'); ?></h2>

            <div class="headlesswp-stats-grid">
                <div class="headlesswp-stat-box">
                    <div class="headlesswp-stat-icon">
                        <span class="dashicons dashicons-rest-api"></span>
                    </div>
                    <div class="headlesswp-stat-content">
                        <h3><?php echo esc_html($total_endpoints); ?></h3>
                        <p><?php _e('Total Endpoints', 'headlesswp'); ?></p>
                    </div>
                </div>

                <div class="headlesswp-stat-box">
                    <div class="headlesswp-stat-icon">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <div class="headlesswp-stat-content">
                        <h3><?php echo esc_html($total_namespaces); ?></h3>
                        <p><?php _e('API Namespaces', 'headlesswp'); ?></p>
                    </div>
                </div>

                <div class="headlesswp-stat-box">
                    <div class="headlesswp-stat-icon">
                        <span class="dashicons dashicons-hidden"></span>
                    </div>
                    <div class="headlesswp-stat-content">
                        <h3><?php echo esc_html($disabled_endpoints); ?></h3>
                        <p><?php _e('Disabled Endpoints', 'headlesswp'); ?></p>
                    </div>
                </div>

                <div class="headlesswp-stat-box">
                    <div class="headlesswp-stat-icon">
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                    <div class="headlesswp-stat-content">
                        <h3><?php echo esc_url(rest_url()); ?></h3>
                        <p><?php _e('API Root', 'headlesswp'); ?></p>
                    </div>
                </div>
            </div>

            <div class="headlesswp-action-buttons">
                <a href="<?php echo admin_url('admin.php?page=headlesswp-api'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-tools"></span> <?php _e('Manage API Endpoints', 'headlesswp'); ?>
                </a>

                <a href="<?php echo admin_url('admin.php?page=headlesswp-security'); ?>" class="button">
                    <span class="dashicons dashicons-shield"></span> <?php _e('Security Settings', 'headlesswp'); ?>
                </a>

                <a href="<?php echo rest_url(); ?>" class="button" target="_blank">
                    <span class="dashicons dashicons-external"></span> <?php _e('View API Root', 'headlesswp'); ?>
                </a>
            </div>
        </div>

        <!-- Quick Links and Resources -->
        <div class="headlesswp-card">
            <h2><?php _e('Getting Started with Headless WordPress', 'headlesswp'); ?></h2>
            <p><?php _e('Headless WordPress separates your content management from your frontend presentation, allowing you to build your frontend using modern frameworks while managing content in WordPress.', 'headlesswp'); ?></p>

            <div class="headlesswp-quick-links">
                <div class="headlesswp-link-box">
                    <div class="headlesswp-link-icon">
                        <span class="dashicons dashicons-book"></span>
                    </div>
                    <div class="headlesswp-link-content">
                        <h3><?php _e('Documentation', 'headlesswp'); ?></h3>
                        <p><?php _e('Explore our comprehensive documentation to get started with HeadlessWP.', 'headlesswp'); ?></p>
                        <a href="https://headlesswp.net/docs" class="button" target="_blank">
							<?php _e('View Docs', 'headlesswp'); ?>
                        </a>
                    </div>
                </div>

                <div class="headlesswp-link-box">
                    <div class="headlesswp-link-icon">
                        <span class="dashicons dashicons-admin-site"></span>
                    </div>
                    <div class="headlesswp-link-content">
                        <h3><?php _e('Starter Templates', 'headlesswp'); ?></h3>
                        <p><?php _e('Get going quickly with our frontend starter templates for React, Vue, and more.', 'headlesswp'); ?></p>
                        <a href="https://headlesswp.net/templates" class="button" target="_blank">
							<?php _e('Browse Templates', 'headlesswp'); ?>
                        </a>
                    </div>
                </div>

                <div class="headlesswp-link-box">
                    <div class="headlesswp-link-icon">
                        <span class="dashicons dashicons-sos"></span>
                    </div>
                    <div class="headlesswp-link-content">
                        <h3><?php _e('Get Support', 'headlesswp'); ?></h3>
                        <p><?php _e('Need help? Our support team is ready to assist with your HeadlessWP integration.', 'headlesswp'); ?></p>
                        <a href="https://headlesswp.net/support" class="button" target="_blank">
							<?php _e('Contact Support', 'headlesswp'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>

<style>
    /* Toggle Switch Styles */
    .headlesswp-toggle-container {
        margin: 20px 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .headlesswp-toggle-switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
        margin-right: 10px;
    }

    .headlesswp-toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .headlesswp-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }

    .headlesswp-toggle-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .headlesswp-toggle-slider {
        background-color: #2271b1;
    }

    input:focus + .headlesswp-toggle-slider {
        box-shadow: 0 0 1px #2271b1;
    }

    input:checked + .headlesswp-toggle-slider:before {
        transform: translateX(26px);
    }

    .headlesswp-toggle-label {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 5px;
    }

    /* Status Box Styles */
    .headlesswp-status-box {
        display: flex;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }

    .headlesswp-status-box.active {
        background-color: #edf7ed;
        border-left: 4px solid #46b450;
    }

    .headlesswp-status-box.inactive {
        background-color: #f7eded;
        border-left: 4px solid #dc3232;
    }

    .headlesswp-status-icon {
        margin-right: 15px;
        font-size: 24px;
    }

    .headlesswp-status-box.active .headlesswp-status-icon {
        color: #46b450;
    }

    .headlesswp-status-box.inactive .headlesswp-status-icon {
        color: #dc3232;
    }

    .headlesswp-status-message h4 {
        margin: 0 0 5px 0;
    }

    .headlesswp-status-message p {
        margin: 0;
    }

    /* Statistics Grid */
    .headlesswp-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .headlesswp-stat-box {
        background-color: #f0f0f1;
        border-radius: 5px;
        padding: 15px;
        display: flex;
        align-items: center;
    }

    .headlesswp-stat-icon {
        margin-right: 15px;
        font-size: 24px;
        color: #2271b1;
    }

    .headlesswp-stat-content h3 {
        margin: 0 0 5px 0;
        font-size: 18px;
        word-break: break-word;
    }

    .headlesswp-stat-content p {
        margin: 0;
        color: #646970;
    }

    /* Action Buttons */
    .headlesswp-action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .headlesswp-action-buttons .button {
        display: flex;
        align-items: center;
    }

    .headlesswp-action-buttons .dashicons {
        margin-right: 5px;
    }

    /* Quick Links */
    .headlesswp-quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .headlesswp-link-box {
        background-color: #f0f0f1;
        border-radius: 5px;
        padding: 20px;
        display: flex;
    }

    .headlesswp-link-icon {
        margin-right: 15px;
        font-size: 24px;
        color: #2271b1;
    }

    .headlesswp-link-content h3 {
        margin: 0 0 10px 0;
    }

    .headlesswp-link-content p {
        margin: 0 0 15px 0;
    }
</style>