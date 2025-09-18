<?php
/**
 * Quiz Results Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$score_category = aihs_get_score_category($response->calculated_score);
$recommended_products = json_decode($response->recommended_products, true) ?: array();
$packages = AIHS_Database::get_response_packages($response->id);
$public_id = aihs_generate_public_id($response->id);
?>

<div class="aihs-results-container" data-show-details="<?php echo esc_attr($atts['show_details']); ?>" data-show-recommendations="<?php echo esc_attr($atts['show_recommendations']); ?>">

    <!-- Results Header -->
    <div class="aihs-results-header">
        <div class="aihs-completion-badge">
            <span class="aihs-badge-icon">✓</span>
            <span class="aihs-badge-text">Analiza završena</span>
        </div>

        <h2 class="aihs-results-title">Vaša AI Analiza Zdravlja</h2>
        <p class="aihs-results-subtitle">
            Rezultati su generisani <?php echo date_i18n('j. F Y.', strtotime($response->created_at)); ?> u <?php echo date_i18n('H:i', strtotime($response->created_at)); ?>h
        </p>
    </div>

    <!-- Health Score Section -->
    <div class="aihs-score-section">
        <div class="aihs-score-card">
            <div class="aihs-score-gauge">
                <svg class="aihs-gauge-svg" viewBox="0 0 200 200">
                    <circle cx="100" cy="100" r="80" fill="none" stroke="#ecf0f1" stroke-width="20"/>
                    <circle cx="100" cy="100" r="80" fill="none"
                            stroke="<?php echo esc_attr($score_category['data']['color'] ?? '#3498db'); ?>"
                            stroke-width="20"
                            stroke-dasharray="<?php echo ($response->calculated_score / 100) * 502.65; ?> 502.65"
                            stroke-dashoffset="125.66"
                            transform="rotate(-90 100 100)"
                            stroke-linecap="round"/>
                </svg>
                <div class="aihs-score-center">
                    <div class="aihs-score-number"><?php echo intval($response->calculated_score); ?></div>
                    <div class="aihs-score-max">/100</div>
                </div>
            </div>

            <div class="aihs-score-info">
                <h3 class="aihs-score-category" style="color: <?php echo esc_attr($score_category['data']['color'] ?? '#3498db'); ?>">
                    <?php echo esc_html($score_category['data']['label'] ?? 'Umereno'); ?>
                </h3>
                <p class="aihs-score-description">
                    <?php echo esc_html($score_category['data']['description'] ?? 'Vaš zdravstveni skor pokazuje prosečno stanje.'); ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($atts['show_details'] === 'yes' && !empty($response->ai_analysis)): ?>
        <!-- AI Analysis Section -->
        <div class="aihs-analysis-section">
            <h3 class="aihs-section-title">
                <span class="aihs-section-icon">🧠</span>
                AI Analiza Vašeg Zdravlja
            </h3>

            <div class="aihs-analysis-content">
                <?php echo wp_kses_post(wpautop($response->ai_analysis)); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($atts['show_recommendations'] === 'yes' && !empty($recommended_products)): ?>
        <!-- Product Recommendations Section -->
        <div class="aihs-recommendations-section">
            <h3 class="aihs-section-title">
                <span class="aihs-section-icon">💊</span>
                Preporučeni Proizvodi
            </h3>

            <div class="aihs-products-grid">
                <?php
                $product_limit = intval($atts['limit'] ?? 6);
                $displayed_products = array_slice($recommended_products, 0, $product_limit);

                foreach ($displayed_products as $product_id):
                    $product = wc_get_product($product_id);
                    if (!$product || !$product->exists()) continue;

                    $why_good = get_post_meta($product_id, '_aihs_why_good', true);
                    $dosage = get_post_meta($product_id, '_aihs_dosage', true);
                ?>
                    <div class="aihs-product-card">
                        <div class="aihs-product-image">
                            <?php if (has_post_thumbnail($product_id)): ?>
                                <?php echo get_the_post_thumbnail($product_id, 'medium', array('class' => 'aihs-product-img')); ?>
                            <?php else: ?>
                                <div class="aihs-product-placeholder">
                                    <span class="aihs-placeholder-icon">📦</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="aihs-product-info">
                            <h4 class="aihs-product-name"><?php echo esc_html($product->get_name()); ?></h4>

                            <?php if ($atts['show_price'] === 'yes'): ?>
                                <div class="aihs-product-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($why_good && $atts['show_description'] === 'yes'): ?>
                                <p class="aihs-product-benefit"><?php echo esc_html($why_good); ?></p>
                            <?php endif; ?>

                            <?php if ($dosage): ?>
                                <div class="aihs-product-dosage">
                                    <strong>Doziranje:</strong> <?php echo esc_html($dosage); ?>
                                </div>
                            <?php endif; ?>

                            <div class="aihs-product-actions">
                                <a href="<?php echo get_permalink($product_id); ?>"
                                   class="aihs-btn aihs-btn-product">
                                    Pogledaj proizvod
                                </a>

                                <?php if (function_exists('wc_get_cart_url')): ?>
                                    <a href="<?php echo esc_url(wc_get_cart_url() . '?add-to-cart=' . $product_id); ?>"
                                       class="aihs-btn aihs-btn-cart"
                                       data-product-id="<?php echo esc_attr($product_id); ?>">
                                        Dodaj u korpu
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($atts['show_products'] === 'yes' && !empty($packages)): ?>
        <!-- Health Packages Section -->
        <div class="aihs-packages-section">
            <h3 class="aihs-section-title">
                <span class="aihs-section-icon">📦</span>
                Personalizovani Paketi za Vas
            </h3>

            <p class="aihs-packages-description">
                Na osnovu vaših odgovora, kreirali smo posebne pakete proizvoda sa popustima.
            </p>

            <div class="aihs-packages-grid">
                <?php foreach ($packages as $package):
                    $package_type_info = aihs_get_package_types()[$package->package_type] ?? null;
                    $savings = $package->original_price - $package->final_price;
                ?>
                    <div class="aihs-package-card" data-package-type="<?php echo esc_attr($package->package_type); ?>">
                        <div class="aihs-package-header">
                            <h4 class="aihs-package-name"><?php echo esc_html($package->package_name); ?></h4>
                            <?php if ($package_type_info): ?>
                                <span class="aihs-package-type"><?php echo esc_html($package_type_info['label']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="aihs-package-pricing">
                            <?php if ($savings > 0): ?>
                                <div class="aihs-package-savings">
                                    <span class="aihs-savings-badge">-<?php echo number_format($package->discount_percentage, 0); ?>%</span>
                                    <span class="aihs-savings-text">Ušteda: <?php echo aihs_format_price($savings); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="aihs-package-prices">
                                <?php if ($savings > 0): ?>
                                    <span class="aihs-original-price"><?php echo aihs_format_price($package->original_price); ?></span>
                                <?php endif; ?>
                                <span class="aihs-final-price"><?php echo aihs_format_price($package->final_price); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($package->description)): ?>
                            <p class="aihs-package-description"><?php echo esc_html($package->description); ?></p>
                        <?php endif; ?>

                        <div class="aihs-package-products">
                            <?php
                            $product_ids = json_decode($package->product_ids, true) ?: array();
                            foreach ($product_ids as $index => $product_id):
                                if ($index >= 3) break; // Show max 3 products in preview
                                $product = wc_get_product($product_id);
                                if ($product && $product->exists()):
                            ?>
                                <div class="aihs-package-product">
                                    <span class="aihs-product-bullet">•</span>
                                    <span class="aihs-product-title"><?php echo esc_html($product->get_name()); ?></span>
                                </div>
                            <?php
                                endif;
                            endforeach;

                            if (count($product_ids) > 3):
                            ?>
                                <div class="aihs-package-product">
                                    <span class="aihs-product-bullet">+</span>
                                    <span class="aihs-product-title"><?php echo count($product_ids) - 3; ?> dodatnih proizvoda</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="aihs-package-actions">
                            <?php if ($package->wc_product_id): ?>
                                <a href="<?php echo get_permalink($package->wc_product_id); ?>"
                                   class="aihs-btn aihs-btn-package-primary">
                                    Naruči paket
                                </a>
                                <a href="<?php echo get_permalink($package->wc_product_id); ?>"
                                   class="aihs-btn aihs-btn-package-secondary">
                                    Detalji paketa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Personal Recommendations -->
    <div class="aihs-lifestyle-section">
        <h3 class="aihs-section-title">
            <span class="aihs-section-icon">🌱</span>
            Preporuke za Poboljšanje
        </h3>

        <div class="aihs-lifestyle-tips">
            <div class="aihs-tip-card">
                <div class="aihs-tip-icon">💧</div>
                <div class="aihs-tip-content">
                    <h4>Hidratacija</h4>
                    <p>Pijte najmanje 8 čaša vode dnevno za optimalno funkcionisanje organizma.</p>
                </div>
            </div>

            <div class="aihs-tip-card">
                <div class="aihs-tip-icon">🏃‍♂️</div>
                <div class="aihs-tip-content">
                    <h4>Fizička aktivnost</h4>
                    <p>30 minuta umerene vežbe dnevno značajno poboljšava zdravlje.</p>
                </div>
            </div>

            <div class="aihs-tip-card">
                <div class="aihs-tip-icon">😴</div>
                <div class="aihs-tip-content">
                    <h4>Kvalitetan san</h4>
                    <p>7-9 sati sna svake noći je ključno za regeneraciju organizma.</p>
                </div>
            </div>

            <div class="aihs-tip-card">
                <div class="aihs-tip-icon">🥗</div>
                <div class="aihs-tip-content">
                    <h4>Zdrava ishrana</h4>
                    <p>Unesajte raznovrsnu hranu bogatu vitaminima i mineralima.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Actions -->
    <div class="aihs-results-actions">
        <div class="aihs-action-card">
            <h4>Podelite rezultate</h4>
            <p>Možete podeliti vaše rezultate sa lekarom ili porodicom.</p>
            <div class="aihs-share-buttons">
                <button class="aihs-btn aihs-btn-share" onclick="copyResultsLink()">
                    📋 Kopiraj link
                </button>
                <button class="aihs-btn aihs-btn-share" onclick="downloadResults()">
                    📥 Preuzmi PDF
                </button>
                <button class="aihs-btn aihs-btn-share" onclick="emailResults()">
                    📧 Pošalji email
                </button>
            </div>
        </div>

        <div class="aihs-action-card">
            <h4>Sledeći koraci</h4>
            <p>Preporučujemo konsultaciju sa zdravstvenim stručnjakom.</p>
            <div class="aihs-next-steps">
                <a href="#" class="aihs-btn aihs-btn-next-step">
                    🩺 Zakažite pregled
                </a>
                <a href="#" class="aihs-btn aihs-btn-next-step">
                    📞 Kontaktirajte nas
                </a>
            </div>
        </div>

        <?php if (is_user_logged_in()): ?>
            <div class="aihs-action-card">
                <h4>Vaš profil</h4>
                <p>Pratite napredak vašeg zdravlja kroz vreme.</p>
                <a href="#" class="aihs-btn aihs-btn-profile">
                    👤 Idite na dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="aihs-results-footer">
        <div class="aihs-disclaimer">
            <h4>⚠️ Važna napomena</h4>
            <p>
                Ova analiza je generisana AI sistemom i služi samo za informativne svrhe.
                Ne zamenjuje profesionalnu medicinsku dijagnozu ili savet. Za sve zdravstvene probleme
                konsultujte se sa kvalifikovanim zdravstvenim radnikom.
            </p>
        </div>

        <div class="aihs-retake">
            <p>Želite da ponovite test?</p>
            <a href="<?php echo esc_url(remove_query_arg('aihs_public_id')); ?>" class="aihs-btn aihs-btn-retake">
                🔄 Nova analiza
            </a>
        </div>
    </div>
</div>

<style>
.aihs-results-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

.aihs-results-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-completion-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #d4edda;
    color: #155724;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
}

.aihs-badge-icon {
    background: #28a745;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.aihs-results-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 300;
}

.aihs-results-subtitle {
    color: #7f8c8d;
    font-size: 1.1em;
}

.aihs-score-section {
    margin-bottom: 40px;
}

.aihs-score-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40px;
    text-align: center;
}

.aihs-score-gauge {
    position: relative;
    width: 200px;
    height: 200px;
}

.aihs-gauge-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.aihs-score-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.aihs-score-number {
    font-size: 3em;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.aihs-score-max {
    font-size: 1.2em;
    color: #7f8c8d;
    margin-top: -5px;
}

.aihs-score-info {
    flex: 1;
    text-align: left;
}

.aihs-score-category {
    font-size: 2em;
    margin-bottom: 15px;
    font-weight: 600;
}

.aihs-score-description {
    font-size: 1.1em;
    color: #7f8c8d;
    margin: 0;
}

.aihs-section-title {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 1.8em;
    color: #2c3e50;
    margin-bottom: 25px;
    font-weight: 600;
}

.aihs-section-icon {
    font-size: 1.2em;
}

.aihs-analysis-section,
.aihs-recommendations-section,
.aihs-packages-section,
.aihs-lifestyle-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.aihs-analysis-content {
    color: #2c3e50;
    font-size: 1.1em;
    line-height: 1.8;
}

.aihs-analysis-content h4 {
    color: #3498db;
    margin: 25px 0 15px 0;
}

.aihs-analysis-content ul,
.aihs-analysis-content ol {
    margin: 15px 0;
    padding-left: 25px;
}

.aihs-analysis-content li {
    margin-bottom: 8px;
}

.aihs-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.aihs-product-card {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.aihs-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.aihs-product-image {
    height: 200px;
    overflow: hidden;
    background: #ecf0f1;
    position: relative;
}

.aihs-product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.aihs-product-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
}

.aihs-placeholder-icon {
    font-size: 3em;
    color: #7f8c8d;
}

.aihs-product-info {
    padding: 20px;
}

.aihs-product-name {
    font-size: 1.2em;
    color: #2c3e50;
    margin-bottom: 10px;
    font-weight: 600;
}

.aihs-product-price {
    font-size: 1.3em;
    font-weight: 700;
    color: #e74c3c;
    margin-bottom: 15px;
}

.aihs-product-benefit {
    color: #7f8c8d;
    font-size: 0.95em;
    margin-bottom: 15px;
    line-height: 1.5;
}

.aihs-product-dosage {
    background: #e8f4fd;
    padding: 10px;
    border-radius: 6px;
    font-size: 0.9em;
    color: #2c3e50;
    margin-bottom: 20px;
}

.aihs-product-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.aihs-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.aihs-btn-product {
    background: #3498db;
    color: white;
    flex: 1;
}

.aihs-btn-product:hover {
    background: #2980b9;
}

.aihs-btn-cart {
    background: #27ae60;
    color: white;
    flex: 1;
}

.aihs-btn-cart:hover {
    background: #229954;
}

.aihs-packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.aihs-package-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
    position: relative;
    overflow: hidden;
}

.aihs-package-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.aihs-package-header {
    margin-bottom: 20px;
}

.aihs-package-name {
    font-size: 1.4em;
    margin-bottom: 5px;
    font-weight: 600;
}

.aihs-package-type {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    display: inline-block;
}

.aihs-package-pricing {
    margin-bottom: 20px;
}

.aihs-package-savings {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.aihs-savings-badge {
    background: #e74c3c;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 700;
}

.aihs-savings-text {
    font-size: 0.9em;
    color: rgba(255,255,255,0.9);
}

.aihs-package-prices {
    display: flex;
    align-items: center;
    gap: 15px;
}

.aihs-original-price {
    text-decoration: line-through;
    color: rgba(255,255,255,0.7);
    font-size: 1.1em;
}

.aihs-final-price {
    font-size: 1.8em;
    font-weight: 700;
}

.aihs-package-description {
    color: rgba(255,255,255,0.9);
    margin-bottom: 20px;
    line-height: 1.5;
}

.aihs-package-products {
    margin-bottom: 25px;
}

.aihs-package-product {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    color: rgba(255,255,255,0.9);
    font-size: 0.95em;
}

.aihs-product-bullet {
    color: #2ecc71;
    font-weight: bold;
}

.aihs-package-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.aihs-btn-package-primary {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    flex: 1;
}

.aihs-btn-package-primary:hover {
    background: rgba(255,255,255,0.3);
}

.aihs-btn-package-secondary {
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
    flex: 1;
}

.aihs-btn-package-secondary:hover {
    background: rgba(255,255,255,0.1);
}

.aihs-lifestyle-tips {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.aihs-tip-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #3498db;
}

.aihs-tip-icon {
    font-size: 2em;
    flex-shrink: 0;
}

.aihs-tip-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 1.1em;
}

.aihs-tip-content p {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.95em;
    line-height: 1.4;
}

.aihs-results-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.aihs-action-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    text-align: center;
}

.aihs-action-card h4 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 1.3em;
}

.aihs-action-card p {
    color: #7f8c8d;
    margin-bottom: 20px;
}

.aihs-share-buttons,
.aihs-next-steps {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.aihs-btn-share,
.aihs-btn-next-step,
.aihs-btn-profile {
    background: #ecf0f1;
    color: #2c3e50;
    font-size: 13px;
    padding: 10px 15px;
}

.aihs-btn-share:hover,
.aihs-btn-next-step:hover,
.aihs-btn-profile:hover {
    background: #d5dbdb;
}

.aihs-results-footer {
    border-top: 1px solid #ecf0f1;
    padding-top: 30px;
    margin-top: 40px;
}

.aihs-disclaimer {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.aihs-disclaimer h4 {
    color: #856404;
    margin-bottom: 10px;
    font-size: 1.1em;
}

.aihs-disclaimer p {
    color: #856404;
    margin: 0;
    font-size: 0.95em;
    line-height: 1.5;
}

.aihs-retake {
    text-align: center;
}

.aihs-retake p {
    color: #7f8c8d;
    margin-bottom: 15px;
}

.aihs-btn-retake {
    background: #3498db;
    color: white;
    padding: 15px 30px;
    font-size: 16px;
}

.aihs-btn-retake:hover {
    background: #2980b9;
}

/* Responsive */
@media (max-width: 768px) {
    .aihs-results-container {
        padding: 15px;
    }

    .aihs-results-title {
        font-size: 2em;
    }

    .aihs-score-card {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .aihs-score-info {
        text-align: center;
    }

    .aihs-products-grid,
    .aihs-packages-grid {
        grid-template-columns: 1fr;
    }

    .aihs-lifestyle-tips {
        grid-template-columns: 1fr;
    }

    .aihs-tip-card {
        flex-direction: column;
        text-align: center;
    }

    .aihs-package-actions,
    .aihs-product-actions {
        flex-direction: column;
    }

    .aihs-share-buttons,
    .aihs-next-steps {
        flex-direction: column;
    }

    .aihs-section-title {
        font-size: 1.5em;
    }
}

/* Print styles */
@media print {
    .aihs-results-actions,
    .aihs-retake,
    .aihs-share-buttons {
        display: none;
    }

    .aihs-results-container {
        box-shadow: none;
        padding: 0;
    }

    .aihs-score-card,
    .aihs-analysis-section,
    .aihs-recommendations-section,
    .aihs-packages-section {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<script>
function copyResultsLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link je kopiran u clipboard!');
    });
}

function downloadResults() {
    // Trigger PDF generation
    window.print();
}

function emailResults() {
    const subject = 'Moja AI Analiza Zdravlja';
    const body = 'Podeljem sa vama rezultate moje analize zdravlja: ' + window.location.href;
    const mailtoLink = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.location.href = mailtoLink;
}

// Add to cart functionality
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.aihs-btn-cart');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.getAttribute('data-product-id');
            const originalText = this.textContent;

            this.textContent = 'Dodaje se...';
            this.disabled = true;

            // Simulate adding to cart (replace with actual WooCommerce AJAX)
            setTimeout(() => {
                this.textContent = 'Dodato ✓';
                this.style.background = '#27ae60';

                setTimeout(() => {
                    this.textContent = originalText;
                    this.disabled = false;
                    this.style.background = '';
                }, 2000);
            }, 1000);
        });
    });
});
</script>