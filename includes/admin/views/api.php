<?php
/**
 * Endpoints page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
        <div class="headlesswp-card">
            <h2><?php _e('Available REST API Endpoints', 'headlesswp'); ?></h2>
            <p><?php _e('These are all the available REST API endpoints for your headless WordPress site.', 'headlesswp'); ?></p>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th><?php _e('Route', 'headlesswp'); ?></th>
                    <th><?php _e('Methods', 'headlesswp'); ?></th>
                    <th><?php _e('Namespace', 'headlesswp'); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ($routes as $route => $route_data) :
					$methods = [];
					foreach ($route_data as $data) {
						if (isset($data['methods'])) {
							$methods = array_merge($methods, array_keys($data['methods']));
						}
					}
					$methods = array_unique($methods);
					sort($methods);

					// Extract namespace from route
					$namespace = explode('/', ltrim($route, '/'))[0];
					?>
                    <tr>
                        <td><code><?php echo esc_html($route); ?></code></td>
                        <td><?php echo esc_html(implode(', ', $methods)); ?></td>
                        <td><?php echo esc_html($namespace); ?></td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="headlesswp-card">
            <h2><?php _e('API Base URL', 'headlesswp'); ?></h2>
            <p><?php _e('Use this as your base URL for API requests:', 'headlesswp'); ?></p>
            <code style="display: block; padding: 10px; background: #f0f0f0; margin: 10px 0;"><?php echo esc_url(rest_url()); ?></code>

            <p><?php _e('Example request:', 'headlesswp'); ?></p>
            <code style="display: block; padding: 10px; background: #f0f0f0; margin: 10px 0;"><?php echo esc_url(rest_url('wp/v2/posts')); ?></code>
        </div>
    </div>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>