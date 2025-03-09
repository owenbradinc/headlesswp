<?php
/**
 * Admin header template with alert suppression from other plugins.
 *
 * @package HeadlessWP
 */
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

// Remove all admin notices from other plugins
function headlesswp_disable_other_plugin_notices() {
	// Remove all actions related to admin notices
	remove_all_actions('admin_notices');
	// Re-add only our own notices if needed
	add_action('admin_notices', 'headlesswp_admin_notices');
}
add_action('admin_head', 'headlesswp_disable_other_plugin_notices', 1);

// Function to display only HeadlessWP notices
function headlesswp_admin_notices() {
	// You can implement your own notice system here
	// This function can be empty if you don't want to show any notices
	// Or you can add specific HeadlessWP notices
}
?>
<div class="headlesswp-admin-header">
    <div class="headlesswp-logo-container">
        <img src="<?php echo HEADLESSWP_PLUGIN_URL . 'assets/headlesswp-logo.png'; ?>" alt="HeadlessWP Logo" class="headlesswp-logo">
        <h1>HeadlessWP</h1>
        <sup>v<?php echo HEADLESSWP_VERSION ?></sup>
    </div>
    <div class="headlesswp-header-nav">
        <nav>
            <ul>
                <li><a href="https://headlesswp.net/pricing" target="_blank"><?php _e('Upgrade', 'headlesswp'); ?></a></li>
                <li><a href="https://headlesswp.net/support" target="_blank"><?php _e('Support', 'headlesswp'); ?></a></li>
                <li><a href="https://headlesswp.net/docs" target="_blank"><?php _e('Documentation', 'headlesswp'); ?></a></li>
            </ul>
        </nav>
    </div>
</div>
<hr class="headlesswp-hr">
<div class="headlesswp-admin-title">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
</div>

<!-- CSS to hide any notices that might slip through -->
<style type="text/css">
    .notice:not(.headlesswp-notice),
    .updated:not(.headlesswp-updated),
    .update-nag:not(.headlesswp-update-nag) {
        display: none !important;
    }
</style>