<?php
/**
 * API Keys management page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Get current options
$options = get_option('headlesswp_options', array());
if (!is_array($options)) {
	$options = array();
}
$api_keys = isset($options['api_keys']) ? $options['api_keys'] : array();
$cors_origins = isset($options['cors_origins']) ? $options['cors_origins'] : array();

// Debug output
error_log('Current options: ' . print_r($options, true));
error_log('Current API keys: ' . print_r($api_keys, true));

// Process form submissions
if (isset($_POST['headlesswp_create_api_key']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_api_keys', 'headlesswp_nonce');

	$key_name = sanitize_text_field($_POST['key_name']);
	$key_description = sanitize_text_field($_POST['key_description']);
	$key_permissions = isset($_POST['key_permissions']) ? sanitize_text_field($_POST['key_permissions']) : 'read';
	$selected_origins = isset($_POST['key_origins']) ? array_map('sanitize_text_field', $_POST['key_origins']) : array();

	error_log('Form submitted with data: ' . print_r($_POST, true));

	if (empty($key_name)) {
		add_settings_error(
			'headlesswp_api_keys',
			'empty_key_name',
			__('Please provide a name for your API key.', 'headlesswp'),
			'error'
		);
	} else {
		// Generate a unique key
		$api_key = 'hwp_' . wp_generate_password(32, false, false);
		$api_key_plain = $api_key; // Store the plain key for display
		$api_key = wp_hash_password($api_key); // Hash the key for storage

		// Create new key array
		$new_key = array(
			'id' => uniqid('key_'),
			'name' => $key_name,
			'description' => $key_description,
			'key' => $api_key,
			'permissions' => $key_permissions,
			'origins' => $selected_origins,
			'created' => current_time('mysql'),
			'last_used' => '',
		);

		// Ensure api_keys array exists
		if (!isset($options['api_keys']) || !is_array($options['api_keys'])) {
			$options['api_keys'] = array();
		}

		// Add new key to array
		$options['api_keys'][] = $new_key;

		// Save the entire options array
		$result = update_option('headlesswp_options', $options);

		if ($result) {
			// Set transient to display the newly created key (using the plain key)
			set_transient('headlesswp_new_api_key', array(
				'key' => $api_key_plain
			), 60);

			add_settings_error(
				'headlesswp_api_keys',
				'key_created',
				__('API key created successfully.', 'headlesswp'),
				'success'
			);
		} else {
			add_settings_error(
				'headlesswp_api_keys',
				'key_save_error',
				__('Failed to save API key. Please try again.', 'headlesswp'),
				'error'
			);
		}

		// Refresh the page data
		$options = get_option('headlesswp_options', array());
		$api_keys = isset($options['api_keys']) ? $options['api_keys'] : array();
	}
}

// Handle API key revocation
if (isset($_POST['headlesswp_revoke_api_key']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_revoke_key', 'headlesswp_revoke_nonce');

	$key_id = sanitize_text_field($_POST['key_id']);

	if (!empty($key_id) && !empty($options['api_keys'])) {
		foreach ($options['api_keys'] as $index => $key) {
			if ($key['id'] === $key_id) {
				unset($options['api_keys'][$index]);
				break;
			}
		}

		// Re-index the array
		$options['api_keys'] = array_values($options['api_keys']);
		update_option('headlesswp_options', $options);

		add_settings_error(
			'headlesswp_api_keys',
			'key_revoked',
			__('API key revoked successfully.', 'headlesswp'),
			'success'
		);

		// Refresh the page data
		$options = get_option('headlesswp_options', array());
		$api_keys = isset($options['api_keys']) ? $options['api_keys'] : array();
	}
}

// Check for newly created key
$new_key = get_transient('headlesswp_new_api_key');
if ($new_key) {
	delete_transient('headlesswp_new_api_key');
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

	<div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_api_keys'); ?>

		<?php if ($new_key): ?>
			<div class="headlesswp-card headlesswp-new-key-notice">
				<h3><?php _e('Your new API key has been created', 'headlesswp'); ?></h3>
				<p><?php _e('Be sure to copy your API key now. For security reasons, we will only show the API key once.', 'headlesswp'); ?></p>

				<div class="headlesswp-key-details">
					<div class="headlesswp-copy-field">
						<label><?php _e('API Key:', 'headlesswp'); ?></label>
						<div class="headlesswp-copy-container">
							<input type="text" value="<?php echo esc_attr($new_key['key']); ?>" readonly class="headlesswp-copy-text" id="new-api-key">
							<button type="button" class="button headlesswp-copy-button" data-clipboard-target="#new-api-key">
								<span class="dashicons dashicons-clipboard"></span>
							</button>
						</div>
					</div>
				</div>

				<div class="headlesswp-warning">
					<p><strong><?php _e('Important:', 'headlesswp'); ?></strong> <?php _e('Copy this key to a secure location. You won\'t be able to see it again after you leave this page.', 'headlesswp'); ?></p>
				</div>
			</div>
		<?php endif; ?>

		<div class="headlesswp-card">
			<h2><?php _e('API Keys', 'headlesswp'); ?></h2>
			<p><?php _e('API keys allow external applications to authenticate with your WordPress REST API. You can restrict keys to specific origins and permission levels.', 'headlesswp'); ?></p>

			<?php if (empty($api_keys)): ?>
				<div class="headlesswp-no-items">
					<p><?php _e('You haven\'t created any API keys yet. Create your first key below.', 'headlesswp'); ?></p>
				</div>
			<?php else: ?>
				<table class="wp-list-table widefat fixed striped headlesswp-api-keys-table">
					<thead>
					<tr>
						<th><?php _e('Name', 'headlesswp'); ?></th>
						<th><?php _e('API Key', 'headlesswp'); ?></th>
						<th><?php _e('Permissions', 'headlesswp'); ?></th>
						<th><?php _e('Allowed Origins', 'headlesswp'); ?></th>
						<th><?php _e('Created', 'headlesswp'); ?></th>
						<th><?php _e('Last Used', 'headlesswp'); ?></th>
						<th><?php _e('Actions', 'headlesswp'); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($api_keys as $key): ?>
						<tr>
							<td>
								<strong><?php echo esc_html($key['name']); ?></strong>
								<?php if (!empty($key['description'])): ?>
									<br><span class="description"><?php echo esc_html($key['description']); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<code class="headlesswp-api-key"><?php echo esc_html(substr($key['key'], 0, 8) . '...'); ?></code>
								<span class="description"><?php _e('(Key is hashed for security)', 'headlesswp'); ?></span>
							</td>
							<td>
								<?php
								$permission_labels = array(
									'read' => __('Read only', 'headlesswp'),
									'write' => __('Read & Write', 'headlesswp'),
									'admin' => __('Full Access', 'headlesswp')
								);
								echo isset($permission_labels[$key['permissions']]) ?
									esc_html($permission_labels[$key['permissions']]) :
									esc_html($key['permissions']);
								?>
							</td>
							<td>
								<?php
								if (empty($key['origins'])) {
									echo '<em>' . esc_html__('All allowed origins', 'headlesswp') . '</em>';
								} else {
									$origin_names = array();
									foreach ($key['origins'] as $origin_id) {
										foreach ($cors_origins as $origin) {
											if (isset($origin['id']) && $origin['id'] === $origin_id) {
												$origin_names[] = $origin['origin'];
												break;
											}
										}
									}
									echo esc_html(implode(', ', $origin_names));
								}
								?>
							</td>
							<td>
								<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($key['created']))); ?>
							</td>
							<td>
								<?php
								if (!empty($key['last_used'])) {
									echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($key['last_used'])));
								} else {
									echo '<em>' . esc_html__('Never', 'headlesswp') . '</em>';
								}
								?>
							</td>
							<td>
								<form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to revoke this API key? This action cannot be undone.', 'headlesswp'); ?>');">
									<?php wp_nonce_field('headlesswp_revoke_key', 'headlesswp_revoke_nonce'); ?>
									<input type="hidden" name="key_id" value="<?php echo esc_attr($key['id']); ?>">
									<button type="submit" name="headlesswp_revoke_api_key" class="button button-small button-link-delete">
										<?php _e('Revoke', 'headlesswp'); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="headlesswp-card">
			<h2><?php _e('Create New API Key', 'headlesswp'); ?></h2>

			<form method="post" action="">
				<?php wp_nonce_field('headlesswp_api_keys', 'headlesswp_nonce'); ?>

				<div class="headlesswp-form-row">
					<label for="key_name"><?php _e('Name', 'headlesswp'); ?><span class="required">*</span></label>
					<input type="text" id="key_name" name="key_name" class="regular-text" required>
					<p class="description"><?php _e('A descriptive name to identify this key (e.g. "Frontend App").', 'headlesswp'); ?></p>
				</div>

				<div class="headlesswp-form-row">
					<label for="key_description"><?php _e('Description', 'headlesswp'); ?></label>
					<input type="text" id="key_description" name="key_description" class="regular-text">
					<p class="description"><?php _e('Optional description for this API key.', 'headlesswp'); ?></p>
				</div>

				<div class="headlesswp-form-row">
					<label for="key_permissions"><?php _e('Permissions', 'headlesswp'); ?></label>
					<select id="key_permissions" name="key_permissions">
						<option value="read"><?php _e('Read only', 'headlesswp'); ?></option>
						<option value="write"><?php _e('Read & Write', 'headlesswp'); ?></option>
						<option value="admin"><?php _e('Full Access', 'headlesswp'); ?></option>
					</select>
					<p class="description">
						<?php _e('The access level for this key. "Read only" is recommended for most applications.', 'headlesswp'); ?>
					</p>
				</div>

				<div class="headlesswp-form-row">
					<label><?php _e('Allowed Origins', 'headlesswp'); ?></label>

					<?php if (empty($cors_origins)): ?>
						<div class="headlesswp-notice">
							<p>
								<?php _e('You haven\'t configured any CORS origins yet. This API key will be allowed for all origins.', 'headlesswp'); ?>
								<a href="<?php echo admin_url('admin.php?page=headlesswp-security'); ?>"><?php _e('Configure CORS Settings', 'headlesswp'); ?></a>
							</p>
						</div>
					<?php else: ?>
						<div class="headlesswp-checkbox-list">
							<p>
								<label>
									<input type="checkbox" id="toggle_all_origins">
									<strong><?php _e('Select/Deselect All', 'headlesswp'); ?></strong>
								</label>
							</p>

							<?php foreach ($cors_origins as $index => $origin): ?>
								<?php
								// Make sure each origin has an ID
								$origin_id = isset($origin['id']) ? $origin['id'] : 'origin_' . $index;
								if (!isset($origin['id'])) {
									$cors_origins[$index]['id'] = $origin_id;

									// Save the updated origins with IDs
									$options['cors_origins'] = $cors_origins;
									update_option('headlesswp_options', $options);
								}
								?>
								<p>
									<label>
										<input type="checkbox" name="key_origins[]" value="<?php echo esc_attr($origin_id); ?>" class="origin-checkbox">
										<?php echo esc_html($origin['origin']); ?>
										<?php if (!empty($origin['description'])): ?>
											<span class="description">(<?php echo esc_html($origin['description']); ?>)</span>
										<?php endif; ?>
									</label>
								</p>
							<?php endforeach; ?>

							<p class="description">
								<?php _e('Select which origins can use this API key. If none are selected, all allowed origins can use this key.', 'headlesswp'); ?>
							</p>
						</div>
					<?php endif; ?>
				</div>

				<div class="headlesswp-form-row">
					<button type="submit" name="headlesswp_create_api_key" class="button button-primary">
						<?php _e('Generate API Key', 'headlesswp'); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="headlesswp-card">
			<h2><?php _e('How to Use API Keys', 'headlesswp'); ?></h2>

			<div class="headlesswp-api-usage">
				<h3><?php _e('Authentication Methods', 'headlesswp'); ?></h3>
				<p><?php _e('You can authenticate your API requests using one of the following methods:', 'headlesswp'); ?></p>

				<h4><?php _e('1. HTTP Headers (Recommended)', 'headlesswp'); ?></h4>
				<div class="headlesswp-code-block">
					<code>X-WP-API-Key: your_api_key</code>
				</div>

				<h4><?php _e('2. Query Parameters', 'headlesswp'); ?></h4>
				<div class="headlesswp-code-block">
					<code>https://example.com/wp-json/wp/v2/posts?api_key=your_api_key</code>
				</div>

				<h3><?php _e('Example Usage (JavaScript)', 'headlesswp'); ?></h3>
				<div class="headlesswp-code-block">
<pre>
fetch('https://example.com/wp-json/wp/v2/posts', {
  method: 'GET',
  headers: {
    'X-WP-API-Key': 'your_api_key',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
</pre>
				</div>

				<h3><?php _e('Security Recommendations', 'headlesswp'); ?></h3>
				<ul>
					<li><?php _e('Store API keys securely and never expose them in client-side code.', 'headlesswp'); ?></li>
					<li><?php _e('Use HTTPS to encrypt API requests and prevent credential theft.', 'headlesswp'); ?></li>
					<li><?php _e('Create separate API keys for different applications or services.', 'headlesswp'); ?></li>
					<li><?php _e('Regularly audit and revoke unused API keys.', 'headlesswp'); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize clipboard.js if present
            if (typeof ClipboardJS !== 'undefined') {
                var clipboard = new ClipboardJS('.headlesswp-copy-button');

                clipboard.on('success', function(e) {
                    var $button = $(e.trigger);
                    var originalHTML = $button.html();

                    // Show success message
                    $button.html('<span class="dashicons dashicons-yes"></span>');

                    // Reset button after 2 seconds
                    setTimeout(function() {
                        $button.html(originalHTML);
                    }, 2000);

                    e.clearSelection();
                });
            }

            // Toggle all origins checkboxes
            $('#toggle_all_origins').on('change', function() {
                $('.origin-checkbox').prop('checked', $(this).prop('checked'));
            });
        });
	</script>

	<style>
        /* API Keys specific styles */
        .headlesswp-new-key-notice {
            border-left: 4px solid #46b450;
            background-color: #ecf7ed;
        }

        .headlesswp-key-details {
            margin: 20px 0;
        }

        .headlesswp-copy-field {
            margin-bottom: 15px;
        }

        .headlesswp-copy-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .headlesswp-copy-container {
            display: flex;
        }

        .headlesswp-copy-text {
            flex: 1;
            padding: 6px 10px;
            background: #f6f7f7;
            border: 1px solid #ddd;
            border-radius: 3px 0 0 3px;
            border-right: none;
            font-family: monospace;
        }

        .headlesswp-copy-button {
            border-radius: 0 3px 3px 0 !important;
        }

        .headlesswp-warning {
            background-color: #fff8e5;
            border-left: 4px solid #ffb900;
            padding: 10px 15px;
            margin: 15px 0;
        }

        .headlesswp-notice {
            background-color: #f0f6fc;
            border-left: 4px solid #72aee6;
            padding: 10px 15px;
            margin: 15px 0;
        }

        .headlesswp-no-items {
            background-color: #f0f0f1;
            padding: 20px;
            text-align: center;
            border-radius: 3px;
        }

        .headlesswp-api-keys-table code.headlesswp-api-key {
            background: none;
            padding: 0;
        }

        .headlesswp-form-row {
            margin-bottom: 20px;
        }

        .headlesswp-form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .headlesswp-form-row .required {
            color: #d63638;
            margin-left: 3px;
        }

        .headlesswp-checkbox-list {
            background-color: #f6f7f7;
            padding: 10px 15px;
            border-radius: 3px;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
        }

        .headlesswp-code-block {
            background-color: #23282d;
            color: #fff;
            padding: 15px;
            border-radius: 3px;
            margin: 10px 0 20px;
            overflow-x: auto;
            font-family: monospace;
        }

        .headlesswp-code-block pre {
            margin: 0;
            white-space: pre-wrap;
        }

        .headlesswp-api-usage h4 {
            margin-bottom: 5px;
        }

        .headlesswp-api-usage ul {
            margin-left: 20px;
        }
	</style>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>