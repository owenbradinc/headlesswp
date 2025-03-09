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
?>

<div class="wrap headlesswp-admin-wrap">
	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/header.php'; ?>

    <div class="headlesswp-admin-content">
        <div class="headlesswp-card">
            <form action="options.php" method="post">
				<?php
				settings_fields('headlesswp_security_options');
				do_settings_sections('headlesswp_security');
				submit_button();
				?>
            </form>
        </div>
    </div>

	<?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>