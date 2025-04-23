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

// Initialize options
$options = get_option('headlesswp_security_options', array(
    'enable_cors' => false,
    'allow_all_origins' => false,
    'cors_origins' => array()
));

// Process form submission
if (isset($_POST['submit']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_security_options', 'headlesswp_security_nonce');
	
	// The settings API will handle the saving
	settings_errors('headlesswp_security_options');
}
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_security_options'); ?>

        <div class="headlesswp-card">
            <h2><?php _e('Security Settings', 'headlesswp'); ?></h2>
            <p><?php _e('Configure security settings for your headless WordPress installation.', 'headlesswp'); ?></p>

            <form method="post" action="options.php">
				<?php 
                settings_fields('headlesswp_security_options');
                wp_nonce_field('headlesswp_security_options', 'headlesswp_security_nonce');
                ?>

                <!-- CORS Settings -->
                <h3><?php _e('CORS Settings', 'headlesswp'); ?></h3>
                <p><?php _e('Cross-Origin Resource Sharing (CORS) allows your API to be accessed from different domains.', 'headlesswp'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('API Key Requirement', 'headlesswp'); ?></th>
                        <td>
                            <fieldset>
                                <label for="require_api_key">
                                    <input name="headlesswp_security_options[require_api_key]" type="checkbox" id="require_api_key" value="1" <?php checked(isset($options['require_api_key']) ? $options['require_api_key'] : false); ?>>
                                    <?php _e('Require API key for all API requests', 'headlesswp'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, all API requests (both REST API and GraphQL) will require a valid API key. When disabled, API keys are optional but can still be used for authentication.', 'headlesswp'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable CORS', 'headlesswp'); ?></th>
                        <td>
                            <fieldset>
                                <label for="enable_cors">
                                    <input name="headlesswp_security_options[enable_cors]" type="checkbox" id="enable_cors" value="1" <?php checked(isset($options['enable_cors']) ? $options['enable_cors'] : false); ?>>
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
                                    <input name="headlesswp_security_options[allow_all_origins]" type="checkbox" id="allow_all_origins" value="1" <?php checked(isset($options['allow_all_origins']) ? $options['allow_all_origins'] : false); ?>>
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
                                        <input type="hidden" name="headlesswp_security_options[origin_id][<?php echo $index; ?>]" value="<?php echo esc_attr($origin_id); ?>">
                                        <input type="text" class="regular-text"
                                               name="headlesswp_security_options[origin][<?php echo $index; ?>]"
                                               value="<?php echo esc_attr($origin_data['origin']); ?>"
                                               placeholder="https://example.com">
                                    </td>
                                    <td>
                                        <input type="text" class="regular-text"
                                               name="headlesswp_security_options[origin_description][<?php echo $index; ?>]"
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
                const newRow = $('<tr>');
                newRow.append(`
                    <td>
                        <input type="hidden" name="headlesswp_security_options[origin_id][${index}]" value="${originId}">
                        <input type="text" class="regular-text" name="headlesswp_security_options[origin][${index}]" value="${origin}" placeholder="https://example.com">
                    </td>
                    <td>
                        <input type="text" class="regular-text" name="headlesswp_security_options[origin_description][${index}]" value="${description}" placeholder="<?php _e('Frontend application', 'headlesswp'); ?>">
                    </td>
                    <td>
                        <button type="button" class="button remove-origin"><?php _e('Remove', 'headlesswp'); ?></button>
                    </td>
                `);

                // Add the new row and hide the "no origins" message
                $('.no-origins').addClass('hidden');
                $('#cors-origins-table tbody').append(newRow);

                // Clear the input fields
                $('#new-origin').val('');
                $('#new-description').val('');
            });

            // Remove origin
            $(document).on('click', '.remove-origin', function() {
                $(this).closest('tr').remove();
                
                // Show "no origins" message if no origins left
                if ($('#cors-origins-table tbody tr').not('.no-origins').length === 0) {
                    $('.no-origins').removeClass('hidden');
                }
            });
        });
    </script>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>