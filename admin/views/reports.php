<?php
/**
 * Reports & Analytics View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Calculate date ranges
$today = current_time('Y-m-d');
$last_30_days = date('Y-m-d', strtotime('-30 days'));
$last_7_days = date('Y-m-d', strtotime('-7 days'));

// Get additional statistics
global $wpdb;
$responses_table = $wpdb->prefix . AIHS_RESPONSES_TABLE;
$packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

// Score distribution by time periods
$score_by_period = array(
    'last_7_days' => $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(calculated_score) FROM $responses_table WHERE calculated_score > 0 AND created_at >= %s",
        $last_7_days
    )),
    'last_30_days' => $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(calculated_score) FROM $responses_table WHERE calculated_score > 0 AND created_at >= %s",
        $last_30_days
    )),
    'all_time' => $wpdb->get_var(
        "SELECT AVG(calculated_score) FROM $responses_table WHERE calculated_score > 0"
    )
);

// Response completion rates
$completion_stats = array(
    'total_started' => $wpdb->get_var("SELECT COUNT(*) FROM $responses_table"),
    'questions_completed' => $wpdb->get_var("SELECT COUNT(*) FROM $responses_table WHERE completion_status IN ('questions_completed', 'analysis_completed')"),
    'analysis_completed' => $wpdb->get_var("SELECT COUNT(*) FROM $responses_table WHERE completion_status = 'analysis_completed'")
);

// Calculate percentages
$completion_stats['questions_rate'] = $completion_stats['total_started'] > 0
    ? round(($completion_stats['questions_completed'] / $completion_stats['total_started']) * 100, 1)
    : 0;
$completion_stats['analysis_rate'] = $completion_stats['total_started'] > 0
    ? round(($completion_stats['analysis_completed'] / $completion_stats['total_started']) * 100, 1)
    : 0;

// Popular products (from packages)
$popular_products = $wpdb->get_results(
    "SELECT product_ids, COUNT(*) as package_count
     FROM $packages_table
     WHERE product_ids IS NOT NULL AND product_ids != ''
     GROUP BY product_ids
     ORDER BY package_count DESC
     LIMIT 10"
);

// Package generation stats
$package_generation_stats = array();
$package_types = aihs_get_package_types();

foreach ($package_types as $type => $info) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $packages_table WHERE package_type = %s",
        $type
    ));
    $package_generation_stats[$type] = array(
        'count' => intval($count),
        'label' => $info['label']
    );
}

// Get top health issues (from answers)
$health_issues = array();
$questions = aihs_get_health_questions();

foreach ($questions as $question) {
    $yes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $responses_table
         WHERE answers LIKE %s AND completion_status IN ('questions_completed', 'analysis_completed')",
        '%"' . $question['id'] . '":"Da"%'
    ));

    if ($yes_count > 0) {
        $health_issues[] = array(
            'question' => $question['text'],
            'count' => intval($yes_count),
            'weight' => $question['weight']
        );
    }
}

// Sort by count
usort($health_issues, function($a, $b) {
    return $b['count'] - $a['count'];
});

$health_issues = array_slice($health_issues, 0, 10);
?>

<div class="wrap">
    <h1><?php _e('Reports & Analytics', 'ai-health-savetnik'); ?></h1>

    <!-- Overview Stats -->
    <div class="aihs-reports-overview">
        <div class="aihs-overview-cards">
            <div class="aihs-overview-card">
                <div class="aihs-card-content">
                    <h3><?php _e('Total Responses', 'ai-health-savetnik'); ?></h3>
                    <div class="aihs-big-number"><?php echo intval($stats['total_responses'] ?? 0); ?></div>
                    <p class="aihs-card-subtitle">
                        <?php echo intval($stats['completed_responses'] ?? 0); ?> <?php _e('completed', 'ai-health-savetnik'); ?>
                    </p>
                </div>
            </div>

            <div class="aihs-overview-card">
                <div class="aihs-card-content">
                    <h3><?php _e('Average Health Score', 'ai-health-savetnik'); ?></h3>
                    <div class="aihs-big-number"><?php echo number_format($stats['average_score'] ?? 0, 1); ?></div>
                    <p class="aihs-card-subtitle"><?php _e('out of 100', 'ai-health-savetnik'); ?></p>
                </div>
            </div>

            <div class="aihs-overview-card">
                <div class="aihs-card-content">
                    <h3><?php _e('Packages Generated', 'ai-health-savetnik'); ?></h3>
                    <div class="aihs-big-number"><?php echo intval($stats['packages_generated'] ?? 0); ?></div>
                    <p class="aihs-card-subtitle">
                        <?php echo array_sum(array_column($package_generation_stats, 'count')); ?> <?php _e('total', 'ai-health-savetnik'); ?>
                    </p>
                </div>
            </div>

            <div class="aihs-overview-card">
                <div class="aihs-card-content">
                    <h3><?php _e('Completion Rate', 'ai-health-savetnik'); ?></h3>
                    <div class="aihs-big-number"><?php echo $completion_stats['analysis_rate']; ?>%</div>
                    <p class="aihs-card-subtitle"><?php _e('full completion', 'ai-health-savetnik'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="aihs-reports-content">
        <!-- Score Trends -->
        <div class="aihs-report-section">
            <h2><?php _e('Health Score Trends', 'ai-health-savetnik'); ?></h2>

            <div class="aihs-score-comparison">
                <div class="aihs-score-period">
                    <h4><?php _e('Last 7 Days', 'ai-health-savetnik'); ?></h4>
                    <div class="aihs-score-value"><?php echo number_format($score_by_period['last_7_days'] ?? 0, 1); ?></div>
                </div>
                <div class="aihs-score-period">
                    <h4><?php _e('Last 30 Days', 'ai-health-savetnik'); ?></h4>
                    <div class="aihs-score-value"><?php echo number_format($score_by_period['last_30_days'] ?? 0, 1); ?></div>
                </div>
                <div class="aihs-score-period">
                    <h4><?php _e('All Time', 'ai-health-savetnik'); ?></h4>
                    <div class="aihs-score-value"><?php echo number_format($score_by_period['all_time'] ?? 0, 1); ?></div>
                </div>
            </div>

            <?php if (!empty($score_distribution)): ?>
                <div class="aihs-score-distribution">
                    <h4><?php _e('Score Distribution', 'ai-health-savetnik'); ?></h4>
                    <div class="aihs-distribution-chart">
                        <?php foreach ($score_distribution as $category => $data): ?>
                            <div class="aihs-distribution-bar">
                                <div class="aihs-bar-label"><?php echo esc_html($data['label']); ?></div>
                                <div class="aihs-bar-container">
                                    <div class="aihs-bar"
                                         style="width: <?php echo min(100, ($data['count'] / max(1, array_sum(array_column($score_distribution, 'count')))) * 100); ?>%;
                                                background-color: <?php echo esc_attr($data['color']); ?>">
                                    </div>
                                </div>
                                <div class="aihs-bar-count"><?php echo intval($data['count']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completion Funnel -->
        <div class="aihs-report-section">
            <h2><?php _e('Completion Funnel', 'ai-health-savetnik'); ?></h2>

            <div class="aihs-funnel-chart">
                <div class="aihs-funnel-step">
                    <div class="aihs-funnel-bar" style="width: 100%;">
                        <span class="aihs-funnel-label"><?php _e('Started Quiz', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-funnel-count"><?php echo $completion_stats['total_started']; ?></span>
                    </div>
                </div>

                <div class="aihs-funnel-step">
                    <div class="aihs-funnel-bar" style="width: <?php echo $completion_stats['questions_rate']; ?>%;">
                        <span class="aihs-funnel-label"><?php _e('Completed Questions', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-funnel-count"><?php echo $completion_stats['questions_completed']; ?> (<?php echo $completion_stats['questions_rate']; ?>%)</span>
                    </div>
                </div>

                <div class="aihs-funnel-step">
                    <div class="aihs-funnel-bar" style="width: <?php echo $completion_stats['analysis_rate']; ?>%;">
                        <span class="aihs-funnel-label"><?php _e('Received Analysis', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-funnel-count"><?php echo $completion_stats['analysis_completed']; ?> (<?php echo $completion_stats['analysis_rate']; ?>%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Health Issues -->
        <div class="aihs-report-section">
            <h2><?php _e('Most Common Health Issues', 'ai-health-savetnik'); ?></h2>

            <?php if (empty($health_issues)): ?>
                <p><?php _e('No health issues data available yet.', 'ai-health-savetnik'); ?></p>
            <?php else: ?>
                <div class="aihs-health-issues-chart">
                    <?php foreach ($health_issues as $issue): ?>
                        <div class="aihs-issue-item">
                            <div class="aihs-issue-question"><?php echo esc_html(wp_trim_words($issue['question'], 8)); ?></div>
                            <div class="aihs-issue-bar-container">
                                <div class="aihs-issue-bar"
                                     style="width: <?php echo min(100, ($issue['count'] / max(1, $health_issues[0]['count'])) * 100); ?>%;">
                                </div>
                            </div>
                            <div class="aihs-issue-count">
                                <?php echo $issue['count']; ?> <?php _e('responses', 'ai-health-savetnik'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Package Analytics -->
        <div class="aihs-report-section">
            <h2><?php _e('Package Analytics', 'ai-health-savetnik'); ?></h2>

            <div class="aihs-package-stats">
                <h4><?php _e('Packages by Type', 'ai-health-savetnik'); ?></h4>
                <div class="aihs-package-types-chart">
                    <?php foreach ($package_generation_stats as $type => $data): ?>
                        <div class="aihs-package-type-item">
                            <div class="aihs-package-type-label"><?php echo esc_html($data['label']); ?></div>
                            <div class="aihs-package-type-bar-container">
                                <div class="aihs-package-type-bar"
                                     style="width: <?php echo $data['count'] > 0 ? min(100, ($data['count'] / max(1, array_sum(array_column($package_generation_stats, 'count')))) * 100) : 0; ?>%;">
                                </div>
                            </div>
                            <div class="aihs-package-type-count"><?php echo $data['count']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="aihs-report-section">
            <h2><?php _e('Export Data', 'ai-health-savetnik'); ?></h2>

            <div class="aihs-export-options">
                <div class="aihs-export-option">
                    <h4><?php _e('Export Responses', 'ai-health-savetnik'); ?></h4>
                    <p><?php _e('Export all quiz responses with answers and analysis.', 'ai-health-savetnik'); ?></p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=export_csv'), 'aihs_export_csv', 'nonce'); ?>"
                       class="button button-primary">
                        <?php _e('Export CSV', 'ai-health-savetnik'); ?>
                    </a>
                </div>

                <div class="aihs-export-option">
                    <h4><?php _e('Export Packages', 'ai-health-savetnik'); ?></h4>
                    <p><?php _e('Export all generated packages with pricing information.', 'ai-health-savetnik'); ?></p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-packages&action=export_packages_csv'), 'aihs_export_packages', 'nonce'); ?>"
                       class="button">
                        <?php _e('Export Packages CSV', 'ai-health-savetnik'); ?>
                    </a>
                </div>

                <div class="aihs-export-option">
                    <h4><?php _e('Analytics Report', 'ai-health-savetnik'); ?></h4>
                    <p><?php _e('Generate detailed analytics report for the selected period.', 'ai-health-savetnik'); ?></p>
                    <select id="aihs-report-period">
                        <option value="7"><?php _e('Last 7 days', 'ai-health-savetnik'); ?></option>
                        <option value="30"><?php _e('Last 30 days', 'ai-health-savetnik'); ?></option>
                        <option value="90"><?php _e('Last 90 days', 'ai-health-savetnik'); ?></option>
                        <option value="all"><?php _e('All time', 'ai-health-savetnik'); ?></option>
                    </select>
                    <button type="button" class="button" onclick="generateReport()">
                        <?php _e('Generate Report', 'ai-health-savetnik'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.aihs-reports-overview {
    margin: 20px 0;
}

.aihs-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.aihs-overview-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.aihs-card-content {
    padding: 20px;
    text-align: center;
}

.aihs-card-content h3 {
    margin: 0 0 15px 0;
    color: #646970;
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.aihs-big-number {
    font-size: 3em;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
    margin-bottom: 10px;
}

.aihs-card-subtitle {
    color: #646970;
    font-size: 13px;
    margin: 0;
}

.aihs-reports-content {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.aihs-report-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.aihs-report-section h2 {
    margin: 0 0 20px 0;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}

.aihs-score-comparison {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.aihs-score-period {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.aihs-score-period h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #646970;
    text-transform: uppercase;
}

.aihs-score-value {
    font-size: 2em;
    font-weight: 600;
    color: #2271b1;
}

.aihs-distribution-chart {
    margin-top: 15px;
}

.aihs-distribution-bar {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 15px;
}

.aihs-bar-label {
    width: 100px;
    font-size: 13px;
    color: #646970;
}

.aihs-bar-container {
    flex: 1;
    height: 25px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.aihs-bar {
    height: 100%;
    border-radius: 3px;
}

.aihs-bar-count {
    width: 50px;
    text-align: right;
    font-weight: 600;
    color: #1d2327;
}

.aihs-funnel-chart {
    max-width: 600px;
}

.aihs-funnel-step {
    margin-bottom: 10px;
}

.aihs-funnel-bar {
    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.aihs-funnel-bar:hover {
    transform: translateX(5px);
}

.aihs-health-issues-chart {
    margin-top: 15px;
}

.aihs-issue-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    gap: 15px;
}

.aihs-issue-question {
    width: 200px;
    font-size: 13px;
    color: #1d2327;
}

.aihs-issue-bar-container {
    flex: 1;
    height: 20px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.aihs-issue-bar {
    height: 100%;
    background: linear-gradient(135deg, #fd7e14 0%, #e8661c 100%);
    border-radius: 3px;
}

.aihs-issue-count {
    width: 100px;
    text-align: right;
    font-size: 12px;
    color: #646970;
}

.aihs-package-types-chart {
    margin-top: 15px;
}

.aihs-package-type-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 15px;
}

.aihs-package-type-label {
    width: 150px;
    font-size: 13px;
    color: #1d2327;
}

.aihs-package-type-bar-container {
    flex: 1;
    height: 20px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.aihs-package-type-bar {
    height: 100%;
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    border-radius: 3px;
}

.aihs-package-type-count {
    width: 50px;
    text-align: right;
    font-weight: 600;
    color: #1d2327;
}

.aihs-export-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.aihs-export-option {
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 20px;
}

.aihs-export-option h4 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.aihs-export-option p {
    color: #646970;
    margin-bottom: 15px;
}

#aihs-report-period {
    margin-right: 10px;
}

@media (max-width: 782px) {
    .aihs-overview-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .aihs-score-comparison {
        grid-template-columns: 1fr;
    }

    .aihs-issue-item {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .aihs-issue-question {
        width: auto;
    }

    .aihs-issue-count {
        width: auto;
        text-align: left;
    }
}
</style>

<script>
function generateReport() {
    var period = document.getElementById('aihs-report-period').value;
    var url = '<?php echo admin_url('admin.php?page=aihs-reports&action=generate_report'); ?>';
    url += '&period=' + period;
    url += '&nonce=<?php echo wp_create_nonce('aihs_generate_report'); ?>';

    window.open(url, '_blank');
}
</script>