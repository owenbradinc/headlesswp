<?php
/**
 * Setup page template with first-time activation checklist.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Get options
$options = get_option('headlesswp_options', array());

// Check if setup has been completed
$setup_completed = !empty($options['setup_completed']);

// Process form submission for setup completion
if (isset($_POST['headlesswp_complete_setup']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_complete_setup', 'headlesswp_setup_nonce');

	// Update setup status in options
	$options['setup_completed'] = true;
	update_option('headlesswp_options', $options);

	// Show notice
	add_settings_error(
		'headlesswp_setup',
		'headlesswp_setup_completed',
		__('Setup completed successfully! Your WordPress site is now ready for headless mode.', 'headlesswp'),
		'updated'
	);

	$setup_completed = true;
}

// Process checklist item updates
if (isset($_POST['headlesswp_update_checklist']) && current_user_can('manage_options')) {
	check_admin_referer('headlesswp_update_checklist', 'headlesswp_checklist_nonce');

	// Get checklist items from POST
	$checklist_items = isset($_POST['checklist_items']) ? (array) $_POST['checklist_items'] : array();

	// Update options
	$options['checklist'] = $checklist_items;
	update_option('headlesswp_options', $options);

	// Show notice
	add_settings_error(
		'headlesswp_setup',
		'headlesswp_checklist_updated',
		__('Setup progress saved.', 'headlesswp'),
		'updated'
	);
}

// Get checklist status
$checklist = isset($options['checklist']) ? $options['checklist'] : array();

// Define checklist items
$checklist_groups = array(
	'essential' => array(
		'title' => __('Essential Setup', 'headlesswp'),
		'description' => __('Complete these steps to get your headless WordPress site up and running.', 'headlesswp'),
		'items' => array(
			'enable_rest_api' => array(
				'label' => __('Enable REST API', 'headlesswp'),
				'description' => __('Make sure the WordPress REST API is enabled and accessible.', 'headlesswp'),
				'link' => admin_url('admin.php?page=headlesswp-api'),
				'checked' => !empty($checklist['enable_rest_api'])
			),
			'configure_cors' => array(
				'label' => __('Configure CORS Settings', 'headlesswp'),
				'description' => __('Set up Cross-Origin Resource Sharing to allow your frontend to communicate with WordPress.', 'headlesswp'),
				'link' => admin_url('admin.php?page=headlesswp-security'),
				'checked' => !empty($checklist['configure_cors'])
			),
			'create_application_password' => array(
				'label' => __('Create Application Password', 'headlesswp'),
				'description' => __('Set up an application password for secure API authentication.', 'headlesswp'),
				'link' => admin_url('profile.php#application-passwords-section'),
				'checked' => !empty($checklist['create_application_password'])
			),
		)
	),
	'content' => array(
		'title' => __('Content Preparation', 'headlesswp'),
		'description' => __('Prepare your content for headless delivery.', 'headlesswp'),
		'items' => array(
			'configure_permalinks' => array(
				'label' => __('Configure Permalinks', 'headlesswp'),
				'description' => __('Set up SEO-friendly permalinks for your content.', 'headlesswp'),
				'link' => admin_url('options-permalink.php'),
				'checked' => !empty($checklist['configure_permalinks'])
			),
			'setup_media' => array(
				'label' => __('Set Up Media Handling', 'headlesswp'),
				'description' => __('Configure image sizes and media settings for optimal delivery.', 'headlesswp'),
				'link' => admin_url('options-media.php'),
				'checked' => !empty($checklist['setup_media'])
			),
		)
	),
	'advanced' => array(
		'title' => __('Advanced Configuration', 'headlesswp'),
		'description' => __('Fine-tune your headless setup with these additional steps.', 'headlesswp'),
		'items' => array(
			'disable_frontend' => array(
				'label' => __('Disable Frontend (Optional)', 'headlesswp'),
				'description' => __('Redirect all frontend requests to your API or frontend application.', 'headlesswp'),
				'link' => admin_url('admin.php?page=headlesswp'),
				'checked' => !empty($checklist['disable_frontend'])
			),
			'configure_caching' => array(
				'label' => __('Configure API Caching', 'headlesswp'),
				'description' => __('Set up caching for improved API performance.', 'headlesswp'),
				'link' => admin_url('admin.php?page=headlesswp-settings'),
				'checked' => !empty($checklist['configure_caching'])
			),
			'setup_webhook' => array(
				'label' => __('Set Up Webhooks for Content Updates', 'headlesswp'),
				'description' => __('Configure webhooks to notify your frontend when content changes.', 'headlesswp'),
				'link' => admin_url('admin.php?page=headlesswp-settings'),
				'checked' => !empty($checklist['setup_webhook'])
			),
		)
	),
);

// Calculate progress
$total_items = 0;
$completed_items = 0;

foreach ($checklist_groups as $group) {
	foreach ($group['items'] as $key => $item) {
		$total_items++;
		if (!empty($checklist[$key])) {
			$completed_items++;
		}
	}
}

$progress_percentage = $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
		<?php settings_errors('headlesswp_setup'); ?>

		<?php if ($setup_completed): ?>
            <div class="headlesswp-card headlesswp-setup-complete">
                <div class="headlesswp-setup-complete-header">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h2><?php _e('Setup Complete!', 'headlesswp'); ?></h2>
                </div>
                <p><?php _e('Congratulations! You have completed the initial setup for HeadlessWP. Your WordPress site is now configured for headless operation.', 'headlesswp'); ?></p>

                <div class="headlesswp-next-steps">
                    <h3><?php _e('Next Steps', 'headlesswp'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=headlesswp-api'); ?>"><?php _e('Explore your API endpoints', 'headlesswp'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=headlesswp-security'); ?>"><?php _e('Review security settings', 'headlesswp'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=headlesswp'); ?>"><?php _e('Return to dashboard', 'headlesswp'); ?></a></li>
                    </ul>
                </div>

                <div class="headlesswp-setup-actions">
                    <a href="<?php echo admin_url('admin.php?page=headlesswp'); ?>" class="button button-primary"><?php _e('Go to Dashboard', 'headlesswp'); ?></a>
                    <a href="<?php echo admin_url('admin.php?page=headlesswp-setup&reset=1'); ?>" class="button"><?php _e('View Setup Checklist Again', 'headlesswp'); ?></a>
                </div>
            </div>
		<?php else: ?>
            <div class="headlesswp-card headlesswp-setup-welcome">
                <h2><?php _e('Welcome to HeadlessWP!', 'headlesswp'); ?></h2>
                <p><?php _e('Complete the steps below to set up your WordPress site for headless operation. This checklist will guide you through the essential configuration needed for a successful headless WordPress implementation.', 'headlesswp'); ?></p>

                <div class="headlesswp-progress-bar-container">
                    <div class="headlesswp-progress-bar">
                        <div class="headlesswp-progress-bar-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                    </div>
                    <div class="headlesswp-progress-status">
						<?php printf(__('Progress: %d%%', 'headlesswp'), $progress_percentage); ?>
                        <span class="headlesswp-progress-steps"><?php printf(__('%d of %d tasks completed', 'headlesswp'), $completed_items, $total_items); ?></span>
                    </div>
                </div>
            </div>

            <form method="post" action="" id="headlesswp-checklist-form">
				<?php wp_nonce_field('headlesswp_update_checklist', 'headlesswp_checklist_nonce'); ?>

				<?php foreach ($checklist_groups as $group_key => $group): ?>
                    <div class="headlesswp-card headlesswp-setup-group">
                        <h3><?php echo esc_html($group['title']); ?></h3>
                        <p class="headlesswp-group-description"><?php echo esc_html($group['description']); ?></p>

                        <div class="headlesswp-checklist">
							<?php foreach ($group['items'] as $item_key => $item): ?>
                                <div class="headlesswp-checklist-item <?php echo $item['checked'] ? 'checked' : ''; ?>">
                                    <label class="headlesswp-checkbox-label">
                                        <input type="checkbox" name="checklist_items[<?php echo esc_attr($item_key); ?>]" value="1" <?php checked($item['checked'], true); ?>>
                                        <span class="headlesswp-checkbox-custom">
                                            <span class="dashicons dashicons-yes"></span>
                                        </span>
                                        <span class="headlesswp-checkbox-text"><?php echo esc_html($item['label']); ?></span>
                                    </label>
                                    <p class="headlesswp-checklist-description"><?php echo esc_html($item['description']); ?></p>
                                    <a href="<?php echo esc_url($item['link']); ?>" class="button headlesswp-checklist-button"><?php _e('Configure', 'headlesswp'); ?></a>
                                </div>
							<?php endforeach; ?>
                        </div>
                    </div>
				<?php endforeach; ?>

                <div class="headlesswp-card headlesswp-setup-actions-card">
                    <div class="headlesswp-setup-actions">
                        <button type="submit" name="headlesswp_update_checklist" class="button button-secondary"><?php _e('Save Progress', 'headlesswp'); ?></button>

						<?php if ($progress_percentage >= 100): ?>
                            <button type="submit" name="headlesswp_complete_setup" class="button button-primary"><?php _e('Complete Setup', 'headlesswp'); ?></button>
							<?php wp_nonce_field('headlesswp_complete_setup', 'headlesswp_setup_nonce'); ?>
						<?php else: ?>
                            <span class="headlesswp-setup-incomplete-notice"><?php _e('Complete all tasks to finish setup', 'headlesswp'); ?></span>
						<?php endif; ?>
                    </div>
                </div>
            </form>
		<?php endif; ?>
    </div>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>

<style>
    /* Progress Bar */
    .headlesswp-progress-bar-container {
        margin: 20px 0;
    }

    .headlesswp-progress-bar {
        height: 20px;
        background-color: #f0f0f1;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .headlesswp-progress-bar-fill {
        height: 100%;
        background-color: #2271b1;
        transition: width 0.3s ease;
    }

    .headlesswp-progress-status {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
        font-weight: 500;
    }

    .headlesswp-progress-steps {
        color: #646970;
    }

    /* Checklist Styles */
    .headlesswp-checklist {
        margin-top: 15px;
    }

    .headlesswp-checklist-item {
        padding: 15px;
        margin-bottom: 10px;
        background-color: #f6f7f7;
        border-radius: 5px;
        border-left: 4px solid #dcdcde;
        transition: all 0.3s ease;
    }

    .headlesswp-checklist-item.checked {
        background-color: #f0f6e6;
        border-left-color: #46b450;
    }

    .headlesswp-checkbox-label {
        display: flex;
        align-items: flex-start;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
    }

    .headlesswp-checkbox-label input[type="checkbox"] {
        display: none;
    }

    .headlesswp-checkbox-custom {
        display: inline-block;
        width: 22px;
        height: 22px;
        margin-right: 10px;
        border: 2px solid #dcdcde;
        border-radius: 3px;
        position: relative;
        background-color: #fff;
        flex-shrink: 0;
    }

    .headlesswp-checkbox-custom .dashicons {
        position: absolute;
        top: -2px;
        left: -2px;
        color: #fff;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .headlesswp-checkbox-label input[type="checkbox"]:checked + .headlesswp-checkbox-custom {
        background-color: #46b450;
        border-color: #46b450;
    }

    .headlesswp-checkbox-label input[type="checkbox"]:checked + .headlesswp-checkbox-custom .dashicons {
        opacity: 1;
    }

    .headlesswp-checklist-description {
        margin: 10px 0 15px 32px;
        color: #646970;
    }

    .headlesswp-checklist-button {
        margin-left: 32px !important;
    }

    /* Group Styles */
    .headlesswp-setup-group {
        margin-bottom: 20px;
    }

    .headlesswp-group-description {
        color: #646970;
        margin-bottom: 20px;
    }

    /* Setup Complete Styles */
    .headlesswp-setup-complete-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .headlesswp-setup-complete-header .dashicons {
        font-size: 30px;
        width: 30px;
        height: 30px;
        margin-right: 10px;
        color: #46b450;
    }

    .headlesswp-setup-complete-header h2 {
        margin: 0;
        padding: 0;
        color: #46b450;
    }

    .headlesswp-next-steps {
        background-color: #f6f7f7;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }

    .headlesswp-next-steps h3 {
        margin-top: 0;
    }

    .headlesswp-next-steps ul {
        margin-left: 20px;
    }

    .headlesswp-next-steps a {
        text-decoration: none;
    }

    /* Action Buttons */
    .headlesswp-setup-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 10px;
    }

    .headlesswp-setup-actions-card {
        background-color: #f8f9fa;
        border-top: 1px solid #dcdcde;
    }

    .headlesswp-setup-incomplete-notice {
        margin-left: 10px;
        color: #646970;
        font-style: italic;
    }

    .headlesswp-setup-actions .button {
        margin-left: 10px;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Auto-save checklist when items are checked
        $('.headlesswp-checklist input[type="checkbox"]').on('change', function() {
            var $item = $(this).closest('.headlesswp-checklist-item');

            if ($(this).is(':checked')) {
                $item.addClass('checked');
            } else {
                $item.removeClass('checked');
            }

            // Automatically submit the form when a checkbox is changed
            // Uncomment the line below if you want this behavior
            // $('#headlesswp-checklist-form').submit();
        });
    });
</script>