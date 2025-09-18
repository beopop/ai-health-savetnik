<?php
/**
 * Response Detail View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$answers = json_decode($response->answers, true) ?: array();
$intensity_data = json_decode($response->intensity_data, true) ?: array();
$questions = aihs_get_health_questions();
$public_id = aihs_generate_public_id($response->id);
$public_url = add_query_arg('aihs_public_id', $public_id, home_url());
?>

<div class="wrap">
    <h1>
        <?php _e('Response Details', 'ai-health-savetnik'); ?>
        <span class="title-count">#<?php echo esc_html($response->id); ?></span>
    </h1>

    <div class="aihs-response-header">
        <div class="aihs-response-meta">
            <h2><?php echo esc_html($response->first_name . ' ' . $response->last_name); ?></h2>
            <p class="aihs-response-info">
                <strong><?php _e('Email:', 'ai-health-savetnik'); ?></strong>
                <a href="mailto:<?php echo esc_attr($response->email); ?>"><?php echo esc_html($response->email); ?></a>

                <?php if ($response->phone): ?>
                    <br><strong><?php _e('Phone:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($response->phone); ?>
                <?php endif; ?>

                <?php if ($response->age): ?>
                    <br><strong><?php _e('Age:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($response->age); ?>
                <?php endif; ?>

                <?php if ($response->gender): ?>
                    <br><strong><?php _e('Gender:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($response->gender); ?>
                <?php endif; ?>

                <br><strong><?php _e('Submitted:', 'ai-health-savetnik'); ?></strong>
                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($response->created_at)); ?>

                <br><strong><?php _e('Status:', 'ai-health-savetnik'); ?></strong>
                <?php
                $status_labels = array(
                    'draft' => __('Draft', 'ai-health-savetnik'),
                    'questions_completed' => __('Questions Completed', 'ai-health-savetnik'),
                    'analysis_completed' => __('Analysis Completed', 'ai-health-savetnik')
                );
                echo $status_labels[$response->completion_status] ?? $response->completion_status;
                ?>
            </p>
        </div>

        <div class="aihs-response-actions">
            <?php if ($response->completion_status === 'questions_completed'): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=regenerate_analysis&response_id=' . $response->id), 'aihs_regenerate_analysis', 'nonce'); ?>"
                   class="button button-primary">
                    <?php _e('Generate AI Analysis', 'ai-health-savetnik'); ?>
                </a>
            <?php elseif ($response->completion_status === 'analysis_completed'): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=regenerate_analysis&response_id=' . $response->id), 'aihs_regenerate_analysis', 'nonce'); ?>"
                   class="button button-secondary"
                   onclick="return confirm('<?php echo esc_js(__('Are you sure you want to regenerate the AI analysis?', 'ai-health-savetnik')); ?>')">
                    <?php _e('Regenerate Analysis', 'ai-health-savetnik'); ?>
                </a>

                <a href="<?php echo esc_url($public_url); ?>" target="_blank" class="button">
                    <?php _e('View Public Results', 'ai-health-savetnik'); ?>
                </a>
            <?php endif; ?>

            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-results&action=delete_response&response_id=' . $response->id), 'aihs_delete_response', 'nonce'); ?>"
               class="button button-link-delete"
               onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this response?', 'ai-health-savetnik')); ?>')">
                <?php _e('Delete Response', 'ai-health-savetnik'); ?>
            </a>
        </div>
    </div>

    <div class="aihs-response-content">
        <!-- Health Score -->
        <?php if ($response->calculated_score > 0): ?>
            <div class="aihs-section aihs-health-score-section">
                <h3><?php _e('Health Score', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-score-display-large">
                    <?php echo aihs_format_score($response->calculated_score, true); ?>
                </div>

                <?php if ($response->score_category): ?>
                    <p class="aihs-score-description">
                        <?php
                        $category_info = aihs_get_score_category($response->calculated_score);
                        if ($category_info && !empty($category_info['data']['description'])) {
                            echo esc_html($category_info['data']['description']);
                        }
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quiz Answers -->
        <div class="aihs-section">
            <h3><?php _e('Quiz Answers', 'ai-health-savetnik'); ?></h3>

            <?php if (empty($answers)): ?>
                <p><?php _e('No answers recorded yet.', 'ai-health-savetnik'); ?></p>
            <?php else: ?>
                <div class="aihs-answers-list">
                    <?php foreach ($questions as $question): ?>
                        <?php if (isset($answers[$question['id']])): ?>
                            <div class="aihs-answer-item">
                                <div class="aihs-question">
                                    <strong><?php echo esc_html($question['text']); ?></strong>
                                    <span class="aihs-question-weight">(<?php printf(__('Weight: %d', 'ai-health-savetnik'), $question['weight']); ?>)</span>
                                </div>

                                <div class="aihs-answer">
                                    <span class="aihs-answer-value"><?php echo esc_html($answers[$question['id']]); ?></span>

                                    <?php if (isset($intensity_data[$question['id']])): ?>
                                        <span class="aihs-intensity">
                                            <?php printf(__('Intensity: %s', 'ai-health-savetnik'), esc_html($intensity_data[$question['id']])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($question['sub_question']) && strtolower($answers[$question['id']]) === 'da'): ?>
                                    <div class="aihs-sub-question">
                                        <em><?php echo esc_html($question['sub_question']); ?></em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- AI Analysis -->
        <?php if (!empty($response->ai_analysis)): ?>
            <div class="aihs-section">
                <h3><?php _e('AI Health Analysis', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-ai-analysis">
                    <?php echo wp_kses_post(wpautop($response->ai_analysis)); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recommended Products -->
        <?php if (!empty($response->recommended_products)): ?>
            <?php
            $recommended_products = json_decode($response->recommended_products, true) ?: array();
            if (!empty($recommended_products)):
            ?>
                <div class="aihs-section">
                    <h3><?php _e('Recommended Products', 'ai-health-savetnik'); ?></h3>
                    <div class="aihs-products-list">
                        <?php foreach ($recommended_products as $product_id): ?>
                            <?php
                            $product = wc_get_product($product_id);
                            if ($product && $product->exists()):
                            ?>
                                <div class="aihs-product-item">
                                    <div class="aihs-product-info">
                                        <h4><?php echo esc_html($product->get_name()); ?></h4>
                                        <p class="aihs-product-price"><?php echo $product->get_price_html(); ?></p>

                                        <?php
                                        $why_good = get_post_meta($product_id, '_aihs_why_good', true);
                                        if ($why_good):
                                        ?>
                                            <p class="aihs-product-benefit"><?php echo esc_html($why_good); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="aihs-product-actions">
                                        <a href="<?php echo get_permalink($product_id); ?>" target="_blank" class="button">
                                            <?php _e('View Product', 'ai-health-savetnik'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" class="button button-small">
                                            <?php _e('Edit', 'ai-health-savetnik'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Health Packages -->
        <?php
        $packages = AIHS_Database::get_response_packages($response->id);
        if (!empty($packages)):
        ?>
            <div class="aihs-section">
                <h3><?php _e('Generated Packages', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-packages-list">
                    <?php foreach ($packages as $package): ?>
                        <div class="aihs-package-item">
                            <h4><?php echo esc_html($package->package_name); ?></h4>
                            <p class="aihs-package-type">
                                <?php
                                $package_types = aihs_get_package_types();
                                echo $package_types[$package->package_type]['label'] ?? $package->package_type;
                                ?>
                            </p>

                            <div class="aihs-package-pricing">
                                <span class="aihs-original-price"><?php echo aihs_format_price($package->original_price); ?></span>
                                <?php if ($package->discount_percentage > 0): ?>
                                    <span class="aihs-discount"><?php printf(__('-%s%%', 'ai-health-savetnik'), $package->discount_percentage); ?></span>
                                    <span class="aihs-final-price"><?php echo aihs_format_price($package->final_price); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($package->description)): ?>
                                <p class="aihs-package-description"><?php echo esc_html($package->description); ?></p>
                            <?php endif; ?>

                            <?php if ($package->wc_product_id): ?>
                                <div class="aihs-package-actions">
                                    <a href="<?php echo get_permalink($package->wc_product_id); ?>" target="_blank" class="button">
                                        <?php _e('View Package', 'ai-health-savetnik'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('post.php?post=' . $package->wc_product_id . '&action=edit'); ?>" class="button button-small">
                                        <?php _e('Edit Product', 'ai-health-savetnik'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Raw Data -->
        <div class="aihs-section aihs-raw-data">
            <h3>
                <?php _e('Raw Data', 'ai-health-savetnik'); ?>
                <button type="button" class="button button-small" onclick="toggleRawData()">
                    <?php _e('Toggle', 'ai-health-savetnik'); ?>
                </button>
            </h3>

            <div id="aihs-raw-data" style="display: none;">
                <h4><?php _e('Answers JSON', 'ai-health-savetnik'); ?></h4>
                <textarea readonly class="large-text" rows="5"><?php echo esc_textarea($response->answers); ?></textarea>

                <?php if ($response->intensity_data): ?>
                    <h4><?php _e('Intensity Data JSON', 'ai-health-savetnik'); ?></h4>
                    <textarea readonly class="large-text" rows="3"><?php echo esc_textarea($response->intensity_data); ?></textarea>
                <?php endif; ?>

                <h4><?php _e('Session Info', 'ai-health-savetnik'); ?></h4>
                <p>
                    <strong><?php _e('Session ID:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($response->session_id); ?><br>
                    <strong><?php _e('User ID:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($response->user_id ?: __('Guest', 'ai-health-savetnik')); ?><br>
                    <strong><?php _e('Public ID:', 'ai-health-savetnik'); ?></strong> <?php echo esc_html($public_id); ?>
                </p>
            </div>
        </div>
    </div>

    <p class="submit">
        <a href="<?php echo admin_url('admin.php?page=aihs-results'); ?>" class="button">
            <?php _e('Back to Results', 'ai-health-savetnik'); ?>
        </a>
    </p>
</div>

<style>
.title-count {
    color: #646970;
    font-weight: normal;
    font-size: 16px;
}

.aihs-response-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-response-meta h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.aihs-response-info {
    color: #646970;
    line-height: 1.6;
}

.aihs-response-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    white-space: nowrap;
}

.aihs-response-content {
    display: grid;
    gap: 20px;
}

.aihs-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.aihs-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #c3c4c7;
}

.aihs-health-score-section {
    text-align: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.aihs-score-display-large {
    font-size: 3em;
    font-weight: 600;
    margin: 20px 0;
}

.aihs-score-description {
    font-style: italic;
    color: #646970;
}

.aihs-answers-list {
    display: grid;
    gap: 15px;
}

.aihs-answer-item {
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 15px;
}

.aihs-question {
    margin-bottom: 8px;
}

.aihs-question-weight {
    font-size: 12px;
    color: #646970;
    font-weight: normal;
}

.aihs-answer {
    display: flex;
    align-items: center;
    gap: 15px;
}

.aihs-answer-value {
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 3px;
}

.aihs-answer-value {
    background: #d4edda;
    color: #155724;
}

.aihs-intensity {
    font-size: 13px;
    color: #0073aa;
    font-style: italic;
}

.aihs-sub-question {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-left: 3px solid #2271b1;
    color: #646970;
}

.aihs-ai-analysis {
    line-height: 1.7;
    color: #1d2327;
}

.aihs-ai-analysis h4 {
    color: #2271b1;
    margin: 20px 0 10px 0;
}

.aihs-ai-analysis ul,
.aihs-ai-analysis ol {
    margin: 15px 0;
    padding-left: 30px;
}

.aihs-products-list,
.aihs-packages-list {
    display: grid;
    gap: 15px;
}

.aihs-product-item,
.aihs-package-item {
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.aihs-product-info h4,
.aihs-package-item h4 {
    margin: 0 0 5px 0;
}

.aihs-product-price {
    font-weight: 600;
    color: #2271b1;
    margin: 5px 0;
}

.aihs-product-benefit {
    font-size: 13px;
    color: #646970;
    margin: 5px 0 0 0;
}

.aihs-package-type {
    font-size: 13px;
    color: #646970;
    margin: 5px 0;
}

.aihs-package-pricing {
    margin: 10px 0;
}

.aihs-original-price {
    text-decoration: line-through;
    color: #646970;
    margin-right: 10px;
}

.aihs-discount {
    background: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 10px;
}

.aihs-final-price {
    font-weight: 600;
    color: #28a745;
    font-size: 16px;
}

.aihs-package-description {
    font-size: 13px;
    color: #646970;
    margin: 10px 0 0 0;
}

.aihs-product-actions,
.aihs-package-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.aihs-raw-data {
    background: #f8f9fa;
}

.aihs-raw-data textarea {
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
    background: #fff;
    border: 1px solid #c3c4c7;
}

@media (max-width: 782px) {
    .aihs-response-header {
        flex-direction: column;
        gap: 20px;
    }

    .aihs-response-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .aihs-product-item,
    .aihs-package-item {
        flex-direction: column;
        gap: 15px;
    }

    .aihs-product-actions,
    .aihs-package-actions {
        flex-direction: row;
    }

    .aihs-answer {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<script>
function toggleRawData() {
    var element = document.getElementById('aihs-raw-data');
    if (element.style.display === 'none') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}
</script>