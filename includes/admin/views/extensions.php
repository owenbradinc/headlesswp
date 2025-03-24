<?php
/**
 * Extensions page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Get extensions manager
global $headlesswp_extensions;
if (!isset($headlesswp_extensions) || !is_object($headlesswp_extensions)) {
	echo '<div class="notice notice-error"><p>Extensions manager not found.</p></div>';
	return;
}

// Process extension activation/deactivation
if (isset($_GET['activate_extension']) && current_user_can('manage_options')) {
	check_admin_referer('activate-extension_' . $_GET['activate_extension']);

	$result = $headlesswp_extensions->activate_extension($_GET['activate_extension']);

	if (is_wp_error($result)) {
		add_settings_error(
			'headlesswp_extensions',
			'activation_failed',
			$result->get_error_message(),
			'error'
		);
	} else {
		add_settings_error(
			'headlesswp_extensions',
			'activation_success',
			__('Extension activated successfully.', 'headlesswp'),
			'success'
		);
	}
}

if (isset($_GET['deactivate_extension']) && current_user_can('manage_options')) {
	check_admin_referer('deactivate-extension_' . $_GET['deactivate_extension']);

	$result = $headlesswp_extensions->deactivate_extension($_GET['deactivate_extension']);

	if ($result) {
		add_settings_error(
			'headlesswp_extensions',
			'deactivation_success',
			__('Extension deactivated successfully.', 'headlesswp'),
			'success'
		);
	} else {
		add_settings_error(
			'headlesswp_extensions',
			'deactivation_failed',
			__('Failed to deactivate extension.', 'headlesswp'),
			'error'
		);
	}
}

// Get all extensions
$extensions = $headlesswp_extensions->get_extensions();
$active_extensions = $headlesswp_extensions->get_active_extensions();

// Group extensions by type (built-in vs third-party)
$builtin_extensions = array();
$external_extensions = array();

foreach ($extensions as $slug => $extension) {
	if (!empty($extension['is_builtin'])) {
		$builtin_extensions[$slug] = $extension;
	} else {
		$external_extensions[$slug] = $extension;
	}
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

	<div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_extensions'); ?>

		<div class="headlesswp-card">
			<h2><?php _e('Extensions', 'headlesswp'); ?></h2>
			<p><?php _e('Extend the functionality of HeadlessWP with these extensions.', 'headlesswp'); ?></p>

			<?php if (empty($extensions)): ?>
				<div class="headlesswp-no-items">
					<p><?php _e('No extensions found.', 'headlesswp'); ?></p>
				</div>
			<?php else: ?>
				<?php if (!empty($active_extensions)): ?>
					<h3><?php _e('Active Extensions', 'headlesswp'); ?></h3>
					<div class="headlesswp-extensions-grid">
						<?php
						foreach ($active_extensions as $slug) {
							if (isset($extensions[$slug])) {
								$extension = $extensions[$slug];
								$is_builtin = !empty($extension['is_builtin']);
								?>
								<div class="headlesswp-extension-card active">
									<div class="headlesswp-extension-header">
										<h4><?php echo esc_html($extension['name']); ?></h4>
										<?php if ($is_builtin): ?>
											<span class="headlesswp-extension-badge built-in"><?php _e('Built-in', 'headlesswp'); ?></span>
										<?php endif; ?>
										<span class="headlesswp-extension-badge active"><?php _e('Active', 'headlesswp'); ?></span>
									</div>

									<div class="headlesswp-extension-content">
										<p><?php echo esc_html($extension['description']); ?></p>

										<div class="headlesswp-extension-meta">
											<span class="headlesswp-extension-version">
												<?php printf(__('Version: %s', 'headlesswp'), $extension['version']); ?>
											</span>

											<span class="headlesswp-extension-author">
												<?php
												if (!empty($extension['author_uri'])) {
													printf(
														__('By <a href="%s" target="_blank">%s</a>', 'headlesswp'),
														esc_url($extension['author_uri']),
														esc_html($extension['author'])
													);
												} else {
													printf(__('By %s', 'headlesswp'), esc_html($extension['author']));
												}
												?>
											</span>
										</div>
									</div>

									<div class="headlesswp-extension-actions">
										<?php
										$deactivate_url = add_query_arg(
											array(
												'page' => 'headlesswp-extensions',
												'deactivate_extension' => $slug,
											),
											admin_url('admin.php')
										);
										$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-extension_' . $slug);
										?>
										<a href="<?php echo esc_url($deactivate_url); ?>" class="button headlesswp-deactivate-extension">
											<?php _e('Deactivate', 'headlesswp'); ?>
										</a>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
				<?php endif; ?>

				<?php if (!empty($builtin_extensions) || !empty($external_extensions)): ?>
					<h3><?php _e('Available Extensions', 'headlesswp'); ?></h3>
					<div class="headlesswp-extensions-grid">
						<?php
						// Process built-in extensions first
						foreach ($builtin_extensions as $slug => $extension) {
							if (!in_array($slug, $active_extensions)) {
								?>
								<div class="headlesswp-extension-card">
									<div class="headlesswp-extension-header">
										<h4><?php echo esc_html($extension['name']); ?></h4>
										<span class="headlesswp-extension-badge built-in"><?php _e('Built-in', 'headlesswp'); ?></span>
									</div>

									<div class="headlesswp-extension-content">
										<p><?php echo esc_html($extension['description']); ?></p>

										<div class="headlesswp-extension-meta">
											<span class="headlesswp-extension-version">
												<?php printf(__('Version: %s', 'headlesswp'), $extension['version']); ?>
											</span>

											<span class="headlesswp-extension-author">
												<?php
												if (!empty($extension['author_uri'])) {
													printf(
														__('By <a href="%s" target="_blank">%s</a>', 'headlesswp'),
														esc_url($extension['author_uri']),
														esc_html($extension['author'])
													);
												} else {
													printf(__('By %s', 'headlesswp'), esc_html($extension['author']));
												}
												?>
											</span>
										</div>
									</div>

									<div class="headlesswp-extension-actions">
										<?php
										$activate_url = add_query_arg(
											array(
												'page' => 'headlesswp-extensions',
												'activate_extension' => $slug,
											),
											admin_url('admin.php')
										);
										$activate_url = wp_nonce_url($activate_url, 'activate-extension_' . $slug);
										?>
										<a href="<?php echo esc_url($activate_url); ?>" class="button button-primary headlesswp-activate-extension">
											<?php _e('Activate', 'headlesswp'); ?>
										</a>
									</div>
								</div>
								<?php
							}
						}

						// Process external extensions
						foreach ($external_extensions as $slug => $extension) {
							if (!in_array($slug, $active_extensions)) {
								?>
								<div class="headlesswp-extension-card">
									<div class="headlesswp-extension-header">
										<h4><?php echo esc_html($extension['name']); ?></h4>
									</div>

									<div class="headlesswp-extension-content">
										<p><?php echo esc_html($extension['description']); ?></p>

										<div class="headlesswp-extension-meta">
											<span class="headlesswp-extension-version">
												<?php printf(__('Version: %s', 'headlesswp'), $extension['version']); ?>
											</span>

											<span class="headlesswp-extension-author">
												<?php
												if (!empty($extension['author_uri'])) {
													printf(
														__('By <a href="%s" target="_blank">%s</a>', 'headlesswp'),
														esc_url($extension['author_uri']),
														esc_html($extension['author'])
													);
												} else {
													printf(__('By %s', 'headlesswp'), esc_html($extension['author']));
												}
												?>
											</span>
										</div>
									</div>

									<div class="headlesswp-extension-actions">
										<?php
										$activate_url = add_query_arg(
											array(
												'page' => 'headlesswp-extensions',
												'activate_extension' => $slug,
											),
											admin_url('admin.php')
										);
										$activate_url = wp_nonce_url($activate_url, 'activate-extension_' . $slug);
										?>
										<a href="<?php echo esc_url($activate_url); ?>" class="button button-primary headlesswp-activate-extension">
											<?php _e('Activate', 'headlesswp'); ?>
										</a>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="headlesswp-card">
			<h2><?php _e('Develop Extensions', 'headlesswp'); ?></h2>
			<p><?php _e('Want to create your own extension for HeadlessWP? Extensions allow you to add new features or modify existing functionality.', 'headlesswp'); ?></p>

			<h3><?php _e('Extension Directory Structure', 'headlesswp'); ?></h3>
			<p><?php _e('To create a new extension, create a directory in the following location:', 'headlesswp'); ?></p>
			<code><?php echo esc_html(apply_filters('headlesswp_extensions_directory', '')); ?></code>

			<p><?php _e('Your extension directory should have the following structure:', 'headlesswp'); ?></p>
			<pre>
my-extension/
├── extension.php       # Main extension file with header
├── assets/             # Optional assets directory
│   ├── css/            # CSS files
│   └── js/             # JavaScript files
└── includes/           # PHP includes
    └── functions.php   # Extension functions
</pre>

			<h3><?php _e('Extension Header', 'headlesswp'); ?></h3>
			<p><?php _e('Your main extension.php file should include a header like this:', 'headlesswp'); ?></p>
			<pre>
/**
 * Extension Name: My Custom Extension
 * Extension Slug: my-custom-extension
 * Description: Adds some awesome new functionality to HeadlessWP.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Extension URI: https://yourwebsite.com/extensions/my-custom-extension
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */
</pre>

			<h3><?php _e('Extension Functions', 'headlesswp'); ?></h3>
			<p><?php _e('Your extension should implement these functions:', 'headlesswp'); ?></p>
			<ul>
				<li><code>headlesswp_[slug]_init($extensions_manager)</code> - <?php _e('Called when the extension is loaded', 'headlesswp'); ?></li>
				<li><code>headlesswp_[slug]_activate()</code> - <?php _e('Called when the extension is activated (optional)', 'headlesswp'); ?></li>
				<li><code>headlesswp_[slug]_deactivate()</code> - <?php _e('Called when the extension is deactivated (optional)', 'headlesswp'); ?></li>
			</ul>

			<h3><?php _e('Example Extension', 'headlesswp'); ?></h3>
			<p><?php _e('Here\'s a simple example of an extension:', 'headlesswp'); ?></p>
			<pre>
