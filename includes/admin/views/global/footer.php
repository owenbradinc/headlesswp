<?php
/**
 * Admin footer template.
 *
 * @package HeadlessWP
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}
?>
<hr class="headlesswp-hr">
<div class="headlesswp-admin-footer">
	<div class="headlesswp-footer-content">
		<p>
			<?php printf(
				__('HeadlessWP v%s | <a href="%s" target="_blank">Help</a> | <a href="%s" target="_blank">Support</a>', 'headlesswp'),
				HEADLESSWP_VERSION,
				'https://headlesswp.net/docs',
				'https://headlesswp.net/support'
			); ?>
		</p>
	</div>
</div>