<?php
/**
 * Questions List View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Health Questions', 'ai-health-savetnik'); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=aihs-questions&action=add'); ?>" class="page-title-action">
        <?php _e('Add New Question', 'ai-health-savetnik'); ?>
    </a>

    <hr class="wp-header-end">

    <?php if (empty($questions)): ?>
        <div class="notice notice-info">
            <p>
                <?php _e('No health questions configured yet.', 'ai-health-savetnik'); ?>
                <a href="<?php echo admin_url('admin.php?page=aihs-questions&action=add'); ?>">
                    <?php _e('Add your first question', 'ai-health-savetnik'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        <div class="aihs-questions-actions">
            <div class="alignleft">
                <button type="button" class="button" id="aihs-reorder-questions">
                    <?php _e('Reorder Questions', 'ai-health-savetnik'); ?>
                </button>
            </div>
            <div class="alignright">
                <span class="description">
                    <?php printf(__('Total: %d questions', 'ai-health-savetnik'), count($questions)); ?>
                </span>
            </div>
            <br class="clear">
        </div>

        <div id="aihs-questions-list" class="aihs-questions-container">
            <?php foreach ($questions as $index => $question): ?>
                <div class="aihs-question-card" data-question-id="<?php echo esc_attr($question['id']); ?>">
                    <div class="aihs-question-header">
                        <div class="aihs-question-order">
                            <span class="dashicons dashicons-menu"></span>
                            <span class="question-number"><?php echo $index + 1; ?></span>
                        </div>

                        <div class="aihs-question-content">
                            <h3 class="aihs-question-text"><?php echo esc_html($question['text']); ?></h3>

                            <div class="aihs-question-meta">
                                <span class="aihs-question-type">
                                    <strong><?php _e('Type:', 'ai-health-savetnik'); ?></strong>
                                    <?php
                                    $types = array(
                                        'binary' => __('Yes/No', 'ai-health-savetnik'),
                                        'intensity' => __('With Intensity', 'ai-health-savetnik'),
                                        'scale' => __('Scale 1-10', 'ai-health-savetnik')
                                    );
                                    echo $types[$question['type']] ?? $question['type'];
                                    ?>
                                </span>

                                <span class="aihs-question-weight">
                                    <strong><?php _e('Weight:', 'ai-health-savetnik'); ?></strong>
                                    <?php echo intval($question['weight']); ?>
                                </span>

                                <?php if (!empty($question['recommended_products'])): ?>
                                    <span class="aihs-question-products">
                                        <strong><?php _e('Products:', 'ai-health-savetnik'); ?></strong>
                                        <?php echo count($question['recommended_products']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($question['sub_question'])): ?>
                                <div class="aihs-sub-question">
                                    <strong><?php _e('Sub-question:', 'ai-health-savetnik'); ?></strong>
                                    <?php echo esc_html($question['sub_question']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($question['type'] === 'intensity' && !empty($question['intensity_options'])): ?>
                                <div class="aihs-intensity-options">
                                    <strong><?php _e('Intensity Options:', 'ai-health-savetnik'); ?></strong>
                                    <?php
                                    $options = $question['intensity_options'];
                                    $weights = $question['intensity_weights'] ?? array();
                                    for ($i = 0; $i < count($options); $i++) {
                                        echo esc_html($options[$i]);
                                        if (isset($weights[$i])) {
                                            echo ' (' . intval($weights[$i]) . ')';
                                        }
                                        if ($i < count($options) - 1) echo ', ';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($question['ai_hint'])): ?>
                                <div class="aihs-ai-hint">
                                    <strong><?php _e('AI Hint:', 'ai-health-savetnik'); ?></strong>
                                    <?php echo esc_html(wp_trim_words($question['ai_hint'], 15)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="aihs-question-actions">
                            <a href="<?php echo admin_url('admin.php?page=aihs-questions&action=edit&question_id=' . $question['id']); ?>"
                               class="button button-small">
                                <?php _e('Edit', 'ai-health-savetnik'); ?>
                            </a>

                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-questions&action=delete_question&question_id=' . $question['id']), 'aihs_delete_question', 'nonce'); ?>"
                               class="button button-small button-link-delete"
                               onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this question?', 'ai-health-savetnik')); ?>')">
                                <?php _e('Delete', 'ai-health-savetnik'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Score Preview -->
        <div class="aihs-score-preview">
            <h2><?php _e('Score Preview', 'ai-health-savetnik'); ?></h2>
            <p class="description">
                <?php _e('Preview how different answer patterns would affect the health score.', 'ai-health-savetnik'); ?>
            </p>

            <div class="aihs-preview-controls">
                <button type="button" class="button" id="aihs-preview-all-yes">
                    <?php _e('All "Yes" Answers', 'ai-health-savetnik'); ?>
                </button>
                <button type="button" class="button" id="aihs-preview-all-no">
                    <?php _e('All "No" Answers', 'ai-health-savetnik'); ?>
                </button>
                <button type="button" class="button" id="aihs-preview-random">
                    <?php _e('Random Answers', 'ai-health-savetnik'); ?>
                </button>
            </div>

            <div id="aihs-score-result" class="aihs-score-result" style="display: none;">
                <!-- Score preview will be populated here -->
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.aihs-questions-actions {
    margin: 20px 0;
    overflow: hidden;
}

.aihs-questions-container {
    margin: 20px 0;
}

.aihs-question-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    cursor: move;
}

.aihs-question-header {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    gap: 15px;
}

.aihs-question-order {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #646970;
    font-weight: 600;
}

.aihs-question-order .dashicons {
    cursor: grab;
}

.aihs-question-content {
    flex: 1;
}

.aihs-question-text {
    margin: 0 0 10px 0;
    font-size: 16px;
    line-height: 1.4;
}

.aihs-question-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 10px;
    font-size: 13px;
    color: #646970;
}

.aihs-sub-question,
.aihs-intensity-options,
.aihs-ai-hint {
    margin: 8px 0;
    font-size: 13px;
    color: #646970;
}

.aihs-question-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.aihs-score-preview {
    background: #f9f9f9;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.aihs-preview-controls {
    margin: 15px 0;
}

.aihs-preview-controls .button {
    margin-right: 10px;
}

.aihs-score-result {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
}

.aihs-score-display {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.aihs-score-breakdown {
    margin-top: 15px;
}

.aihs-score-breakdown table {
    width: 100%;
    border-collapse: collapse;
}

.aihs-score-breakdown th,
.aihs-score-breakdown td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.sortable-placeholder {
    background: #f0f0f1;
    border: 2px dashed #c3c4c7;
    height: 80px;
    margin-bottom: 15px;
    border-radius: 4px;
}

@media (max-width: 782px) {
    .aihs-question-header {
        flex-direction: column;
    }

    .aihs-question-actions {
        flex-direction: row;
    }

    .aihs-question-meta {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Make questions sortable
    $('#aihs-questions-list').sortable({
        handle: '.aihs-question-order',
        placeholder: 'sortable-placeholder',
        update: function(event, ui) {
            var order = [];
            $('#aihs-questions-list .aihs-question-card').each(function(index) {
                order.push({
                    id: $(this).data('question-id'),
                    priority: index + 1
                });
                $(this).find('.question-number').text(index + 1);
            });

            // Save new order via AJAX
            $.post(ajaxurl, {
                action: 'aihs_reorder_questions',
                order: order,
                nonce: '<?php echo wp_create_nonce("aihs_reorder_questions"); ?>'
            });
        }
    });

    // Score preview functions
    $('#aihs-preview-all-yes').click(function() {
        previewScore('all_yes');
    });

    $('#aihs-preview-all-no').click(function() {
        previewScore('all_no');
    });

    $('#aihs-preview-random').click(function() {
        previewScore('random');
    });

    function previewScore(pattern) {
        var questions = <?php echo json_encode($questions); ?>;
        var answers = {};
        var intensities = {};

        questions.forEach(function(question) {
            if (pattern === 'all_yes') {
                answers[question.id] = 'Da';
                if (question.type === 'intensity' && question.intensity_options) {
                    intensities[question.id] = question.intensity_options[question.intensity_options.length - 1];
                }
            } else if (pattern === 'all_no') {
                answers[question.id] = 'Ne';
            } else { // random
                answers[question.id] = Math.random() > 0.5 ? 'Da' : 'Ne';
                if (answers[question.id] === 'Da' && question.type === 'intensity' && question.intensity_options) {
                    var randomIndex = Math.floor(Math.random() * question.intensity_options.length);
                    intensities[question.id] = question.intensity_options[randomIndex];
                }
            }
        });

        $.post(ajaxurl, {
            action: 'aihs_preview_score',
            answers: answers,
            intensities: intensities,
            nonce: '<?php echo wp_create_nonce("aihs_preview_score"); ?>'
        }, function(response) {
            if (response.success) {
                displayScorePreview(response.data);
            }
        });
    }

    function displayScorePreview(data) {
        var html = '<div class="aihs-score-display" style="color: ' + (data.category_data.color || '#333') + '">';
        html += data.score + '/100 (' + data.category_data.label + ')';
        html += '</div>';

        if (data.breakdown && data.breakdown.length > 0) {
            html += '<div class="aihs-score-breakdown">';
            html += '<h4><?php _e("Penalty Breakdown", "ai-health-savetnik"); ?></h4>';
            html += '<table>';
            html += '<thead><tr><th><?php _e("Question", "ai-health-savetnik"); ?></th><th><?php _e("Answer", "ai-health-savetnik"); ?></th><th><?php _e("Penalty", "ai-health-savetnik"); ?></th></tr></thead>';
            html += '<tbody>';

            data.breakdown.forEach(function(item) {
                if (item.penalty > 0) {
                    html += '<tr>';
                    html += '<td>' + item.question + '</td>';
                    html += '<td>' + item.answer + '</td>';
                    html += '<td>' + item.penalty + '</td>';
                    html += '</tr>';
                }
            });

            html += '</tbody></table>';
            html += '</div>';
        }

        $('#aihs-score-result').html(html).show();
    }
});
</script>