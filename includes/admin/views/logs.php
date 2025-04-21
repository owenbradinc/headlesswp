<?php
/**
 * Logs page template.
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
        <?php settings_errors('headlesswp_logs'); ?>

        <div class="headlesswp-card">
            <h2><?php _e('Logs', 'headlesswp'); ?></h2>
            <p><?php _e('View and manage HeadlessWP logs.', 'headlesswp'); ?></p>

            <!-- Filters -->
            <div class="headlesswp-logs-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="headlesswp-logs">
                    
                    <div class="headlesswp-filter-row">
                        <div class="headlesswp-filter-col">
                            <label for="level"><?php _e('Level:', 'headlesswp'); ?></label>
                            <select name="level" id="level">
                                <option value=""><?php _e('All Levels', 'headlesswp'); ?></option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?php echo esc_attr($level); ?>" <?php selected($current_level, $level); ?>>
                                        <?php echo esc_html(ucfirst($level)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="headlesswp-filter-col">
                            <label for="component"><?php _e('Component:', 'headlesswp'); ?></label>
                            <select name="component" id="component">
                                <option value=""><?php _e('All Components', 'headlesswp'); ?></option>
                                <?php foreach ($components as $component): ?>
                                    <option value="<?php echo esc_attr($component); ?>" <?php selected($current_component, $component); ?>>
                                        <?php echo esc_html($component); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="headlesswp-filter-col">
                            <input type="submit" class="button" value="<?php _e('Filter', 'headlesswp'); ?>">
                        </div>
                    </div>
                </form>

                <form method="post" action="" class="headlesswp-clear-logs">
                    <?php wp_nonce_field('headlesswp_clear_logs', 'headlesswp_nonce'); ?>
                    <input type="hidden" name="level" value="<?php echo esc_attr($current_level); ?>">
                    <input type="hidden" name="component" value="<?php echo esc_attr($current_component); ?>">
                    <input type="submit" name="headlesswp_clear_logs" class="button" value="<?php _e('Clear Logs', 'headlesswp'); ?>">
                </form>
            </div>

            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Timestamp', 'headlesswp'); ?></th>
                        <th scope="col"><?php _e('Level', 'headlesswp'); ?></th>
                        <th scope="col"><?php _e('Component', 'headlesswp'); ?></th>
                        <th scope="col"><?php _e('Message', 'headlesswp'); ?></th>
                        <th scope="col"><?php _e('User', 'headlesswp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No logs found.', 'headlesswp'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp'] ?? ''); ?></td>
                                <td>
                                    <span class="headlesswp-log-level headlesswp-log-level-<?php echo esc_attr($log['level'] ?? 'info'); ?>">
                                        <?php echo esc_html(ucfirst($log['level'] ?? 'info')); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log['component'] ?? 'general'); ?></td>
                                <td>
                                    <?php echo esc_html($log['message'] ?? ''); ?>
                                    <?php if (!empty($log['context'] ?? [])): ?>
                                        <button type="button" class="headlesswp-log-context-toggle" data-context="<?php echo esc_attr(json_encode($log['context'] ?? [])); ?>">
                                            <?php _e('View Context', 'headlesswp'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $user_id = $log['user_id'] ?? 0;
                                    if ($user_id) {
                                        $user = get_user_by('id', $user_id);
                                        if ($user) {
                                            echo esc_html($user->display_name);
                                        } else {
                                            echo esc_html($user_id);
                                        }
                                    } else {
                                        _e('System', 'headlesswp');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include HEADLESSWP_PLUGIN_DIR . 'includes/admin/views/global/footer.php'; ?>
</div>

<style>
.headlesswp-logs-filters {
    margin-bottom: 20px;
    padding: 15px;
    background: #f5f5f5;
    border: 1px solid #ddd;
}

.headlesswp-filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
}

.headlesswp-filter-col {
    display: flex;
    align-items: center;
    gap: 10px;
}

.headlesswp-log-level {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.headlesswp-log-level-error {
    background: #f8d7da;
    color: #721c24;
}

.headlesswp-log-level-warning {
    background: #fff3cd;
    color: #856404;
}

.headlesswp-log-level-info {
    background: #d1ecf1;
    color: #0c5460;
}

.headlesswp-log-level-debug {
    background: #e2e3e5;
    color: #383d41;
}

.headlesswp-log-context-toggle {
    margin-left: 10px;
    padding: 2px 5px;
    font-size: 12px;
    cursor: pointer;
}

.headlesswp-log-context {
    display: none;
    margin-top: 10px;
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    font-family: monospace;
    white-space: pre-wrap;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.headlesswp-log-context-toggle').on('click', function() {
        var $button = $(this);
        var context = JSON.parse($button.data('context'));
        var $context = $('<div class="headlesswp-log-context">' + JSON.stringify(context, null, 2) + '</div>');
        
        if ($button.next('.headlesswp-log-context').length) {
            $button.next('.headlesswp-log-context').toggle();
        } else {
            $button.after($context);
        }
        
        $button.text($button.text() === 'View Context' ? 'Hide Context' : 'View Context');
    });
});
</script> 