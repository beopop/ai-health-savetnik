<?php
/**
 * Results List View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$list_table = new WP_List_Table();
$pagenum = $list_table->get_pagenum();
$per_page = 20;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Quiz Results', 'ai-health-savetnik'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="aihs-results">

                <select name="status">
                    <option value=""><?php _e('All Statuses', 'ai-health-savetnik'); ?></option>
                    <option value="draft" <?php selected($status, 'draft'); ?>><?php _e('Draft', 'ai-health-savetnik'); ?></option>
                    <option value="questions_completed" <?php selected($status, 'questions_completed'); ?>><?php _e('Questions Completed', 'ai-health-savetnik'); ?></option>
                    <option value="analysis_completed" <?php selected($status, 'analysis_completed'); ?>><?php _e('Analysis Completed', 'ai-health-savetnik'); ?></option>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by name or email...', 'ai-health-savetnik'); ?>">

                <input type="submit" class="button" value="<?php _e('Filter', 'ai-health-savetnik'); ?>">

                <?php if ($search || $status): ?>
                    <a href="<?php echo admin_url('admin.php?page=aihs-results'); ?>" class="button">
                        <?php _e('Clear Filters', 'ai-health-savetnik'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="alignright actions">
            <?php if (!empty($responses)): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=export_csv'), 'aihs_export_csv', 'nonce'); ?>"
                   class="button">
                    <?php _e('Export CSV', 'ai-health-savetnik'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($responses)): ?>
        <div class="notice notice-info">
            <p>
                <?php if ($search || $status): ?>
                    <?php _e('No responses found matching your criteria.', 'ai-health-savetnik'); ?>
                <?php else: ?>
                    <?php _e('No quiz responses yet.', 'ai-health-savetnik'); ?>
                    <?php _e('Share your health quiz to start collecting responses!', 'ai-health-savetnik'); ?>
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <!-- Results Summary -->
        <div class="aihs-results-summary">
            <div class="aihs-summary-stats">
                <div class="aihs-stat">
                    <span class="number"><?php echo count($responses); ?></span>
                    <span class="label"><?php _e('Showing', 'ai-health-savetnik'); ?></span>
                </div>
                <div class="aihs-stat">
                    <span class="number"><?php echo $total_responses; ?></span>
                    <span class="label"><?php _e('Total', 'ai-health-savetnik'); ?></span>
                </div>
                <?php
                $completed_count = count(array_filter($responses, function($r) {
                    return $r->completion_status === 'analysis_completed';
                }));
                ?>
                <div class="aihs-stat">
                    <span class="number"><?php echo $completed_count; ?></span>
                    <span class="label"><?php _e('Completed', 'ai-health-savetnik'); ?></span>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </th>
                    <th scope="col" class="manage-column column-name">
                        <?php _e('Name', 'ai-health-savetnik'); ?>
                    </th>
                    <th scope="col" class="manage-column column-email">
                        <?php _e('Email', 'ai-health-savetnik'); ?>
                    </th>
                    <th scope="col" class="manage-column column-score">
                        <?php _e('Health Score', 'ai-health-savetnik'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('Status', 'ai-health-savetnik'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Date', 'ai-health-savetnik'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'ai-health-savetnik'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responses as $response): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="response_ids[]" value="<?php echo esc_attr($response->id); ?>">
                        </th>
                        <td class="column-name">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=aihs-results&action=view&response_id=' . $response->id); ?>">
                                    <?php echo esc_html($response->first_name . ' ' . $response->last_name); ?>
                                </a>
                            </strong>
                            <?php if ($response->age): ?>
                                <br><small><?php printf(__('Age: %d', 'ai-health-savetnik'), $response->age); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-email">
                            <a href="mailto:<?php echo esc_attr($response->email); ?>">
                                <?php echo esc_html($response->email); ?>
                            </a>
                            <?php if ($response->phone): ?>
                                <br><small><?php echo esc_html($response->phone); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-score">
                            <?php if ($response->calculated_score > 0): ?>
                                <div class="aihs-score-display">
                                    <?php echo aihs_format_score($response->calculated_score, true); ?>
                                </div>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus" style="color: #646970;"></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <?php
                            $status_info = array(
                                'draft' => array('label' => __('Draft', 'ai-health-savetnik'), 'class' => 'draft'),
                                'questions_completed' => array('label' => __('Questions Done', 'ai-health-savetnik'), 'class' => 'processing'),
                                'analysis_completed' => array('label' => __('Complete', 'ai-health-savetnik'), 'class' => 'completed')
                            );

                            $current_status = $status_info[$response->completion_status] ?? array(
                                'label' => $response->completion_status,
                                'class' => 'unknown'
                            );
                            ?>
                            <span class="aihs-status aihs-status-<?php echo $current_status['class']; ?>">
                                <?php echo esc_html($current_status['label']); ?>
                            </span>
                        </td>
                        <td class="column-date">
                            <abbr title="<?php echo esc_attr(date_i18n('Y/m/d g:i:s A', strtotime($response->created_at))); ?>">
                                <?php echo date_i18n(get_option('date_format'), strtotime($response->created_at)); ?>
                            </abbr>
                            <br><small><?php echo human_time_diff(strtotime($response->created_at), current_time('timestamp')); ?> <?php _e('ago', 'ai-health-savetnik'); ?></small>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=aihs-results&action=view&response_id=' . $response->id); ?>">
                                        <?php _e('View', 'ai-health-savetnik'); ?>
                                    </a>
                                </span>

                                <?php if ($response->completion_status === 'questions_completed'): ?>
                                    <span class="regenerate"> |
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=regenerate_analysis&response_id=' . $response->id), 'aihs_regenerate_analysis', 'nonce'); ?>"
                                           style="color: #0073aa;">
                                            <?php _e('Generate Analysis', 'ai-health-savetnik'); ?>
                                        </a>
                                    </span>
                                <?php elseif ($response->completion_status === 'analysis_completed'): ?>
                                    <span class="regenerate"> |
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=regenerate_analysis&response_id=' . $response->id), 'aihs_regenerate_analysis', 'nonce'); ?>"
                                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to regenerate the AI analysis?', 'ai-health-savetnik')); ?>')">
                                            <?php _e('Regenerate', 'ai-health-savetnik'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>

                                <span class="delete"> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=delete_response&response_id=' . $response->id), 'aihs_delete_response', 'nonce'); ?>"
                                       style="color: #a00;"
                                       onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this response? This action cannot be undone.', 'ai-health-savetnik')); ?>')">
                                        <?php _e('Delete', 'ai-health-savetnik'); ?>
                                    </a>
                                </span>

                                <?php if ($response->completion_status === 'analysis_completed'): ?>
                                    <?php
                                    $public_id = aihs_generate_public_id($response->id);
                                    $public_url = add_query_arg('aihs_public_id', $public_id, home_url());
                                    ?>
                                    <span class="public-link"> |
                                        <a href="<?php echo esc_url($public_url); ?>" target="_blank">
                                            <?php _e('Public Link', 'ai-health-savetnik'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column">
                        <input type="checkbox" id="cb-select-all-2">
                    </th>
                    <th scope="col" class="manage-column"><?php _e('Name', 'ai-health-savetnik'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Email', 'ai-health-savetnik'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Health Score', 'ai-health-savetnik'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Status', 'ai-health-savetnik'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Date', 'ai-health-savetnik'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Actions', 'ai-health-savetnik'); ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- Pagination -->
        <?php
        $total_pages = ceil($total_responses / $per_page);
        if ($total_pages > 1):
        ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_responses, 'ai-health-savetnik'), number_format_i18n($total_responses)); ?>
                </span>
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $pagenum
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.aihs-results-summary {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 10px 0 20px 0;
    padding: 15px;
}

.aihs-summary-stats {
    display: flex;
    gap: 30px;
}

.aihs-stat {
    text-align: center;
}

.aihs-stat .number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
}

.aihs-stat .label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.aihs-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.aihs-status-draft {
    background: #f0f0f1;
    color: #646970;
}

.aihs-status-processing {
    background: #fff3cd;
    color: #856404;
}

.aihs-status-completed {
    background: #d4edda;
    color: #155724;
}

.aihs-status-unknown {
    background: #f8d7da;
    color: #721c24;
}

.aihs-score-display {
    font-weight: 600;
}

.column-name {
    width: 20%;
}

.column-email {
    width: 25%;
}

.column-score {
    width: 15%;
}

.column-status {
    width: 12%;
}

.column-date {
    width: 15%;
}

.column-actions {
    width: 13%;
}

.row-actions {
    font-size: 13px;
}

.tablenav .actions {
    padding: 2px 0;
}

.tablenav .actions select,
.tablenav .actions input[type="search"] {
    margin-right: 5px;
}

@media (max-width: 782px) {
    .aihs-summary-stats {
        flex-direction: column;
        gap: 15px;
    }

    .column-email,
    .column-date {
        display: none;
    }

    .row-actions span {
        display: block;
        margin: 2px 0;
    }

    .row-actions span:before {
        content: '';
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkboxes functionality
    $('#cb-select-all-1, #cb-select-all-2').change(function() {
        var checked = $(this).prop('checked');
        $('input[name="response_ids[]"]').prop('checked', checked);
    });

    // Individual checkbox change
    $('input[name="response_ids[]"]').change(function() {
        var total = $('input[name="response_ids[]"]').length;
        var checked = $('input[name="response_ids[]"]:checked').length;

        $('#cb-select-all-1, #cb-select-all-2').prop('checked', total === checked);
    });
});
</script>