<?php
/**
 * Main Health Quiz Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$ui_settings = get_option('aihs_ui_settings', array());
$form_title = $ui_settings['form_title'] ?? 'AI Analiza Zdravlja';
$form_description = $ui_settings['form_description'] ?? 'Kompletna analiza vašeg zdravstvenog stanja uz personalizovane preporuke.';
$questions = aihs_get_health_questions();
$session_id = aihs_get_session_id();

// Check if user already completed the quiz
if ($existing_response && $existing_response->completion_status === 'analysis_completed') {
    // Show results instead
    include AIHS_PLUGIN_DIR . 'frontend/templates/quiz-results.php';
    return;
}
?>

<div class="aihs-quiz-container" data-style="<?php echo esc_attr($atts['style']); ?>" data-theme="<?php echo esc_attr($atts['theme']); ?>">

    <!-- Quiz Header -->
    <div class="aihs-quiz-header">
        <h2 class="aihs-quiz-title"><?php echo esc_html($form_title); ?></h2>
        <?php if ($form_description): ?>
            <p class="aihs-quiz-description"><?php echo esc_html($form_description); ?></p>
        <?php endif; ?>

        <?php if ($atts['show_progress'] === 'yes'): ?>
            <div class="aihs-progress-container">
                <div class="aihs-progress-bar" style="--progress-style: <?php echo esc_attr($ui_settings['progress_bar_style'] ?? 'gradient'); ?>">
                    <div class="aihs-progress-fill" data-progress="0"></div>
                    <?php if (!empty($ui_settings['show_progress_percentage'])): ?>
                        <span class="aihs-progress-text">0%</span>
                    <?php endif; ?>
                </div>
                <div class="aihs-progress-label">
                    <span class="aihs-current-step">1</span> od <span class="aihs-total-steps"><?php echo count($questions) + 1; ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quiz Form -->
    <form id="aihs-health-quiz-form" class="aihs-quiz-form" data-session-id="<?php echo esc_attr($session_id); ?>">
        <?php wp_nonce_field('aihs_quiz_submit', 'aihs_quiz_nonce'); ?>

        <!-- Step 1: Personal Information -->
        <div class="aihs-quiz-step aihs-step-personal active" data-step="1">
            <div class="aihs-step-content">
                <h3 class="aihs-step-title">Osnovni podaci</h3>
                <p class="aihs-step-description">Molimo unesite vaše osnovne podatke za personalizovanu analizu.</p>

                <div class="aihs-form-grid">
                    <div class="aihs-form-group">
                        <label for="aihs_first_name">Ime *</label>
                        <input type="text"
                               id="aihs_first_name"
                               name="first_name"
                               value="<?php echo esc_attr($existing_response->first_name ?? ''); ?>"
                               required>
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_last_name">Prezime *</label>
                        <input type="text"
                               id="aihs_last_name"
                               name="last_name"
                               value="<?php echo esc_attr($existing_response->last_name ?? ''); ?>"
                               required>
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_email">Email adresa *</label>
                        <input type="email"
                               id="aihs_email"
                               name="email"
                               value="<?php echo esc_attr($existing_response->email ?? ''); ?>"
                               required>
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_phone">Telefon</label>
                        <input type="tel"
                               id="aihs_phone"
                               name="phone"
                               value="<?php echo esc_attr($existing_response->phone ?? ''); ?>"
                               placeholder="+381 60 123 4567">
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_age">Godine</label>
                        <input type="number"
                               id="aihs_age"
                               name="age"
                               value="<?php echo esc_attr($existing_response->age ?? ''); ?>"
                               min="18" max="100">
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_gender">Pol</label>
                        <select id="aihs_gender" name="gender">
                            <option value="">Izaberite...</option>
                            <option value="muski" <?php selected($existing_response->gender ?? '', 'muski'); ?>>Muški</option>
                            <option value="zenski" <?php selected($existing_response->gender ?? '', 'zenski'); ?>>Ženski</option>
                            <option value="drugo" <?php selected($existing_response->gender ?? '', 'drugo'); ?>>Drugo</option>
                        </select>
                    </div>
                </div>

                <div class="aihs-gdpr-consent">
                    <label class="aihs-checkbox-label">
                        <input type="checkbox" id="aihs_gdpr_consent" name="gdpr_consent" required>
                        <span class="aihs-checkmark"></span>
                        Slažem se sa <a href="#" target="_blank">uslovima korišćenja</a> i
                        <a href="#" target="_blank">politikom privatnosti</a> *
                    </label>
                </div>
            </div>

            <div class="aihs-step-navigation">
                <button type="button" class="aihs-btn aihs-btn-next" data-next-step="2">
                    Nastavi na pitanja
                    <span class="aihs-btn-icon">→</span>
                </button>
            </div>
        </div>

        <!-- Health Questions Steps -->
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $index => $question): ?>
                <div class="aihs-quiz-step aihs-step-question" data-step="<?php echo $index + 2; ?>" data-question-id="<?php echo esc_attr($question['id']); ?>">
                    <div class="aihs-step-content">
                        <div class="aihs-question-header">
                            <span class="aihs-question-number"><?php echo $index + 1; ?></span>
                            <h3 class="aihs-question-text"><?php echo esc_html($question['text']); ?></h3>
                        </div>

                        <div class="aihs-question-answers">
                            <?php if ($question['type'] === 'scale'): ?>
                                <!-- Scale 1-10 -->
                                <div class="aihs-scale-container">
                                    <div class="aihs-scale-labels">
                                        <span>Uopšte ne</span>
                                        <span>Izuzetno</span>
                                    </div>
                                    <div class="aihs-scale-options">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <label class="aihs-scale-option">
                                                <input type="radio"
                                                       name="question_<?php echo $question['id']; ?>"
                                                       value="<?php echo $i; ?>">
                                                <span class="aihs-scale-number"><?php echo $i; ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Binary Yes/No -->
                                <div class="aihs-binary-options">
                                    <label class="aihs-option-card">
                                        <input type="radio"
                                               name="question_<?php echo $question['id']; ?>"
                                               value="Da">
                                        <div class="aihs-option-content">
                                            <span class="aihs-option-icon">✓</span>
                                            <span class="aihs-option-text">Da</span>
                                        </div>
                                    </label>

                                    <label class="aihs-option-card">
                                        <input type="radio"
                                               name="question_<?php echo $question['id']; ?>"
                                               value="Ne">
                                        <div class="aihs-option-content">
                                            <span class="aihs-option-icon">✗</span>
                                            <span class="aihs-option-text">Ne</span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Sub-question for "Da" answers -->
                                <?php if (!empty($question['sub_question'])): ?>
                                    <div class="aihs-sub-question" style="display: none;">
                                        <h4 class="aihs-sub-question-text"><?php echo esc_html($question['sub_question']); ?></h4>

                                        <?php if ($question['type'] === 'intensity' && !empty($question['intensity_options'])): ?>
                                            <div class="aihs-intensity-options">
                                                <?php foreach ($question['intensity_options'] as $intensity_index => $intensity_option): ?>
                                                    <label class="aihs-intensity-option">
                                                        <input type="radio"
                                                               name="intensity_<?php echo $question['id']; ?>"
                                                               value="<?php echo esc_attr($intensity_option); ?>">
                                                        <span class="aihs-intensity-text"><?php echo esc_html($intensity_option); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($question['ai_hint'])): ?>
                            <div class="aihs-question-help">
                                <button type="button" class="aihs-help-toggle">
                                    <span class="aihs-help-icon">?</span>
                                    Dodatne informacije
                                </button>
                                <div class="aihs-help-content" style="display: none;">
                                    <p><?php echo esc_html($question['ai_hint']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="aihs-step-navigation">
                        <button type="button" class="aihs-btn aihs-btn-prev" data-prev-step="<?php echo $index + 1; ?>">
                            <span class="aihs-btn-icon">←</span>
                            Nazad
                        </button>

                        <?php if ($index < count($questions) - 1): ?>
                            <button type="button" class="aihs-btn aihs-btn-next" data-next-step="<?php echo $index + 3; ?>">
                                Sledeće pitanje
                                <span class="aihs-btn-icon">→</span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="aihs-btn aihs-btn-finish" data-next-step="final">
                                Završi analizu
                                <span class="aihs-btn-icon">✓</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Final Step: Processing -->
        <div class="aihs-quiz-step aihs-step-final" data-step="final">
            <div class="aihs-step-content">
                <div class="aihs-processing-animation">
                    <div class="aihs-spinner"></div>
                    <h3 class="aihs-processing-title">Analiziramo vaše odgovore...</h3>
                    <p class="aihs-processing-text">AI sistem kreira personalizovanu analizu vašeg zdravlja i preporuke za poboljšanje.</p>

                    <div class="aihs-processing-steps">
                        <div class="aihs-processing-step active">
                            <span class="aihs-step-icon">📊</span>
                            <span class="aihs-step-text">Kalkulacija zdravstvenog skora</span>
                        </div>
                        <div class="aihs-processing-step">
                            <span class="aihs-step-icon">🧠</span>
                            <span class="aihs-step-text">AI analiza odgovora</span>
                        </div>
                        <div class="aihs-processing-step">
                            <span class="aihs-step-icon">💊</span>
                            <span class="aihs-step-text">Kreiranje personalizovanih preporuka</span>
                        </div>
                        <div class="aihs-processing-step">
                            <span class="aihs-step-icon">📦</span>
                            <span class="aihs-step-text">Generisanje paketa proizvoda</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Auto-save indicator -->
    <div class="aihs-autosave-indicator" style="display: none;">
        <span class="aihs-autosave-icon">💾</span>
        <span class="aihs-autosave-text">Automatski sačuvano</span>
    </div>

    <!-- Quiz navigation help -->
    <div class="aihs-quiz-help">
        <div class="aihs-help-item">
            <span class="aihs-help-key">Enter</span>
            <span class="aihs-help-action">Nastavi</span>
        </div>
        <div class="aihs-help-item">
            <span class="aihs-help-key">Backspace</span>
            <span class="aihs-help-action">Nazad</span>
        </div>
    </div>
</div>

<style>
.aihs-quiz-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.aihs-quiz-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-quiz-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 300;
}

.aihs-quiz-description {
    font-size: 1.1em;
    color: #7f8c8d;
    margin-bottom: 30px;
    line-height: 1.6;
}

.aihs-progress-container {
    margin-bottom: 30px;
}

.aihs-progress-bar {
    width: 100%;
    height: 8px;
    background: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    margin-bottom: 10px;
}

.aihs-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    border-radius: 10px;
    width: 0%;
    transition: width 0.5s ease;
}

.aihs-progress-bar[style*="solid"] .aihs-progress-fill {
    background: #3498db;
}

.aihs-progress-bar[style*="striped"] .aihs-progress-fill {
    background: repeating-linear-gradient(45deg, #3498db 0px, #3498db 10px, #2980b9 10px, #2980b9 20px);
}

.aihs-progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.aihs-progress-label {
    text-align: center;
    font-size: 14px;
    color: #7f8c8d;
}

.aihs-quiz-step {
    display: none;
    animation: fadeIn 0.5s ease;
}

.aihs-quiz-step.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.aihs-step-content {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.aihs-step-title {
    font-size: 1.8em;
    color: #2c3e50;
    margin-bottom: 15px;
    text-align: center;
}

.aihs-step-description {
    color: #7f8c8d;
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.1em;
}

.aihs-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.aihs-form-group {
    display: flex;
    flex-direction: column;
}

.aihs-form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
}

.aihs-form-group input,
.aihs-form-group select {
    padding: 12px 16px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    background: white;
}

.aihs-form-group input:focus,
.aihs-form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.aihs-gdpr-consent {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.aihs-checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
}

.aihs-checkbox-label input[type="checkbox"] {
    margin-right: 12px;
    margin-top: 2px;
}

.aihs-question-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
}

.aihs-question-number {
    background: #3498db;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 20px;
    flex-shrink: 0;
}

.aihs-question-text {
    font-size: 1.4em;
    color: #2c3e50;
    margin: 0;
    line-height: 1.4;
}

.aihs-binary-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.aihs-option-card {
    border: 2px solid #ecf0f1;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.aihs-option-card:hover {
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
}

.aihs-option-card input[type="radio"] {
    display: none;
}

.aihs-option-card input[type="radio"]:checked + .aihs-option-content {
    color: #3498db;
}

.aihs-option-card input[type="radio"]:checked + .aihs-option-content .aihs-option-icon {
    background: #3498db;
    color: white;
    transform: scale(1.1);
}

.aihs-option-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
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

.aihs-option-text {
    font-size: 1.2em;
    font-weight: 600;
    color: #2c3e50;
}

.aihs-scale-container {
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
    gap: 5px;
}

.aihs-scale-option {
    flex: 1;
    cursor: pointer;
}

.aihs-scale-option input[type="radio"] {
    display: none;
}

.aihs-scale-number {
    display: block;
    width: 100%;
    padding: 15px 10px;
    text-align: center;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    background: white;
}

.aihs-scale-option:hover .aihs-scale-number {
    border-color: #3498db;
    background: #f8f9fa;
}

.aihs-scale-option input[type="radio"]:checked + .aihs-scale-number {
    border-color: #3498db;
    background: #3498db;
    color: white;
}

.aihs-sub-question {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.aihs-sub-question-text {
    font-size: 1.1em;
    color: #2c3e50;
    margin-bottom: 15px;
}

.aihs-intensity-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
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
}

.aihs-intensity-option:hover .aihs-intensity-text {
    border-color: #3498db;
}

.aihs-intensity-option input[type="radio"]:checked + .aihs-intensity-text {
    border-color: #3498db;
    background: #3498db;
    color: white;
}

.aihs-question-help {
    margin-top: 20px;
}

.aihs-help-toggle {
    background: none;
    border: none;
    color: #3498db;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.aihs-help-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.aihs-help-content {
    margin-top: 10px;
    padding: 15px;
    background: #e8f4fd;
    border-radius: 6px;
    font-size: 14px;
    color: #2c3e50;
}

.aihs-step-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.aihs-btn {
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.aihs-btn-next,
.aihs-btn-finish {
    background: #3498db;
    color: white;
    margin-left: auto;
}

.aihs-btn-next:hover,
.aihs-btn-finish:hover {
    background: #2980b9;
    transform: translateY(-1px);
}

.aihs-btn-prev {
    background: #ecf0f1;
    color: #2c3e50;
}

.aihs-btn-prev:hover {
    background: #d5dbdb;
}

.aihs-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.aihs-processing-animation {
    text-align: center;
    padding: 40px 20px;
}

.aihs-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #ecf0f1;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 30px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.aihs-processing-title {
    font-size: 1.5em;
    color: #2c3e50;
    margin-bottom: 15px;
}

.aihs-processing-text {
    color: #7f8c8d;
    margin-bottom: 30px;
}

.aihs-processing-steps {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-width: 400px;
    margin: 0 auto;
}

.aihs-processing-step {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
    opacity: 0.5;
}

.aihs-processing-step.active {
    opacity: 1;
    background: #e8f4fd;
}

.aihs-processing-step.completed {
    opacity: 1;
    background: #d5f4e6;
}

.aihs-step-icon {
    font-size: 20px;
}

.aihs-step-text {
    font-size: 14px;
    color: #2c3e50;
}

.aihs-autosave-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #2ecc71;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
    z-index: 1000;
}

.aihs-quiz-help {
    position: fixed;
    bottom: 20px;
    left: 20px;
    display: flex;
    gap: 10px;
    z-index: 1000;
}

.aihs-help-item {
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.aihs-help-key {
    background: rgba(255,255,255,0.2);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

/* Responsive */
@media (max-width: 768px) {
    .aihs-quiz-container {
        padding: 15px;
    }

    .aihs-quiz-title {
        font-size: 2em;
    }

    .aihs-form-grid {
        grid-template-columns: 1fr;
    }

    .aihs-binary-options {
        grid-template-columns: 1fr;
    }

    .aihs-step-content {
        padding: 30px 20px;
    }

    .aihs-step-navigation {
        flex-direction: column;
    }

    .aihs-btn {
        width: 100%;
        justify-content: center;
    }

    .aihs-scale-options {
        flex-wrap: wrap;
    }

    .aihs-quiz-help {
        display: none;
    }
}

/* Dark theme */
.aihs-quiz-container[data-theme="dark"] {
    background: #2c3e50;
    color: #ecf0f1;
}

.aihs-quiz-container[data-theme="dark"] .aihs-step-content {
    background: #34495e;
}

.aihs-quiz-container[data-theme="dark"] .aihs-quiz-title,
.aihs-quiz-container[data-theme="dark"] .aihs-question-text,
.aihs-quiz-container[data-theme="dark"] .aihs-step-title {
    color: #ecf0f1;
}

.aihs-quiz-container[data-theme="dark"] .aihs-form-group input,
.aihs-quiz-container[data-theme="dark"] .aihs-form-group select {
    background: #2c3e50;
    border-color: #4a5f7a;
    color: #ecf0f1;
}

.aihs-quiz-container[data-theme="dark"] .aihs-option-card {
    background: #2c3e50;
    border-color: #4a5f7a;
}

.aihs-quiz-container[data-theme="dark"] .aihs-option-text {
    color: #ecf0f1;
}
</style>