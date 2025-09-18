<?php
/**
 * Question Edit/Add View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$is_new = ($question['id'] == 0);
$page_title = $is_new ? __('Add New Question', 'ai-health-savetnik') : __('Edit Question', 'ai-health-savetnik');
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>

    <form method="post" action="<?php echo admin_url('admin.php?page=aihs-questions'); ?>" id="aihs-question-form">
        <?php wp_nonce_field('aihs_save_question', 'aihs_question_nonce'); ?>
        <input type="hidden" name="action" value="save_question">
        <input type="hidden" name="question_id" value="<?php echo esc_attr($question['id']); ?>">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="question_text"><?php _e('Question Text', 'ai-health-savetnik'); ?> *</label>
                </th>
                <td>
                    <textarea name="question_text" id="question_text" rows="3" class="large-text" required><?php echo esc_textarea($question['text']); ?></textarea>
                    <p class="description">
                        <?php _e('The main question that will be displayed to users.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="question_type"><?php _e('Question Type', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <select name="question_type" id="question_type">
                        <option value="binary" <?php selected($question['type'], 'binary'); ?>>
                            <?php _e('Yes/No Question', 'ai-health-savetnik'); ?>
                        </option>
                        <option value="intensity" <?php selected($question['type'], 'intensity'); ?>>
                            <?php _e('Yes/No with Intensity', 'ai-health-savetnik'); ?>
                        </option>
                        <option value="scale" <?php selected($question['type'], 'scale'); ?>>
                            <?php _e('Scale 1-10', 'ai-health-savetnik'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Binary = Simple Yes/No, Intensity = Yes/No with severity levels, Scale = 1-10 rating', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="question_weight"><?php _e('Question Weight', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <input type="number" name="question_weight" id="question_weight"
                           value="<?php echo esc_attr($question['weight']); ?>"
                           class="small-text" min="1" max="50" step="1">
                    <p class="description">
                        <?php _e('How much this question affects the health score (1-50). Higher = more important.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="sub_question"><?php _e('Sub-question (Optional)', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <textarea name="sub_question" id="sub_question" rows="2" class="large-text"><?php echo esc_textarea($question['sub_question']); ?></textarea>
                    <p class="description">
                        <?php _e('Additional question shown when user answers "Yes" to the main question.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr id="intensity_options_row" style="display: <?php echo $question['type'] === 'intensity' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label><?php _e('Intensity Options', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <div id="intensity_options_container">
                        <?php
                        $intensity_options = $question['intensity_options'] ?? array('Blago', 'Umereno', 'Jako');
                        $intensity_weights = $question['intensity_weights'] ?? array(5, 10, 15);

                        for ($i = 0; $i < max(3, count($intensity_options)); $i++):
                        ?>
                        <div class="intensity-option-row">
                            <input type="text"
                                   name="intensity_options[]"
                                   value="<?php echo esc_attr($intensity_options[$i] ?? ''); ?>"
                                   placeholder="<?php _e('Option name (e.g., Light)', 'ai-health-savetnik'); ?>"
                                   class="regular-text">
                            <input type="number"
                                   name="intensity_weights[]"
                                   value="<?php echo esc_attr($intensity_weights[$i] ?? ''); ?>"
                                   placeholder="<?php _e('Weight', 'ai-health-savetnik'); ?>"
                                   class="small-text" min="1" max="50">
                            <?php if ($i >= 3): ?>
                                <button type="button" class="button remove-intensity"><?php _e('Remove', 'ai-health-savetnik'); ?></button>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <p>
                        <button type="button" id="add_intensity_option" class="button">
                            <?php _e('Add Intensity Option', 'ai-health-savetnik'); ?>
                        </button>
                    </p>
                    <p class="description">
                        <?php _e('Define intensity levels (e.g., Mild, Moderate, Severe) and their penalty weights.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="ai_hint"><?php _e('AI Analysis Hint', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <textarea name="ai_hint" id="ai_hint" rows="3" class="large-text"><?php echo esc_textarea($question['ai_hint']); ?></textarea>
                    <p class="description">
                        <?php _e('Additional context for AI analysis. This helps the AI provide more accurate recommendations.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="recommended_products"><?php _e('Recommended Products', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <?php
                    // Get WooCommerce products for selection
                    $products = get_posts(array(
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    ?>

                    <?php if (!empty($products)): ?>
                        <div class="aihs-product-selection">
                            <?php foreach ($products as $product): ?>
                                <label class="aihs-product-checkbox">
                                    <input type="checkbox"
                                           name="recommended_products[]"
                                           value="<?php echo esc_attr($product->ID); ?>"
                                           <?php checked(in_array($product->ID, $question['recommended_products'])); ?>>
                                    <?php echo esc_html($product->post_title); ?>
                                    <?php
                                    $price = get_post_meta($product->ID, '_price', true);
                                    if ($price) {
                                        echo ' (' . wc_price($price) . ')';
                                    }
                                    ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">
                            <?php _e('Select products that should be recommended when users answer "Yes" to this question.', 'ai-health-savetnik'); ?>
                        </p>
                    <?php else: ?>
                        <p class="description">
                            <?php _e('No WooCommerce products found. Create some products first.', 'ai-health-savetnik'); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="question_priority"><?php _e('Display Priority', 'ai-health-savetnik'); ?></label>
                </th>
                <td>
                    <input type="number" name="question_priority" id="question_priority"
                           value="<?php echo esc_attr($question['priority']); ?>"
                           class="small-text" min="1" max="999">
                    <p class="description">
                        <?php _e('Lower numbers appear first. Questions are automatically ordered by priority.', 'ai-health-savetnik'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Question Preview -->
        <div class="aihs-question-preview">
            <h2><?php _e('Preview', 'ai-health-savetnik'); ?></h2>
            <div id="question_preview_container">
                <!-- Preview will be populated by JavaScript -->
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary"
                   value="<?php echo $is_new ? __('Add Question', 'ai-health-savetnik') : __('Update Question', 'ai-health-savetnik'); ?>">
            <a href="<?php echo admin_url('admin.php?page=aihs-questions'); ?>" class="button">
                <?php _e('Cancel', 'ai-health-savetnik'); ?>
            </a>
        </p>
    </form>
</div>

<style>
.intensity-option-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.intensity-option-row input {
    margin: 0;
}

.aihs-product-selection {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background: #fafafa;
}

.aihs-product-checkbox {
    display: block;
    margin: 5px 0;
    padding: 3px 0;
}

.aihs-product-checkbox input {
    margin-right: 8px;
}

.aihs-question-preview {
    background: #f9f9f9;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-question-preview h2 {
    margin-top: 0;
}

#question_preview_container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    min-height: 100px;
}

.preview-question {
    margin-bottom: 15px;
}

.preview-question h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.preview-answers {
    margin-left: 20px;
}

.preview-answers label {
    display: block;
    margin: 5px 0;
}

.preview-sub-question {
    margin: 10px 0 10px 20px;
    padding: 10px;
    background: #f0f0f1;
    border-left: 3px solid #2271b1;
}

.preview-intensity {
    margin: 10px 0 10px 40px;
}

.preview-intensity select {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show/hide intensity options based on question type
    $('#question_type').change(function() {
        if ($(this).val() === 'intensity') {
            $('#intensity_options_row').show();
        } else {
            $('#intensity_options_row').hide();
        }
        updatePreview();
    });

    // Add intensity option
    $('#add_intensity_option').click(function() {
        var newRow = '<div class="intensity-option-row">' +
                     '<input type="text" name="intensity_options[]" placeholder="<?php _e("Option name", "ai-health-savetnik"); ?>" class="regular-text">' +
                     '<input type="number" name="intensity_weights[]" placeholder="<?php _e("Weight", "ai-health-savetnik"); ?>" class="small-text" min="1" max="50">' +
                     '<button type="button" class="button remove-intensity"><?php _e("Remove", "ai-health-savetnik"); ?></button>' +
                     '</div>';
        $('#intensity_options_container').append(newRow);
    });

    // Remove intensity option
    $(document).on('click', '.remove-intensity', function() {
        $(this).closest('.intensity-option-row').remove();
        updatePreview();
    });

    // Update preview when form fields change
    $('#question_text, #sub_question, #question_type').on('input change', updatePreview);
    $(document).on('input change', 'input[name="intensity_options[]"]', updatePreview);

    // Initial preview
    updatePreview();

    function updatePreview() {
        var questionText = $('#question_text').val();
        var subQuestion = $('#sub_question').val();
        var questionType = $('#question_type').val();
        var intensityOptions = [];

        $('input[name="intensity_options[]"]').each(function() {
            var val = $(this).val().trim();
            if (val) {
                intensityOptions.push(val);
            }
        });

        if (!questionText) {
            $('#question_preview_container').html('<p style="color: #646970;"><?php _e("Enter question text to see preview", "ai-health-savetnik"); ?></p>');
            return;
        }

        var html = '<div class="preview-question">';
        html += '<h3>' + questionText + '</h3>';

        html += '<div class="preview-answers">';

        if (questionType === 'scale') {
            html += '<label><input type="radio" name="preview_answer" value="1"> 1 - <?php _e("Very Low", "ai-health-savetnik"); ?></label>';
            html += '<label><input type="radio" name="preview_answer" value="5"> 5 - <?php _e("Medium", "ai-health-savetnik"); ?></label>';
            html += '<label><input type="radio" name="preview_answer" value="10"> 10 - <?php _e("Very High", "ai-health-savetnik"); ?></label>';
        } else {
            html += '<label><input type="radio" name="preview_answer" value="da"> <?php _e("Yes", "ai-health-savetnik"); ?></label>';
            html += '<label><input type="radio" name="preview_answer" value="ne"> <?php _e("No", "ai-health-savetnik"); ?></label>';
        }

        html += '</div>';

        if (subQuestion) {
            html += '<div class="preview-sub-question" style="display: none;">';
            html += '<strong>' + subQuestion + '</strong>';
            html += '</div>';
        }

        if (questionType === 'intensity' && intensityOptions.length > 0) {
            html += '<div class="preview-intensity" style="display: none;">';
            html += '<strong><?php _e("Intensity:", "ai-health-savetnik"); ?></strong>';
            html += '<select>';
            intensityOptions.forEach(function(option) {
                html += '<option value="' + option + '">' + option + '</option>';
            });
            html += '</select>';
            html += '</div>';
        }

        html += '</div>';

        $('#question_preview_container').html(html);

        // Show sub-question and intensity when "Yes" is selected in preview
        $('#question_preview_container input[value="da"]').change(function() {
            if ($(this).is(':checked')) {
                $('.preview-sub-question, .preview-intensity').show();
            }
        });

        $('#question_preview_container input[value="ne"]').change(function() {
            if ($(this).is(':checked')) {
                $('.preview-sub-question, .preview-intensity').hide();
            }
        });
    }

    // Form validation
    $('#aihs-question-form').submit(function(e) {
        var questionText = $('#question_text').val().trim();
        if (!questionText) {
            alert('<?php _e("Question text is required", "ai-health-savetnik"); ?>');
            e.preventDefault();
            return false;
        }

        var weight = parseInt($('#question_weight').val());
        if (!weight || weight < 1 || weight > 50) {
            alert('<?php _e("Question weight must be between 1 and 50", "ai-health-savetnik"); ?>');
            e.preventDefault();
            return false;
        }

        if ($('#question_type').val() === 'intensity') {
            var hasValidIntensity = false;
            $('input[name="intensity_options[]"]').each(function() {
                if ($(this).val().trim()) {
                    hasValidIntensity = true;
                    return false;
                }
            });

            if (!hasValidIntensity) {
                alert('<?php _e("At least one intensity option is required for intensity-type questions", "ai-health-savetnik"); ?>');
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>