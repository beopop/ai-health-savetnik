<?php
/**
 * Quiz Questions Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$questions = aihs_get_health_questions();
$session_id = aihs_get_session_id();
$questions_per_page = intval($atts['questions_per_page']);
$show_progress = $atts['show_progress'] === 'yes';

// If questions_per_page is 0, show all questions on one page
if ($questions_per_page === 0) {
    $questions_per_page = count($questions);
}

$total_pages = $questions_per_page > 0 ? ceil(count($questions) / $questions_per_page) : 1;
$current_page = intval($_GET['qpage'] ?? 1);
$current_page = max(1, min($current_page, $total_pages));

// Get questions for current page
$start_index = ($current_page - 1) * $questions_per_page;
$current_questions = array_slice($questions, $start_index, $questions_per_page);
?>

<div class="aihs-questions-container" data-session-id="<?php echo esc_attr($session_id); ?>">

    <?php if ($show_progress): ?>
        <!-- Progress Header -->
        <div class="aihs-questions-header">
            <div class="aihs-progress-info">
                <h2 class="aihs-questions-title">Zdravstveni upitnik</h2>
                <p class="aihs-questions-subtitle">
                    <?php if ($total_pages > 1): ?>
                        Strana <?php echo $current_page; ?> od <?php echo $total_pages; ?>
                    <?php else: ?>
                        Odgovorite na sva pitanja za kompletnu analizu
                    <?php endif; ?>
                </p>
            </div>

            <div class="aihs-progress-bar-container">
                <?php
                $total_questions = count($questions);
                $progress_percentage = $total_questions > 0 ? ($start_index / $total_questions) * 100 : 0;
                ?>
                <div class="aihs-progress-bar">
                    <div class="aihs-progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <div class="aihs-progress-text">
                    <span class="aihs-answered-count"><?php echo $start_index; ?></span> od
                    <span class="aihs-total-count"><?php echo $total_questions; ?></span> pitanja
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Questions Form -->
    <form id="aihs-questions-form" class="aihs-questions-form">
        <?php wp_nonce_field('aihs_questions_submit', 'aihs_questions_nonce'); ?>
        <input type="hidden" name="current_page" value="<?php echo $current_page; ?>">
        <input type="hidden" name="questions_per_page" value="<?php echo $questions_per_page; ?>">

        <?php if (!empty($current_questions)): ?>
            <div class="aihs-questions-list">
                <?php foreach ($current_questions as $index => $question):
                    $global_index = $start_index + $index + 1;
                    $question_id = $question['id'];
                ?>
                    <div class="aihs-question-item" data-question-id="<?php echo esc_attr($question_id); ?>">
                        <div class="aihs-question-header">
                            <div class="aihs-question-number"><?php echo $global_index; ?></div>
                            <div class="aihs-question-content">
                                <h3 class="aihs-question-text"><?php echo esc_html($question['text']); ?></h3>
                                <?php if (!empty($question['ai_hint'])): ?>
                                    <div class="aihs-question-hint">
                                        <button type="button" class="aihs-hint-toggle">
                                            <span class="aihs-hint-icon">💡</span>
                                            <span class="aihs-hint-text">Savet</span>
                                        </button>
                                        <div class="aihs-hint-content" style="display: none;">
                                            <p><?php echo esc_html($question['ai_hint']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="aihs-question-answers">
                            <?php if ($question['type'] === 'scale'): ?>
                                <!-- Scale 1-10 -->
                                <div class="aihs-answer-scale">
                                    <div class="aihs-scale-labels">
                                        <span class="aihs-scale-min">Uopšte ne</span>
                                        <span class="aihs-scale-max">Izuzetno</span>
                                    </div>
                                    <div class="aihs-scale-options">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <label class="aihs-scale-option">
                                                <input type="radio"
                                                       name="question_<?php echo $question_id; ?>"
                                                       value="<?php echo $i; ?>"
                                                       data-question-weight="<?php echo esc_attr($question['weight']); ?>">
                                                <span class="aihs-scale-value"><?php echo $i; ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Binary Yes/No -->
                                <div class="aihs-answer-binary">
                                    <label class="aihs-binary-option aihs-option-yes">
                                        <input type="radio"
                                               name="question_<?php echo $question_id; ?>"
                                               value="Da"
                                               data-question-weight="<?php echo esc_attr($question['weight']); ?>">
                                        <div class="aihs-option-content">
                                            <span class="aihs-option-icon">✓</span>
                                            <span class="aihs-option-label">Da</span>
                                        </div>
                                    </label>

                                    <label class="aihs-binary-option aihs-option-no">
                                        <input type="radio"
                                               name="question_<?php echo $question_id; ?>"
                                               value="Ne"
                                               data-question-weight="0">
                                        <div class="aihs-option-content">
                                            <span class="aihs-option-icon">✗</span>
                                            <span class="aihs-option-label">Ne</span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Sub-question for "Da" answers -->
                                <?php if (!empty($question['sub_question'])): ?>
                                    <div class="aihs-sub-question" style="display: none;">
                                        <h4 class="aihs-sub-question-text"><?php echo esc_html($question['sub_question']); ?></h4>

                                        <?php if ($question['type'] === 'intensity' && !empty($question['intensity_options'])): ?>
                                            <div class="aihs-intensity-selection">
                                                <p class="aihs-intensity-label">Koliko izraženo:</p>
                                                <div class="aihs-intensity-options">
                                                    <?php foreach ($question['intensity_options'] as $intensity_index => $intensity_option):
                                                        $intensity_weight = $question['intensity_weights'][$intensity_index] ?? 0;
                                                    ?>
                                                        <label class="aihs-intensity-option">
                                                            <input type="radio"
                                                                   name="intensity_<?php echo $question_id; ?>"
                                                                   value="<?php echo esc_attr($intensity_option); ?>"
                                                                   data-intensity-weight="<?php echo esc_attr($intensity_weight); ?>">
                                                            <span class="aihs-intensity-text"><?php echo esc_html($intensity_option); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Question Status -->
                        <div class="aihs-question-status">
                            <span class="aihs-status-indicator" style="display: none;">
                                <span class="aihs-status-icon">✓</span>
                                <span class="aihs-status-text">Odgovoreno</span>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="aihs-no-questions">
                <div class="aihs-no-questions-icon">❓</div>
                <h3>Nema dostupnih pitanja</h3>
                <p>Trenutno nema konfiguriranih pitanja za analizu zdravlja.</p>
            </div>
        <?php endif; ?>

        <!-- Navigation -->
        <?php if (!empty($current_questions)): ?>
            <div class="aihs-questions-navigation">
                <div class="aihs-nav-info">
                    <?php if ($total_pages > 1): ?>
                        <span class="aihs-page-info">
                            Strana <?php echo $current_page; ?> od <?php echo $total_pages; ?>
                        </span>
                    <?php endif; ?>

                    <div class="aihs-answered-summary">
                        <span class="aihs-answered-text">Odgovoreno: </span>
                        <span class="aihs-answered-fraction">
                            <span class="aihs-answered-current">0</span>/<span class="aihs-answered-total"><?php echo count($current_questions); ?></span>
                        </span>
                    </div>
                </div>

                <div class="aihs-nav-buttons">
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo add_query_arg('qpage', $current_page - 1); ?>"
                           class="aihs-btn aihs-btn-prev">
                            <span class="aihs-btn-icon">←</span>
                            Prethodna strana
                        </a>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <button type="button"
                                class="aihs-btn aihs-btn-next"
                                id="aihs-next-page-btn"
                                disabled>
                            Sledeća strana
                            <span class="aihs-btn-icon">→</span>
                        </button>
                    <?php else: ?>
                        <button type="button"
                                class="aihs-btn aihs-btn-finish"
                                id="aihs-finish-btn"
                                disabled>
                            Završi pitanja
                            <span class="aihs-btn-icon">✓</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </form>

    <!-- Question Help -->
    <div class="aihs-questions-help">
        <div class="aihs-help-section">
            <h4>💡 Saveti za bolje rezultate</h4>
            <ul>
                <li>Odgovarajte iskreno na sva pitanja</li>
                <li>Razmislite o poslednjih 30 dana</li>
                <li>Kod neizvesnosti, izaberite što je bliže istini</li>
                <li>Možete se vratiti i promeniti odgovore</li>
            </ul>
        </div>

        <div class="aihs-help-section">
            <h4>🔒 Privatnost podataka</h4>
            <p>Svi vaši odgovori su bezbedni i enkriptovani. Koristimo ih isključivo za kreiranje vaše personalizovane analize.</p>
        </div>
    </div>

    <!-- Real-time Score Preview -->
    <div class="aihs-score-preview" style="display: none;">
        <div class="aihs-preview-header">
            <h4>Trenutni skor</h4>
            <button type="button" class="aihs-preview-toggle">
                <span class="aihs-preview-icon">👁️</span>
            </button>
        </div>
        <div class="aihs-preview-content">
            <div class="aihs-preview-score">
                <span class="aihs-score-number">-</span>
                <span class="aihs-score-max">/100</span>
            </div>
            <div class="aihs-score-category">-</div>
        </div>
    </div>

    <!-- Auto-save Status -->
    <div class="aihs-autosave-notification" style="display: none;">
        <span class="aihs-autosave-icon">💾</span>
        <span class="aihs-autosave-message">Odgovori automatski sačuvani</span>
    </div>
</div>

<style>
.aihs-questions-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.aihs-questions-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-questions-title {
    font-size: 2.2em;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 300;
}

.aihs-questions-subtitle {
    color: #7f8c8d;
    font-size: 1.1em;
    margin-bottom: 30px;
}

.aihs-progress-bar-container {
    max-width: 500px;
    margin: 0 auto;
}

.aihs-progress-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.aihs-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    border-radius: 10px;
    transition: width 0.5s ease;
}

.aihs-progress-text {
    text-align: center;
    font-size: 14px;
    color: #7f8c8d;
}

.aihs-questions-form {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.aihs-questions-list {
    padding: 30px;
}

.aihs-question-item {
    border-bottom: 1px solid #ecf0f1;
    padding: 30px 0;
    transition: all 0.3s ease;
}

.aihs-question-item:last-child {
    border-bottom: none;
}

.aihs-question-item.answered {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    margin: 0 -15px;
    padding: 30px 15px;
}

.aihs-question-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
}

.aihs-question-number {
    background: #3498db;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.aihs-question-content {
    flex: 1;
}

.aihs-question-text {
    font-size: 1.3em;
    color: #2c3e50;
    margin-bottom: 15px;
    line-height: 1.5;
    font-weight: 600;
}

.aihs-question-hint {
    margin-top: 10px;
}

.aihs-hint-toggle {
    background: none;
    border: none;
    color: #3498db;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
}

.aihs-hint-toggle:hover {
    color: #2980b9;
}

.aihs-hint-content {
    margin-top: 10px;
    padding: 15px;
    background: #e8f4fd;
    border-radius: 8px;
    font-size: 14px;
    color: #2c3e50;
    line-height: 1.5;
}

.aihs-answer-binary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.aihs-binary-option {
    border: 2px solid #ecf0f1;
    border-radius: 12px;
    padding: 25px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    overflow: hidden;
}

.aihs-binary-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.aihs-binary-option:hover {
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
}

.aihs-binary-option:hover::before {
    left: 100%;
}

.aihs-binary-option input[type="radio"] {
    display: none;
}

.aihs-binary-option input[type="radio"]:checked + .aihs-option-content {
    color: #3498db;
}

.aihs-binary-option input[type="radio"]:checked + .aihs-option-content .aihs-option-icon {
    background: #3498db;
    color: white;
    transform: scale(1.1);
}

.aihs-option-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    text-align: center;
}

.aihs-option-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #ecf0f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.aihs-option-label {
    font-size: 1.2em;
    font-weight: 600;
    color: #2c3e50;
}

.aihs-answer-scale {
    margin-bottom: 20px;
}

.aihs-scale-labels {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 14px;
    color: #7f8c8d;
}

.aihs-scale-options {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}

.aihs-scale-option {
    cursor: pointer;
    flex: 1;
    min-width: 45px;
}

.aihs-scale-option input[type="radio"] {
    display: none;
}

.aihs-scale-value {
    display: block;
    width: 100%;
    padding: 15px 8px;
    text-align: center;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    background: white;
}

.aihs-scale-option:hover .aihs-scale-value {
    border-color: #3498db;
    background: #f8f9fa;
}

.aihs-scale-option input[type="radio"]:checked + .aihs-scale-value {
    border-color: #3498db;
    background: #3498db;
    color: white;
    transform: scale(1.05);
}

.aihs-sub-question {
    margin-top: 20px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border-left: 4px solid #3498db;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.aihs-sub-question-text {
    font-size: 1.1em;
    color: #2c3e50;
    margin-bottom: 20px;
    font-weight: 600;
}

.aihs-intensity-label {
    color: #7f8c8d;
    margin-bottom: 15px;
    font-size: 14px;
}

.aihs-intensity-options {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.aihs-intensity-option {
    cursor: pointer;
}

.aihs-intensity-option input[type="radio"] {
    display: none;
}

.aihs-intensity-text {
    display: block;
    padding: 10px 20px;
    border: 2px solid #ecf0f1;
    border-radius: 20px;
    background: white;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
}

.aihs-intensity-option:hover .aihs-intensity-text {
    border-color: #3498db;
    background: #f8f9fa;
}

.aihs-intensity-option input[type="radio"]:checked + .aihs-intensity-text {
    border-color: #3498db;
    background: #3498db;
    color: white;
}

.aihs-question-status {
    margin-top: 15px;
    text-align: right;
}

.aihs-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #27ae60;
    font-size: 14px;
    font-weight: 500;
}

.aihs-status-icon {
    background: #27ae60;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.aihs-questions-navigation {
    background: #f8f9fa;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.aihs-nav-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.aihs-page-info {
    font-size: 14px;
    color: #7f8c8d;
}

.aihs-answered-summary {
    font-size: 14px;
    color: #2c3e50;
}

.aihs-answered-fraction {
    font-weight: 600;
    color: #3498db;
}

.aihs-nav-buttons {
    display: flex;
    gap: 15px;
}

.aihs-btn {
    padding: 15px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.aihs-btn-prev {
    background: #ecf0f1;
    color: #2c3e50;
}

.aihs-btn-prev:hover {
    background: #d5dbdb;
}

.aihs-btn-next,
.aihs-btn-finish {
    background: #3498db;
    color: white;
}

.aihs-btn-next:hover,
.aihs-btn-finish:hover {
    background: #2980b9;
    transform: translateY(-1px);
}

.aihs-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.aihs-questions-help {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 40px;
}

.aihs-help-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.aihs-help-section h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.1em;
}

.aihs-help-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-help-section li {
    padding: 8px 0;
    color: #7f8c8d;
    line-height: 1.5;
    position: relative;
    padding-left: 20px;
}

.aihs-help-section li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #27ae60;
    font-weight: bold;
}

.aihs-help-section p {
    color: #7f8c8d;
    line-height: 1.6;
    margin: 0;
}

.aihs-score-preview {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    padding: 20px;
    min-width: 150px;
    z-index: 1000;
}

.aihs-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.aihs-preview-header h4 {
    margin: 0;
    font-size: 14px;
    color: #2c3e50;
}

.aihs-preview-toggle {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
}

.aihs-preview-content {
    text-align: center;
}

.aihs-preview-score {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    margin-bottom: 8px;
}

.aihs-score-number {
    font-size: 2em;
    font-weight: 700;
    color: #3498db;
}

.aihs-score-max {
    color: #7f8c8d;
    font-size: 1.2em;
}

.aihs-score-category {
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 500;
}

.aihs-autosave-notification {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: #27ae60;
    color: white;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    z-index: 1000;
    animation: slideInLeft 0.3s ease;
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.aihs-no-questions {
    text-align: center;
    padding: 60px 20px;
}

.aihs-no-questions-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.aihs-no-questions h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.aihs-no-questions p {
    color: #7f8c8d;
    font-size: 1.1em;
}

/* Responsive */
@media (max-width: 768px) {
    .aihs-questions-container {
        padding: 15px;
    }

    .aihs-questions-title {
        font-size: 1.8em;
    }

    .aihs-questions-list {
        padding: 20px 15px;
    }

    .aihs-question-header {
        gap: 15px;
    }

    .aihs-question-number {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }

    .aihs-question-text {
        font-size: 1.1em;
    }

    .aihs-answer-binary {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .aihs-scale-options {
        flex-wrap: wrap;
        gap: 8px;
    }

    .aihs-scale-option {
        min-width: 40px;
    }

    .aihs-questions-navigation {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
        padding: 20px 15px;
    }

    .aihs-nav-buttons {
        justify-content: space-between;
    }

    .aihs-btn {
        flex: 1;
        justify-content: center;
    }

    .aihs-questions-help {
        grid-template-columns: 1fr;
        margin-top: 30px;
    }

    .aihs-score-preview {
        bottom: 10px;
        right: 10px;
        padding: 15px;
    }

    .aihs-intensity-options {
        justify-content: center;
    }
}

/* Animation for answered questions */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.aihs-question-item.just-answered .aihs-question-number {
    animation: pulse 0.6s ease;
    background: #27ae60;
}
</style>