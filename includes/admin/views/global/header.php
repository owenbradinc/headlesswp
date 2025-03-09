<?php
/**
 * Admin header template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
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