/**
 * Extension Name: Custom API Endpoints
 * Extension Slug: custom-api-endpoints
 * Description: Adds custom API endpoints to HeadlessWP.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Initialize the extension.
 *
 * @param HeadlessWP_Extensions $extensions_manager Extensions manager instance.
 */
function headlesswp_custom_api_endpoints_init($extensions_manager) {
	// Include other files
	include_once dirname(__FILE__) . '/includes/functions.php';
	
	// Register REST API endpoints
	add_action('rest_api_init', 'headlesswp_custom_endpoints_register_routes');
}

/**
 * Activation function.
 */
function headlesswp_custom_api_endpoints_activate() {
	// Activation tasks (if any)
}

/**
 * Deactivation function.
 */
function headlesswp_custom_api_endpoints_deactivate() {
	// Cleanup tasks (if any)
}

/**
 * Register custom REST API routes.
 */
function headlesswp_custom_endpoints_register_routes() {
	register_rest_route('headlesswp/v1', '/custom-data', array(
		'methods' => 'GET',
		'callback' => 'headlesswp_custom_data_endpoint',
		'permission_callback' => '__return_true'
	));
}

/**
 * Custom data endpoint callback.
 *
 * @return WP_REST_Response
 */
function headlesswp_custom_data_endpoint() {
	return rest_ensure_response(array(
		'success' => true,
		'data' => array(
			'message' => 'This is a custom endpoint from an extension!'
		)
	));
}
</pre>
		</div>
	</div>

	<style>
        /* Extension Cards */
        .headlesswp-extensions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .headlesswp-extension-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .headlesswp-extension-card.active {
            border-color: #46b450;
        }

        .headlesswp-extension-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .headlesswp-extension-card.active .headlesswp-extension-header {
            background-color: #f0f6e6;
        }

        .headlesswp-extension-header h4 {
            margin: 0;
            flex: 1;
        }

        .headlesswp-extension-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }

        .headlesswp-extension-badge.built-in {
            background-color: #e5f0fa;
            color: #0066cc;
        }

        .headlesswp-extension-badge.active {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .headlesswp-extension-content {
            padding: 15px;
        }

        .headlesswp-extension-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            font-size: 12px;
            color: #555;
        }

        .headlesswp-extension-actions {
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
            text-align: right;
        }

        .headlesswp-no-items {
            background-color: #f0f0f1;
            padding: 20px;
            text-align: center;
            border-radius: 3px;
        }

        /* Extension documentation styles */
        pre {
            background-color: #f6f7f7;
            padding: 15px;
            border-radius: 3px;
            overflow-x: auto;
            border: 1px solid #ddd;
            line-height: 1.4;
        }

        code {
            background-color: #f6f7f7;
            padding: 2px 5px;
            border-radius: 3px;
        }
	</style>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>