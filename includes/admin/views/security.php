<?php
/**
 * Security settings page template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Process form submission
if (isset($_POST['submit']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_security_options', 'headlesswp_security_nonce');

	// Prepare options array
	$updated_options = $options;

	// CORS settings
	$updated_options['enable_cors'] = isset($_POST['enable_cors']) ? true : false;
	$updated_options['allow_all_origins'] = isset($_POST['allow_all_origins']) ? true : false;

	// Process origins
	$origins = array();
	if (isset($_POST['origin']) && is_array($_POST['origin'])) {
		foreach ($_POST['origin'] as $index => $origin_url) {
			$origin_url = trim(sanitize_text_field($origin_url));
			if (!empty($origin_url)) {
				$origin_id = isset($_POST['origin_id'][$index]) ? sanitize_text_field($_POST['origin_id'][$index]) : 'origin_' . uniqid();
				$origin_description = isset($_POST['origin_description'][$index]) ? sanitize_text_field($_POST['origin_description'][$index]) : '';

				$origins[] = array(
					'id' => $origin_id,
					'origin' => $origin_url,
					'description' => $origin_description
				);
			}
		}
	}
	$updated_options['cors_origins'] = $origins;

	// Save options
	update_option('headlesswp_options', $updated_options);

	// Add success message
	add_settings_error(
		'headlesswp_security_options',
		'settings_updated',
		__('Security settings saved successfully.', 'headlesswp'),
		'success'
	);

	// Refresh options
	$options = get_option('headlesswp_options', array());
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_security_options'); ?>

        <div class="headlesswp-card">
            <h2><?php _e('Security Settings', 'headlesswp'); ?></h2>
            <p><?php _e('Configure security settings for your headless WordPress installation.', 'headlesswp'); ?></p>

            <form method="post" action="">
				<?php wp_nonce_field('headlesswp_security_options', 'headlesswp_security_nonce'); ?>

                <!-- CORS Settings -->
                <h3><?php _e('CORS Settings', 'headlesswp'); ?></h3>
                <p><?php _e('Cross-Origin Resource Sharing (CORS) allows your API to be accessed from different domains.', 'headlesswp'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable CORS', 'headlesswp'); ?></th>
                        <td>
                            <fieldset>
                                <label for="enable_cors">
                                    <input name="enable_cors" type="checkbox" id="enable_cors" value="1" <?php checked(!empty($options['enable_cors'])); ?>>
									<?php _e('Enable Cross-Origin Resource Sharing for the REST API', 'headlesswp'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Allow All Origins', 'headlesswp'); ?></th>
                        <td>
                            <fieldset>
                                <label for="allow_all_origins">
                                    <input name="allow_all_origins" type="checkbox" id="allow_all_origins" value="1" <?php checked(!empty($options['allow_all_origins'])); ?>>
									<?php _e('Allow requests from all origins (not recommended for production)', 'headlesswp'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <!-- Origins List -->
                <div id="cors-origins-container" class="cors-origins-container" <?php echo !empty($options['allow_all_origins']) ? 'style="display:none;"' : ''; ?>>
                    <h3><?php _e('Allowed Origins', 'headlesswp'); ?></h3>
                    <p><?php _e('Specify which origins are allowed to access your REST API.', 'headlesswp'); ?></p>

                    <table class="wp-list-table widefat fixed striped" id="cors-origins-table">
                        <thead>
                        <tr>
                            <th><?php _e('Origin URL', 'headlesswp'); ?></th>
                            <th><?php _e('Description', 'headlesswp'); ?></th>
                            <th><?php _e('Actions', 'headlesswp'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						if (!empty($options['cors_origins']) && is_array($options['cors_origins'])) {
							foreach ($options['cors_origins'] as $index => $origin_data) {
								// Ensure each origin has an ID
								$origin_id = isset($origin_data['id']) ? $origin_data['id'] : 'origin_' . uniqid();
								?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="origin_id[<?php echo $index; ?>]" value="<?php echo esc_attr($origin_id); ?>">
                                        <input type="text" class="regular-text"
                                               name="origin[<?php echo $index; ?>]"
                                               value="<?php echo esc_attr($origin_data['origin']); ?>"
                                               placeholder="https://example.com">
                                    </td>
                                    <td>
                                        <input type="text" class="regular-text"
                                               name="origin_description[<?php echo $index; ?>]"
                                               value="<?php echo isset($origin_data['description']) ? esc_attr($origin_data['description']) : ''; ?>"
                                               placeholder="<?php _e('Frontend application', 'headlesswp'); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="button remove-origin"><?php _e('Remove', 'headlesswp'); ?></button>
                                    </td>
                                </tr>
								<?php
							}
						}
						?>
                        <tr class="no-origins <?php echo (!empty($options['cors_origins']) && is_array($options['cors_origins'])) ? 'hidden' : ''; ?>">
                            <td colspan="3"><?php _e('No origins added yet. Add one below.', 'headlesswp'); ?></td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>
                                <input type="text" id="new-origin" class="regular-text" placeholder="https://example.com">
                            </td>
                            <td>
                                <input type="text" id="new-description" class="regular-text" placeholder="<?php _e('Frontend application', 'headlesswp'); ?>">
                            </td>
                            <td>
                                <button type="button" class="button button-primary" id="add-origin"><?php _e('Add Origin', 'headlesswp'); ?></button>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                    <p class="description"><?php _e('Add the full URLs of origins that should be allowed to access your REST API (e.g., https://example.com).', 'headlesswp'); ?></p>
                </div>

				<?php submit_button(); ?>
            </form>
        </div>
    </div>

    <!-- JavaScript for CORS Origins Management -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle visibility of the allowed origins table based on "Allow All Origins" checkbox
            $('#allow_all_origins').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#cors-origins-container').hide();
                } else {
                    $('#cors-origins-container').show();
                }
            });

            // Add new origin
            $('#add-origin').on('click', function() {
                const origin = $('#new-origin').val().trim();
                const description = $('#new-description').val().trim();

                if (origin === '') {
                    alert('<?php _e('Please enter a valid origin URL', 'headlesswp'); ?>');
                    return;
                }

                // Get current count of origins for the new index
                const index = $('#cors-origins-table tbody tr').not('.no-origins').length;
                const originId = 'origin_' + Math.random().toString(36).substr(2, 9);

                // Create a new row
                const newRow = `
                    <tr>
                        <td>
                            <input type="hidden" name="origin_id[${index}]" value="${originId}">
                            <input type="text" class="regular-text"
                                name="origin[${index}]"
                                value="${origin}">
                        </td>
                        <td>
                            <input type="text" class="regular-text"
                                name="origin_description[${index}]"
                                value="${description}">
                        </td>
                        <td>
                            <button type="button" class="button remove-origin"><?php _e('Remove', 'headlesswp'); ?></button>
                        </td>
                    </tr>
                `;

                // Hide the "no origins" message
                $('.no-origins').addClass('hidden');

                // Add the new row before the last row (which is the input form)
                $('#cors-origins-table tbody').append(newRow);

                // Clear the inputs
                $('#new-origin').val('');
                $('#new-description').val('');
            });

            // Remove origin (use event delegation for dynamically added elements)
            $('#cors-origins-table').on('click', '.remove-origin', function() {
                $(this).closest('tr').remove();

                // Show the "no origins" message if there are no origins
                if ($('#cors-origins-table tbody tr').not('.no-origins').length === 0) {
                    $('.no-origins').removeClass('hidden');
                }

                // Reindex the form fields to prevent gaps in the array
                reindexOrigins();
            });

            // Function to reindex the form fields
            function reindexOrigins() {
                $('#cors-origins-table tbody tr').not('.no-origins').each(function(index) {
                    $(this).find('input').each(function() {
                        const name = $(this).attr('name');
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    });
                });
            }
        });
    </script>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>