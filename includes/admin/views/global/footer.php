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
				__('Copyright &copy; %s Weekend Labs, LLC. All Rights Reserved. | <a href="%s" target="_blank">Help</a> | <a href="%s" target="_blank">Support</a>', 'headlesswp'),
				date('Y'),
				'https://headlesswp.com/docs',
				'https://headlesswp.com/support'
			); ?>
        </p>
    </div>
</div>
