<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user_id = get_current_user_id();
$user_responses = AIHS_Database::get_user_responses($user_id);
$user_packages = AIHS_Database::get_user_packages($user_id);
$latest_response = !empty($user_responses) ? $user_responses[0] : null;

$user_info = wp_get_current_user();
$user_meta = get_user_meta($user_id);
?>

<div id="aihs-dashboard-container" class="aihs-container">
    <div class="aihs-dashboard-header">
        <div class="aihs-user-welcome">
            <h1 class="aihs-dashboard-title">
                <?php printf(__('Dobrodošli, %s!', 'ai-health-savetnik'), esc_html($user_info->display_name)); ?>
            </h1>
            <p class="aihs-dashboard-subtitle">
                <?php _e('Pratite svoj zdravstveni napredak i upravljajte svojim profilom', 'ai-health-savetnik'); ?>
            </p>
        </div>
        <div class="aihs-dashboard-date">
            <span class="aihs-current-date"><?php echo date_i18n('j F Y'); ?></span>
        </div>
    </div>

    <?php if ($latest_response): ?>
    <div class="aihs-health-overview">
        <div class="aihs-current-score">
            <div class="aihs-score-display">
                <div class="aihs-score-circle">
                    <svg viewBox="0 0 100 100" class="aihs-score-svg">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e9ecef" stroke-width="8"/>
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#27ae60" stroke-width="8"
                                stroke-dasharray="<?php echo (282.6 * $latest_response->health_score / 100); ?> 282.6"
                                stroke-dashoffset="0" transform="rotate(-90 50 50)"/>
                    </svg>
                    <div class="aihs-score-text">
                        <span class="aihs-score-number"><?php echo esc_html($latest_response->health_score); ?></span>
                        <span class="aihs-score-label">/100</span>
                    </div>
                </div>
                <div class="aihs-score-info">
                    <h3><?php _e('Vaš trenutni zdravstveni skor', 'ai-health-savetnik'); ?></h3>
                    <p class="aihs-score-date">
                        <?php printf(__('Poslednja procena: %s', 'ai-health-savetnik'),
                              date_i18n('j.m.Y', strtotime($latest_response->created_at))); ?>
                    </p>
                    <a href="<?php echo home_url('/zdravstveni-upitnik'); ?>" class="aihs-btn-secondary">
                        <?php _e('Ponovi procenu', 'ai-health-savetnik'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="aihs-health-metrics">
            <div class="aihs-metric-card">
                <div class="aihs-metric-icon">
                    <i class="aihs-icon-chart"></i>
                </div>
                <div class="aihs-metric-content">
                    <span class="aihs-metric-value"><?php echo count($user_responses); ?></span>
                    <span class="aihs-metric-label"><?php _e('Procena ukupno', 'ai-health-savetnik'); ?></span>
                </div>
            </div>

            <div class="aihs-metric-card">
                <div class="aihs-metric-icon">
                    <i class="aihs-icon-package"></i>
                </div>
                <div class="aihs-metric-content">
                    <span class="aihs-metric-value"><?php echo count($user_packages); ?></span>
                    <span class="aihs-metric-label"><?php _e('Sačuvani paketi', 'ai-health-savetnik'); ?></span>
                </div>
            </div>

            <div class="aihs-metric-card">
                <div class="aihs-metric-icon">
                    <i class="aihs-icon-trend"></i>
                </div>
                <div class="aihs-metric-content">
                    <?php
                    $trend = 0;
                    if (count($user_responses) >= 2) {
                        $prev_score = $user_responses[1]->health_score;
                        $trend = $latest_response->health_score - $prev_score;
                    }
                    ?>
                    <span class="aihs-metric-value <?php echo $trend >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $trend >= 0 ? '+' : ''; ?><?php echo $trend; ?>
                    </span>
                    <span class="aihs-metric-label"><?php _e('Trend', 'ai-health-savetnik'); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="aihs-dashboard-content">
        <div class="aihs-main-content">

            <!-- Health History Section -->
            <div class="aihs-dashboard-section">
                <div class="aihs-section-header">
                    <h2><?php _e('Istorija zdravstvenih procena', 'ai-health-savetnik'); ?></h2>
                    <a href="<?php echo home_url('/zdravstveni-upitnik'); ?>" class="aihs-btn-primary">
                        <?php _e('Nova procena', 'ai-health-savetnik'); ?>
                    </a>
                </div>

                <?php if (!empty($user_responses)): ?>
                <div class="aihs-history-timeline">
                    <?php foreach (array_slice($user_responses, 0, 5) as $response):
                        $score_class = '';
                        if ($response->health_score >= 80) $score_class = 'excellent';
                        elseif ($response->health_score >= 60) $score_class = 'good';
                        elseif ($response->health_score >= 40) $score_class = 'fair';
                        else $score_class = 'poor';
                    ?>
                    <div class="aihs-timeline-item">
                        <div class="aihs-timeline-marker <?php echo $score_class; ?>">
                            <span class="aihs-timeline-score"><?php echo esc_html($response->health_score); ?></span>
                        </div>
                        <div class="aihs-timeline-content">
                            <div class="aihs-timeline-header">
                                <h4><?php printf(__('Procena #%d', 'ai-health-savetnik'), $response->id); ?></h4>
                                <span class="aihs-timeline-date">
                                    <?php echo date_i18n('j.m.Y H:i', strtotime($response->created_at)); ?>
                                </span>
                            </div>
                            <div class="aihs-timeline-details">
                                <p class="aihs-score-category">
                                    <?php
                                    $category = '';
                                    if ($response->health_score >= 80) $category = __('Odličo zdravlje', 'ai-health-savetnik');
                                    elseif ($response->health_score >= 60) $category = __('Dobro zdravlje', 'ai-health-savetnik');
                                    elseif ($response->health_score >= 40) $category = __('Prosečno zdravlje', 'ai-health-savetnik');
                                    else $category = __('Potrebno poboljšanje', 'ai-health-savetnik');
                                    echo $category;
                                    ?>
                                </p>
                                <div class="aihs-timeline-actions">
                                    <a href="<?php echo home_url('/rezultati-upitnika/?response_id=' . $response->id); ?>"
                                       class="aihs-btn-secondary aihs-btn-small">
                                        <?php _e('Prikaži detalje', 'ai-health-savetnik'); ?>
                                    </a>
                                    <?php if ($response->ai_analysis): ?>
                                    <button class="aihs-btn-secondary aihs-btn-small aihs-view-analysis"
                                            data-response-id="<?php echo esc_attr($response->id); ?>">
                                        <?php _e('AI Analiza', 'ai-health-savetnik'); ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (count($user_responses) > 5): ?>
                    <div class="aihs-timeline-item">
                        <div class="aihs-timeline-marker more">
                            <span>+<?php echo count($user_responses) - 5; ?></span>
                        </div>
                        <div class="aihs-timeline-content">
                            <a href="<?php echo home_url('/moja-istorija'); ?>" class="aihs-btn-secondary">
                                <?php _e('Prikaži sve procene', 'ai-health-savetnik'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="aihs-empty-state">
                    <div class="aihs-empty-icon">
                        <i class="aihs-icon-heart"></i>
                    </div>
                    <h3><?php _e('Još nema procena', 'ai-health-savetnik'); ?></h3>
                    <p><?php _e('Započnite svoju zdravstvenu putanju prvom procenom.', 'ai-health-savetnik'); ?></p>
                    <a href="<?php echo home_url('/zdravstveni-upitnik'); ?>" class="aihs-btn-primary">
                        <?php _e('Pokreni prvu procenu', 'ai-health-savetnik'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Saved Packages Section -->
            <div class="aihs-dashboard-section">
                <div class="aihs-section-header">
                    <h2><?php _e('Sačuvani paketi', 'ai-health-savetnik'); ?></h2>
                    <a href="<?php echo home_url('/zdravstveni-paketi'); ?>" class="aihs-btn-secondary">
                        <?php _e('Pogledaj sve pakete', 'ai-health-savetnik'); ?>
                    </a>
                </div>

                <?php if (!empty($user_packages)): ?>
                <div class="aihs-saved-packages">
                    <?php foreach (array_slice($user_packages, 0, 3) as $package):
                        $package_data = json_decode($package->package_data, true) ?: array();
                        $original_price = floatval($package_data['original_price'] ?? 0);
                        $discount_percentage = floatval($package_data['discount_percentage'] ?? 0);
                        $final_price = $original_price * (1 - $discount_percentage / 100);
                        $currency_symbol = get_woocommerce_currency_symbol();
                    ?>
                    <div class="aihs-package-card-mini">
                        <div class="aihs-package-mini-header">
                            <h4><?php echo esc_html($package->name); ?></h4>
                            <?php if ($discount_percentage > 0): ?>
                            <span class="aihs-discount-badge">-<?php echo round($discount_percentage); ?>%</span>
                            <?php endif; ?>
                        </div>
                        <p class="aihs-package-mini-description">
                            <?php echo esc_html(wp_trim_words($package->description, 15)); ?>
                        </p>
                        <div class="aihs-package-mini-price">
                            <?php if ($discount_percentage > 0): ?>
                            <span class="aihs-original-price-mini"><?php echo $currency_symbol . number_format($original_price, 2); ?></span>
                            <?php endif; ?>
                            <span class="aihs-final-price-mini"><?php echo $currency_symbol . number_format($final_price, 2); ?></span>
                        </div>
                        <div class="aihs-package-mini-actions">
                            <button class="aihs-btn-primary aihs-btn-small aihs-add-package-to-cart"
                                    data-package-id="<?php echo esc_attr($package->id); ?>"
                                    data-nonce="<?php echo wp_create_nonce('aihs_add_package_' . $package->id); ?>">
                                <?php _e('Dodaj u korpu', 'ai-health-savetnik'); ?>
                            </button>
                            <button class="aihs-btn-secondary aihs-btn-small aihs-remove-saved-package"
                                    data-package-id="<?php echo esc_attr($package->id); ?>"
                                    data-nonce="<?php echo wp_create_nonce('aihs_remove_saved_package_' . $package->id); ?>">
                                <?php _e('Ukloni', 'ai-health-savetnik'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="aihs-empty-state">
                    <div class="aihs-empty-icon">
                        <i class="aihs-icon-package"></i>
                    </div>
                    <h3><?php _e('Nema sačuvanih paketa', 'ai-health-savetnik'); ?></h3>
                    <p><?php _e('Sačuvajte pakete koji vas zanimaju za lakše pronalaženje kasnije.', 'ai-health-savetnik'); ?></p>
                    <a href="<?php echo home_url('/zdravstveni-paketi'); ?>" class="aihs-btn-secondary">
                        <?php _e('Istraži pakete', 'ai-health-savetnik'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="aihs-sidebar">

            <!-- User Profile Section -->
            <div class="aihs-sidebar-section">
                <h3><?php _e('Profil', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-profile-card">
                    <div class="aihs-profile-avatar">
                        <?php echo get_avatar($user_id, 60); ?>
                    </div>
                    <div class="aihs-profile-info">
                        <h4><?php echo esc_html($user_info->display_name); ?></h4>
                        <p><?php echo esc_html($user_info->user_email); ?></p>
                        <a href="<?php echo admin_url('profile.php'); ?>" class="aihs-btn-secondary aihs-btn-small">
                            <?php _e('Uredi profil', 'ai-health-savetnik'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Section -->
            <div class="aihs-sidebar-section">
                <h3><?php _e('Brze statistike', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-quick-stats">
                    <div class="aihs-stat-item">
                        <span class="aihs-stat-label"><?php _e('Član od:', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-stat-value">
                            <?php echo date_i18n('F Y', strtotime($user_info->user_registered)); ?>
                        </span>
                    </div>
                    <div class="aihs-stat-item">
                        <span class="aihs-stat-label"><?php _e('Ukupno procena:', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-stat-value"><?php echo count($user_responses); ?></span>
                    </div>
                    <?php if ($latest_response): ?>
                    <div class="aihs-stat-item">
                        <span class="aihs-stat-label"><?php _e('Poslednja procena:', 'ai-health-savetnik'); ?></span>
                        <span class="aihs-stat-value">
                            <?php echo human_time_diff(strtotime($latest_response->created_at), current_time('timestamp')); ?> <?php _e('pre', 'ai-health-savetnik'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Tips Section -->
            <div class="aihs-sidebar-section">
                <h3><?php _e('Zdravstveni saveti', 'ai-health-savetnik'); ?></h3>
                <div class="aihs-health-tips">
                    <div class="aihs-tip-item">
                        <div class="aihs-tip-icon">
                            <i class="aihs-icon-water"></i>
                        </div>
                        <div class="aihs-tip-content">
                            <h5><?php _e('Hidratacija', 'ai-health-savetnik'); ?></h5>
                            <p><?php _e('Pijte najmanje 8 čaša vode dnevno za optimalno funkcionisanje organizma.', 'ai-health-savetnik'); ?></p>
                        </div>
                    </div>
                    <div class="aihs-tip-item">
                        <div class="aihs-tip-icon">
                            <i class="aihs-icon-sleep"></i>
                        </div>
                        <div class="aihs-tip-content">
                            <h5><?php _e('Kvalitetan san', 'ai-health-savetnik'); ?></h5>
                            <p><?php _e('7-9 sati sna noću je ključno za regeneraciju i zdravlje.', 'ai-health-savetnik'); ?></p>
                        </div>
                    </div>
                    <div class="aihs-tip-item">
                        <div class="aihs-tip-icon">
                            <i class="aihs-icon-exercise"></i>
                        </div>
                        <div class="aihs-tip-content">
                            <h5><?php _e('Redovna aktivnost', 'ai-health-savetnik'); ?></h5>
                            <p><?php _e('30 minuta umerne vežbe dnevno poboljšava zdravlje srca i raspoloženje.', 'ai-health-savetnik'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- AI Analysis Modal -->
<div id="aihs-analysis-modal" class="aihs-modal" style="display: none;">
    <div class="aihs-modal-overlay"></div>
    <div class="aihs-modal-content">
        <div class="aihs-modal-header">
            <h3><?php _e('AI Analiza zdravlja', 'ai-health-savetnik'); ?></h3>
            <button class="aihs-modal-close">&times;</button>
        </div>
        <div class="aihs-modal-body">
            <div id="aihs-analysis-loading" class="aihs-loading">
                <div class="aihs-spinner"></div>
                <span><?php _e('Učitavanje analize...', 'ai-health-savetnik'); ?></span>
            </div>
            <div id="aihs-analysis-content" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
.aihs-dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.aihs-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
}

.aihs-dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.aihs-dashboard-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.aihs-current-date {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
}

.aihs-health-overview {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 30px;
    margin-bottom: 40px;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.aihs-score-display {
    display: flex;
    align-items: center;
    gap: 20px;
}

.aihs-score-circle {
    position: relative;
    width: 120px;
    height: 120px;
}

.aihs-score-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.aihs-score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.aihs-score-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    display: block;
    line-height: 1;
}

.aihs-score-label {
    font-size: 0.9rem;
    color: #7f8c8d;
}

.aihs-score-info h3 {
    color: #2c3e50;
    margin-bottom: 8px;
}

.aihs-score-date {
    color: #7f8c8d;
    margin-bottom: 15px;
}

.aihs-health-metrics {
    display: flex;
    gap: 20px;
    align-items: center;
}

.aihs-metric-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    min-width: 150px;
}

.aihs-metric-icon {
    width: 40px;
    height: 40px;
    background: #e3f2fd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1976d2;
    font-size: 1.2rem;
}

.aihs-metric-content {
    display: flex;
    flex-direction: column;
}

.aihs-metric-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.aihs-metric-value.positive {
    color: #27ae60;
}

.aihs-metric-value.negative {
    color: #e74c3c;
}

.aihs-metric-label {
    font-size: 0.8rem;
    color: #7f8c8d;
}

.aihs-dashboard-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
}

.aihs-dashboard-section {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.aihs-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.aihs-section-header h2 {
    color: #2c3e50;
    font-size: 1.4rem;
    font-weight: 600;
}

.aihs-history-timeline {
    position: relative;
}

.aihs-history-timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.aihs-timeline-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-left: 60px;
}

.aihs-timeline-marker {
    position: absolute;
    left: 0;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    border: 4px solid white;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.aihs-timeline-marker.excellent { background: #27ae60; }
.aihs-timeline-marker.good { background: #f39c12; }
.aihs-timeline-marker.fair { background: #e67e22; }
.aihs-timeline-marker.poor { background: #e74c3c; }
.aihs-timeline-marker.more { background: #95a5a6; }

.aihs-timeline-score {
    font-size: 0.9rem;
}

.aihs-timeline-content {
    flex: 1;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
}

.aihs-timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.aihs-timeline-header h4 {
    color: #2c3e50;
    font-size: 1rem;
    margin: 0;
}

.aihs-timeline-date {
    color: #7f8c8d;
    font-size: 0.8rem;
}

.aihs-score-category {
    color: #7f8c8d;
    margin-bottom: 10px;
    font-style: italic;
}

.aihs-timeline-actions {
    display: flex;
    gap: 10px;
}

.aihs-saved-packages {
    display: grid;
    gap: 20px;
}

.aihs-package-card-mini {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.aihs-package-mini-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.aihs-package-mini-header h4 {
    color: #2c3e50;
    font-size: 1rem;
    margin: 0;
}

.aihs-package-mini-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.aihs-package-mini-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.aihs-original-price-mini {
    text-decoration: line-through;
    color: #6c757d;
    font-size: 0.9rem;
}

.aihs-final-price-mini {
    font-weight: 700;
    color: #27ae60;
    font-size: 1.1rem;
}

.aihs-package-mini-actions {
    display: flex;
    gap: 10px;
}

.aihs-sidebar-section {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.aihs-sidebar-section h3 {
    color: #2c3e50;
    font-size: 1.1rem;
    margin-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 10px;
}

.aihs-profile-card {
    display: flex;
    align-items: center;
    gap: 15px;
}

.aihs-profile-avatar img {
    border-radius: 50%;
    border: 3px solid #e9ecef;
}

.aihs-profile-info h4 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.aihs-profile-info p {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.aihs-quick-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.aihs-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.aihs-stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.aihs-stat-value {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9rem;
}

.aihs-health-tips {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.aihs-tip-item {
    display: flex;
    gap: 12px;
}

.aihs-tip-icon {
    width: 30px;
    height: 30px;
    background: #e8f5e8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #27ae60;
    flex-shrink: 0;
}

.aihs-tip-content h5 {
    color: #2c3e50;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.aihs-tip-content p {
    color: #7f8c8d;
    font-size: 0.8rem;
    line-height: 1.4;
    margin: 0;
}

.aihs-empty-state {
    text-align: center;
    padding: 40px 20px;
}

.aihs-empty-icon {
    font-size: 3rem;
    color: #bdc3c7;
    margin-bottom: 20px;
}

.aihs-empty-state h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.aihs-empty-state p {
    color: #7f8c8d;
    margin-bottom: 20px;
}

.aihs-btn-small {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.aihs-discount-badge {
    background: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Modal Styles */
.aihs-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
}

.aihs-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.aihs-modal-content {
    position: relative;
    background: white;
    max-width: 700px;
    margin: 50px auto;
    border-radius: 15px;
    overflow: hidden;
    max-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.aihs-modal-header {
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aihs-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.aihs-modal-body {
    padding: 20px;
    overflow-y: auto;
}

.aihs-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 40px;
}

.aihs-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .aihs-dashboard-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .aihs-health-overview {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .aihs-score-display {
        justify-content: center;
    }

    .aihs-health-metrics {
        flex-direction: column;
        align-items: stretch;
    }

    .aihs-dashboard-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .aihs-timeline-item {
        padding-left: 40px;
    }

    .aihs-timeline-marker {
        width: 40px;
        height: 40px;
        font-size: 0.8rem;
    }

    .aihs-timeline-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .aihs-package-mini-actions {
        flex-direction: column;
    }

    .aihs-profile-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // View AI analysis
    $('.aihs-view-analysis').on('click', function() {
        const responseId = $(this).data('response-id');

        $('#aihs-analysis-modal').show();
        $('#aihs-analysis-loading').show();
        $('#aihs-analysis-content').hide();

        $.ajax({
            url: aihs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aihs_get_analysis',
                response_id: responseId,
                nonce: aihs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#aihs-analysis-content').html(response.data.analysis);
                    $('#aihs-analysis-loading').hide();
                    $('#aihs-analysis-content').show();
                } else {
                    alert(response.data.message);
                    $('#aihs-analysis-modal').hide();
                }
            },
            error: function() {
                alert('<?php _e("Došlo je do greške. Molimo pokušajte ponovo.", "ai-health-savetnik"); ?>');
                $('#aihs-analysis-modal').hide();
            }
        });
    });

    // Add package to cart from dashboard
    $('.aihs-add-package-to-cart').on('click', function() {
        const $button = $(this);
        const packageId = $button.data('package-id');
        const nonce = $button.data('nonce');

        $button.prop('disabled', true).text('<?php _e("Dodajem...", "ai-health-savetnik"); ?>');

        $.ajax({
            url: aihs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aihs_add_package_to_cart',
                package_id: packageId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('<?php _e("Dodato!", "ai-health-savetnik"); ?>');

                    // Redirect to cart after delay
                    setTimeout(function() {
                        window.location.href = response.data.cart_url;
                    }, 1000);
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('<?php _e("Dodaj u korpu", "ai-health-savetnik"); ?>');
                }
            },
            error: function() {
                alert('<?php _e("Došlo je do greške. Molimo pokušajte ponovo.", "ai-health-savetnik"); ?>');
                $button.prop('disabled', false).text('<?php _e("Dodaj u korpu", "ai-health-savetnik"); ?>');
            }
        });
    });

    // Remove saved package
    $('.aihs-remove-saved-package').on('click', function() {
        const $button = $(this);
        const packageId = $button.data('package-id');
        const nonce = $button.data('nonce');

        if (!confirm('<?php _e("Da li ste sigurni da želite da uklonite ovaj paket?", "ai-health-savetnik"); ?>')) {
            return;
        }

        $.ajax({
            url: aihs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aihs_remove_saved_package',
                package_id: packageId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('.aihs-package-card-mini').fadeOut(300, function() {
                        $(this).remove();

                        // Check if no packages left
                        if ($('.aihs-package-card-mini').length === 0) {
                            $('.aihs-saved-packages').replaceWith(
                                '<div class="aihs-empty-state">' +
                                '<div class="aihs-empty-icon"><i class="aihs-icon-package"></i></div>' +
                                '<h3><?php _e("Nema sačuvanih paketa", "ai-health-savetnik"); ?></h3>' +
                                '<p><?php _e("Sačuvajte pakete koji vas zanimaju za lakše pronalaženje kasnije.", "ai-health-savetnik"); ?></p>' +
                                '<a href="<?php echo home_url("/zdravstveni-paketi"); ?>" class="aihs-btn-secondary"><?php _e("Istraži pakete", "ai-health-savetnik"); ?></a>' +
                                '</div>'
                            );
                        }
                    });
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e("Došlo je do greške. Molimo pokušajte ponovo.", "ai-health-savetnik"); ?>');
            }
        });
    });

    // Close modal
    $('.aihs-modal-close, .aihs-modal-overlay').on('click', function() {
        $('.aihs-modal').hide();
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            $('.aihs-modal').hide();
        }
    });
});
</script>