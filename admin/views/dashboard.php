<?php
/**
 * Admin Dashboard View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('AI Health Savetnik - Dashboard', 'ai-health-savetnik'); ?></h1>

    <?php if (!aihs_is_openai_configured()): ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('OpenAI not configured!', 'ai-health-savetnik'); ?></strong>
                <?php _e('AI analysis features will not work until you configure your OpenAI API key.', 'ai-health-savetnik'); ?>
                <a href="<?php echo admin_url('admin.php?page=aihs-settings&tab=openai'); ?>" class="button button-primary">
                    <?php _e('Configure OpenAI', 'ai-health-savetnik'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="aihs-dashboard-stats">
        <div class="aihs-stat-card">
            <div class="aihs-stat-number"><?php echo intval($stats['total_responses'] ?? 0); ?></div>
            <div class="aihs-stat-label"><?php _e('Total Responses', 'ai-health-savetnik'); ?></div>
        </div>

        <div class="aihs-stat-card">
            <div class="aihs-stat-number"><?php echo intval($stats['completed_responses'] ?? 0); ?></div>
            <div class="aihs-stat-label"><?php _e('Completed Analyses', 'ai-health-savetnik'); ?></div>
        </div>

        <div class="aihs-stat-card">
            <div class="aihs-stat-number"><?php echo number_format($stats['average_score'] ?? 0, 1); ?></div>
            <div class="aihs-stat-label"><?php _e('Average Health Score', 'ai-health-savetnik'); ?></div>
        </div>

        <div class="aihs-stat-card">
            <div class="aihs-stat-number"><?php echo intval($stats['packages_generated'] ?? 0); ?></div>
            <div class="aihs-stat-label"><?php _e('Generated Packages', 'ai-health-savetnik'); ?></div>
        </div>
    </div>

    <div class="aihs-dashboard-content">
        <!-- Recent Responses -->
        <div class="aihs-dashboard-section">
            <h2><?php _e('Recent Responses', 'ai-health-savetnik'); ?></h2>

            <?php if (!empty($recent_responses)): ?>
                <div class="aihs-recent-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'ai-health-savetnik'); ?></th>
                                <th><?php _e('Email', 'ai-health-savetnik'); ?></th>
                                <th><?php _e('Score', 'ai-health-savetnik'); ?></th>
                                <th><?php _e('Status', 'ai-health-savetnik'); ?></th>
                                <th><?php _e('Date', 'ai-health-savetnik'); ?></th>
                                <th><?php _e('Actions', 'ai-health-savetnik'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_responses as $response): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($response->first_name . ' ' . $response->last_name); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($response->email); ?></td>
                                    <td>
                                        <?php if ($response->calculated_score > 0): ?>
                                            <?php echo aihs_format_score($response->calculated_score); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_labels = array(
                                            'draft' => __('Draft', 'ai-health-savetnik'),
                                            'questions_completed' => __('Questions Done', 'ai-health-savetnik'),
                                            'analysis_completed' => __('Complete', 'ai-health-savetnik')
                                        );
                                        $status_class = $response->completion_status === 'analysis_completed' ? 'completed' : 'pending';
                                        ?>
                                        <span class="aihs-status aihs-status-<?php echo $status_class; ?>">
                                            <?php echo $status_labels[$response->completion_status] ?? $response->completion_status; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($response->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=aihs-results&action=view&response_id=' . $response->id); ?>"
                                           class="button button-small">
                                            <?php _e('View', 'ai-health-savetnik'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="description">
                    <a href="<?php echo admin_url('admin.php?page=aihs-results'); ?>">
                        <?php _e('View all responses', 'ai-health-savetnik'); ?> &rarr;
                    </a>
                </p>
            <?php else: ?>
                <div class="aihs-no-data">
                    <p><?php _e('No responses yet.', 'ai-health-savetnik'); ?></p>
                    <p><?php _e('Share your health quiz to start collecting responses!', 'ai-health-savetnik'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Score Trends -->
        <?php if (!empty($score_trends)): ?>
            <div class="aihs-dashboard-section">
                <h2><?php _e('Score Trends (Last 30 Days)', 'ai-health-savetnik'); ?></h2>
                <div class="aihs-chart-container">
                    <canvas id="aihs-score-chart" width="400" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="aihs-dashboard-section">
            <h2><?php _e('Quick Actions', 'ai-health-savetnik'); ?></h2>
            <div class="aihs-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=aihs-questions&action=add'); ?>"
                   class="button button-primary">
                    <?php _e('Add New Question', 'ai-health-savetnik'); ?>
                </a>

                <a href="<?php echo admin_url('admin.php?page=aihs-settings'); ?>"
                   class="button">
                    <?php _e('Plugin Settings', 'ai-health-savetnik'); ?>
                </a>

                <?php if (aihs_is_openai_configured()): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-settings&action=test_openai'), 'aihs_test_openai', 'nonce'); ?>"
                       class="button">
                        <?php _e('Test OpenAI Connection', 'ai-health-savetnik'); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($recent_responses)): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=export_csv'), 'aihs_export_csv', 'nonce'); ?>"
                       class="button">
                        <?php _e('Export CSV', 'ai-health-savetnik'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Plugin Info -->
        <div class="aihs-dashboard-section">
            <h2><?php _e('Plugin Information', 'ai-health-savetnik'); ?></h2>
            <div class="aihs-plugin-info">
                <p>
                    <strong><?php _e('Version:', 'ai-health-savetnik'); ?></strong>
                    <?php echo AIHS_VERSION; ?>
                </p>
                <p>
                    <strong><?php _e('OpenAI Status:', 'ai-health-savetnik'); ?></strong>
                    <?php if (aihs_is_openai_configured()): ?>
                        <span style="color: #46b450;"><?php _e('Configured', 'ai-health-savetnik'); ?></span>
                    <?php else: ?>
                        <span style="color: #dc3232;"><?php _e('Not Configured', 'ai-health-savetnik'); ?></span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong><?php _e('Health Questions:', 'ai-health-savetnik'); ?></strong>
                    <?php echo count(aihs_get_health_questions()); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.aihs-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.aihs-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.aihs-stat-number {
    font-size: 2.5em;
    font-weight: 600;
    color: #2271b1;
    line-height: 1.2;
}

.aihs-stat-label {
    color: #646970;
    font-size: 13px;
    margin-top: 5px;
}

.aihs-dashboard-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.aihs-dashboard-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.aihs-dashboard-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}

.aihs-recent-table {
    margin: 15px 0;
}

.aihs-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.aihs-status-completed {
    background: #d4edda;
    color: #155724;
}

.aihs-status-pending {
    background: #fff3cd;
    color: #856404;
}

.aihs-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.aihs-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.aihs-plugin-info p {
    margin: 10px 0;
}

.aihs-chart-container {
    margin: 15px 0;
    height: 200px;
}

@media (max-width: 782px) {
    .aihs-dashboard-content {
        grid-template-columns: 1fr;
    }

    .aihs-dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php if (!empty($score_trends)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple chart implementation for score trends
    const canvas = document.getElementById('aihs-score-chart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const trends = <?php echo json_encode($score_trends); ?>;

        // Basic line chart implementation
        // This would be better with Chart.js but keeping dependencies minimal
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;

        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#2271b1';
        ctx.lineWidth = 2;
        ctx.fillStyle = '#646970';
        ctx.font = '12px Arial';

        if (trends.length > 0) {
            const maxScore = Math.max(...trends.map(t => parseFloat(t.avg_score)));
            const minScore = Math.min(...trends.map(t => parseFloat(t.avg_score)));
            const scoreRange = maxScore - minScore || 1;

            ctx.beginPath();
            trends.forEach((trend, index) => {
                const x = padding + (index / (trends.length - 1)) * (width - 2 * padding);
                const y = height - padding - ((parseFloat(trend.avg_score) - minScore) / scoreRange) * (height - 2 * padding);

                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }

                // Draw point
                ctx.fillRect(x - 2, y - 2, 4, 4);
            });
            ctx.stroke();

            // Draw axes labels
            ctx.fillText('Score: ' + minScore.toFixed(1), 5, height - 5);
            ctx.fillText(maxScore.toFixed(1), 5, 15);
        } else {
            ctx.fillText('No data available', width/2 - 50, height/2);
        }
    }
});
</script>
<?php endif; ?>