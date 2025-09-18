<?php
/**
 * Health Form Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$session_id = aihs_get_session_id();
$existing_response = aihs_get_session_response($session_id);
?>

<div class="aihs-health-form-container">
    <div class="aihs-form-header">
        <h2 class="aihs-form-title">Osnovni podaci za analizu</h2>
        <p class="aihs-form-description">
            Molimo unesite vaše osnovne podatke kako bismo mogli da kreiramo personalizovanu analizu vašeg zdravlja.
        </p>
    </div>

    <form id="aihs-health-form" class="aihs-health-form" data-session-id="<?php echo esc_attr($session_id); ?>">
        <?php wp_nonce_field('aihs_form_submit', 'aihs_form_nonce'); ?>

        <div class="aihs-form-grid">
            <!-- Required Fields -->
            <div class="aihs-form-section">
                <h3 class="aihs-section-title">Osnovni podaci *</h3>

                <div class="aihs-form-row">
                    <div class="aihs-form-group">
                        <label for="aihs_first_name">
                            Ime *
                            <span class="aihs-field-required">obavezno</span>
                        </label>
                        <input type="text"
                               id="aihs_first_name"
                               name="first_name"
                               value="<?php echo esc_attr($existing_response->first_name ?? ''); ?>"
                               required
                               autocomplete="given-name">
                        <div class="aihs-field-error" style="display: none;"></div>
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_last_name">
                            Prezime *
                            <span class="aihs-field-required">obavezno</span>
                        </label>
                        <input type="text"
                               id="aihs_last_name"
                               name="last_name"
                               value="<?php echo esc_attr($existing_response->last_name ?? ''); ?>"
                               required
                               autocomplete="family-name">
                        <div class="aihs-field-error" style="display: none;"></div>
                    </div>
                </div>

                <div class="aihs-form-group">
                    <label for="aihs_email">
                        Email adresa *
                        <span class="aihs-field-required">obavezno</span>
                    </label>
                    <input type="email"
                           id="aihs_email"
                           name="email"
                           value="<?php echo esc_attr($existing_response->email ?? ''); ?>"
                           required
                           autocomplete="email"
                           placeholder="vase.ime@example.com">
                    <div class="aihs-field-error" style="display: none;"></div>
                    <div class="aihs-field-help">Koristićemo email za slanje rezultata analize.</div>
                </div>
            </div>

            <?php if ($atts['show_optional_fields'] === 'yes'): ?>
                <!-- Optional Fields -->
                <div class="aihs-form-section">
                    <h3 class="aihs-section-title">Dodatni podaci <span class="aihs-optional">opciono</span></h3>

                    <div class="aihs-form-group">
                        <label for="aihs_phone">Telefon</label>
                        <input type="tel"
                               id="aihs_phone"
                               name="phone"
                               value="<?php echo esc_attr($existing_response->phone ?? ''); ?>"
                               autocomplete="tel"
                               placeholder="+381 60 123 4567">
                        <div class="aihs-field-help">Za hitne konsultacije i potvrdu rezultata.</div>
                    </div>

                    <div class="aihs-form-row">
                        <div class="aihs-form-group">
                            <label for="aihs_age">Godine</label>
                            <input type="number"
                                   id="aihs_age"
                                   name="age"
                                   value="<?php echo esc_attr($existing_response->age ?? ''); ?>"
                                   min="18"
                                   max="100"
                                   placeholder="npr. 35">
                            <div class="aihs-field-help">Pomaže u preciznijoj analizi.</div>
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

                    <div class="aihs-form-group">
                        <label for="aihs_health_goals">Zdravstveni ciljevi</label>
                        <textarea id="aihs_health_goals"
                                  name="health_goals"
                                  rows="3"
                                  placeholder="Opišite šta želite da postignete kroz poboljšanje zdravlja..."><?php echo esc_textarea($existing_response->health_goals ?? ''); ?></textarea>
                        <div class="aihs-field-help">Pomogće AI-u da kreira personalizovanije preporuke.</div>
                    </div>

                    <div class="aihs-form-group">
                        <label for="aihs_current_medications">Trenutne terapije/lekovi</label>
                        <textarea id="aihs_current_medications"
                                  name="current_medications"
                                  rows="2"
                                  placeholder="Navedite lekove ili suplemente koje trenutno uzimate..."><?php echo esc_textarea($existing_response->current_medications ?? ''); ?></textarea>
                        <div class="aihs-field-help">Važno za preporuke suplementacije.</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Consent Section -->
            <div class="aihs-form-section">
                <h3 class="aihs-section-title">Saglasnost</h3>

                <div class="aihs-consent-group">
                    <label class="aihs-checkbox-label">
                        <input type="checkbox"
                               id="aihs_gdpr_consent"
                               name="gdpr_consent"
                               required>
                        <span class="aihs-checkbox-custom"></span>
                        <span class="aihs-checkbox-text">
                            Slažem se sa <a href="#" target="_blank">uslovima korišćenja</a> i
                            <a href="#" target="_blank">politikom privatnosti</a> *
                        </span>
                    </label>
                </div>

                <div class="aihs-consent-group">
                    <label class="aihs-checkbox-label">
                        <input type="checkbox"
                               id="aihs_marketing_consent"
                               name="marketing_consent">
                        <span class="aihs-checkbox-custom"></span>
                        <span class="aihs-checkbox-text">
                            Želim da primam email obaveštenja o novim analizama i zdravstvenim savetima
                        </span>
                    </label>
                </div>

                <div class="aihs-consent-group">
                    <label class="aihs-checkbox-label">
                        <input type="checkbox"
                               id="aihs_data_analysis"
                               name="data_analysis">
                        <span class="aihs-checkbox-custom"></span>
                        <span class="aihs-checkbox-text">
                            Dozvoljavam korišćenje anonimizovanih podataka za poboljšanje AI sistema
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="aihs-form-submit">
            <button type="submit" class="aihs-btn aihs-btn-submit" id="aihs-submit-btn">
                <span class="aihs-btn-text">Nastavi na pitanja</span>
                <span class="aihs-btn-icon">→</span>
                <span class="aihs-btn-loading" style="display: none;">
                    <span class="aihs-spinner"></span>
                </span>
            </button>

            <div class="aihs-form-info">
                <div class="aihs-info-item">
                    <span class="aihs-info-icon">🔒</span>
                    <span class="aihs-info-text">Vaši podaci su bezbedni i enkriptovani</span>
                </div>
                <div class="aihs-info-item">
                    <span class="aihs-info-icon">⚡</span>
                    <span class="aihs-info-text">Analiza se završava za 2-3 minuta</span>
                </div>
                <div class="aihs-info-item">
                    <span class="aihs-info-icon">🎯</span>
                    <span class="aihs-info-text">100% personalizovane preporuke</span>
                </div>
            </div>
        </div>

        <!-- Redirect URL -->
        <?php if (!empty($atts['redirect_url'])): ?>
            <input type="hidden" name="redirect_url" value="<?php echo esc_url($atts['redirect_url']); ?>">
        <?php endif; ?>
    </form>

    <!-- Progress Indicator -->
    <div class="aihs-progress-indicator">
        <div class="aihs-progress-step active">
            <div class="aihs-step-number">1</div>
            <div class="aihs-step-label">Osnovni podaci</div>
        </div>
        <div class="aihs-progress-line"></div>
        <div class="aihs-progress-step">
            <div class="aihs-step-number">2</div>
            <div class="aihs-step-label">Zdravstveni upitnik</div>
        </div>
        <div class="aihs-progress-line"></div>
        <div class="aihs-progress-step">
            <div class="aihs-step-number">3</div>
            <div class="aihs-step-label">AI analiza</div>
        </div>
    </div>

    <!-- Autosave indicator -->
    <div class="aihs-autosave-status" style="display: none;">
        <span class="aihs-autosave-icon">💾</span>
        <span class="aihs-autosave-text">Automatski sačuvano</span>
    </div>
</div>

<style>
.aihs-health-form-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.aihs-form-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-form-title {
    font-size: 2.2em;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 300;
}

.aihs-form-description {
    font-size: 1.1em;
    color: #7f8c8d;
    line-height: 1.6;
    margin: 0;
}

.aihs-health-form {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.aihs-form-grid {
    padding: 40px;
}

.aihs-form-section {
    margin-bottom: 40px;
}

.aihs-form-section:last-child {
    margin-bottom: 0;
}

.aihs-section-title {
    font-size: 1.4em;
    color: #2c3e50;
    margin-bottom: 25px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.aihs-optional {
    font-size: 0.8em;
    color: #7f8c8d;
    font-weight: normal;
    background: #ecf0f1;
    padding: 2px 8px;
    border-radius: 10px;
}

.aihs-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.aihs-form-group {
    margin-bottom: 25px;
}

.aihs-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
}

.aihs-field-required {
    font-size: 11px;
    color: #e74c3c;
    background: #fdf2f2;
    padding: 2px 6px;
    border-radius: 8px;
    font-weight: normal;
}

.aihs-form-group input,
.aihs-form-group select,
.aihs-form-group textarea {
    width: 100%;
    padding: 15px 16px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
    font-family: inherit;
    box-sizing: border-box;
}

.aihs-form-group input:focus,
.aihs-form-group select:focus,
.aihs-form-group textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    transform: translateY(-1px);
}

.aihs-form-group input:invalid {
    border-color: #e74c3c;
}

.aihs-form-group input:valid {
    border-color: #27ae60;
}

.aihs-field-error {
    color: #e74c3c;
    font-size: 13px;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.aihs-field-error::before {
    content: "⚠️";
    font-size: 12px;
}

.aihs-field-help {
    color: #7f8c8d;
    font-size: 13px;
    margin-top: 5px;
    line-height: 1.4;
}

.aihs-consent-group {
    margin-bottom: 20px;
}

.aihs-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    line-height: 1.5;
}

.aihs-checkbox-label input[type="checkbox"] {
    display: none;
}

.aihs-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #bdc3c7;
    border-radius: 4px;
    position: relative;
    flex-shrink: 0;
    transition: all 0.3s ease;
    margin-top: 2px;
}

.aihs-checkbox-custom::after {
    content: "✓";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: white;
    font-size: 12px;
    font-weight: bold;
    transition: transform 0.2s ease;
}

.aihs-checkbox-label input[type="checkbox"]:checked + .aihs-checkbox-custom {
    background: #3498db;
    border-color: #3498db;
}

.aihs-checkbox-label input[type="checkbox"]:checked + .aihs-checkbox-custom::after {
    transform: translate(-50%, -50%) scale(1);
}

.aihs-checkbox-text {
    font-size: 14px;
    color: #2c3e50;
}

.aihs-checkbox-text a {
    color: #3498db;
    text-decoration: none;
}

.aihs-checkbox-text a:hover {
    text-decoration: underline;
}

.aihs-form-submit {
    background: #f8f9fa;
    padding: 40px;
    text-align: center;
}

.aihs-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 40px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.aihs-btn-submit {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
}

.aihs-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
}

.aihs-btn-submit:active {
    transform: translateY(0);
}

.aihs-btn-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.aihs-btn-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.aihs-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.aihs-form-info {
    margin-top: 30px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 25px;
}

.aihs-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #7f8c8d;
    font-size: 14px;
}

.aihs-info-icon {
    font-size: 16px;
}

.aihs-progress-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 40px 0;
    padding: 20px;
}

.aihs-progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.aihs-progress-step.active {
    opacity: 1;
}

.aihs-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ecf0f1;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.aihs-progress-step.active .aihs-step-number {
    background: #3498db;
    color: white;
}

.aihs-step-label {
    font-size: 12px;
    color: #7f8c8d;
    text-align: center;
    font-weight: 500;
}

.aihs-progress-step.active .aihs-step-label {
    color: #2c3e50;
    font-weight: 600;
}

.aihs-progress-line {
    width: 80px;
    height: 2px;
    background: #ecf0f1;
    margin: 0 10px;
}

.aihs-autosave-status {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #27ae60;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    z-index: 1000;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .aihs-health-form-container {
        padding: 15px;
    }

    .aihs-form-title {
        font-size: 1.8em;
    }

    .aihs-form-grid {
        padding: 30px 20px;
    }

    .aihs-form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .aihs-form-info {
        flex-direction: column;
        gap: 15px;
    }

    .aihs-progress-indicator {
        padding: 15px 10px;
        margin: 30px 0;
    }

    .aihs-progress-line {
        width: 40px;
        margin: 0 5px;
    }

    .aihs-step-label {
        display: none;
    }

    .aihs-form-submit {
        padding: 30px 20px;
    }

    .aihs-btn-submit {
        width: 100%;
        justify-content: center;
    }
}

/* Form validation states */
.aihs-form-group.error input,
.aihs-form-group.error select,
.aihs-form-group.error textarea {
    border-color: #e74c3c;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.aihs-form-group.success input,
.aihs-form-group.success select,
.aihs-form-group.success textarea {
    border-color: #27ae60;
}

.aihs-form-group.error .aihs-field-error {
    display: block;
}

/* Focus states */
.aihs-form-group input:focus,
.aihs-form-group select:focus,
.aihs-form-group textarea:focus {
    background: #fafbfc;
}

/* Placeholder styles */
.aihs-form-group input::placeholder,
.aihs-form-group textarea::placeholder {
    color: #bdc3c7;
    font-style: italic;
}

/* Loading state */
.aihs-btn-submit.loading .aihs-btn-text,
.aihs-btn-submit.loading .aihs-btn-icon {
    opacity: 0;
}

.aihs-btn-submit.loading .aihs-btn-loading {
    display: block;
}
</style>