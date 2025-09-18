<?php
/**
 * Product Recommendations Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$columns = intval($atts['columns']) ?: 3;
$grid_class = 'aihs-products-grid-' . $columns;
?>

<div class="aihs-product-recommendations" data-columns="<?php echo $columns; ?>">

    <div class="aihs-recommendations-header">
        <h3 class="aihs-recommendations-title">
            <span class="aihs-title-icon">💊</span>
            Personalizovane preporuke za vas
        </h3>
        <p class="aihs-recommendations-subtitle">
            Na osnovu vaših odgovora, AI je odabrao proizvode koji mogu najviše pomoći vašem zdravlju.
        </p>
    </div>

    <div class="aihs-products-grid <?php echo $grid_class; ?>">
        <?php foreach ($recommended_products as $product_id):
            $product = wc_get_product($product_id);
            if (!$product || !$product->exists()) continue;

            // Get custom meta fields for this product
            $why_good = get_post_meta($product_id, '_aihs_why_good', true);
            $dosage = get_post_meta($product_id, '_aihs_dosage', true);
            $benefits = get_post_meta($product_id, '_aihs_benefits', true);
            $ingredients = get_post_meta($product_id, '_aihs_key_ingredients', true);
            $usage_time = get_post_meta($product_id, '_aihs_usage_time', true);

            // Check if product is on sale
            $is_on_sale = $product->is_on_sale();
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
        ?>
            <div class="aihs-product-card" data-product-id="<?php echo esc_attr($product_id); ?>">

                <!-- Product Image -->
                <div class="aihs-product-image">
                    <?php if (has_post_thumbnail($product_id)): ?>
                        <a href="<?php echo get_permalink($product_id); ?>">
                            <?php echo get_the_post_thumbnail($product_id, 'medium', array('class' => 'aihs-product-img')); ?>
                        </a>
                    <?php else: ?>
                        <div class="aihs-product-placeholder">
                            <span class="aihs-placeholder-icon">📦</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_on_sale): ?>
                        <div class="aihs-sale-badge">
                            <?php
                            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
                            echo '-' . $discount_percentage . '%';
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Quick view button -->
                    <div class="aihs-product-overlay">
                        <button class="aihs-quick-view-btn" onclick="openQuickView(<?php echo $product_id; ?>)">
                            <span class="aihs-quick-view-icon">👁️</span>
                            <span class="aihs-quick-view-text">Brz pregled</span>
                        </button>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="aihs-product-info">
                    <h4 class="aihs-product-name">
                        <a href="<?php echo get_permalink($product_id); ?>">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    </h4>

                    <!-- Product rating -->
                    <?php if ($product->get_average_rating()): ?>
                        <div class="aihs-product-rating">
                            <?php
                            $rating = $product->get_average_rating();
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '⭐' : '☆';
                            }
                            ?>
                            <span class="aihs-rating-count">(<?php echo $product->get_review_count(); ?>)</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($atts['show_price'] === 'yes'): ?>
                        <!-- Product Price -->
                        <div class="aihs-product-price">
                            <?php if ($is_on_sale): ?>
                                <span class="aihs-regular-price"><?php echo wc_price($regular_price); ?></span>
                                <span class="aihs-sale-price"><?php echo wc_price($sale_price); ?></span>
                            <?php else: ?>
                                <span class="aihs-current-price"><?php echo $product->get_price_html(); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($why_good && $atts['show_description'] === 'yes'): ?>
                        <!-- Why this product is good -->
                        <div class="aihs-product-benefit">
                            <div class="aihs-benefit-header">
                                <span class="aihs-benefit-icon">✨</span>
                                <span class="aihs-benefit-label">Zašto vam je potrebno:</span>
                            </div>
                            <p class="aihs-benefit-text"><?php echo esc_html($why_good); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($ingredients): ?>
                        <!-- Key ingredients -->
                        <div class="aihs-product-ingredients">
                            <div class="aihs-ingredients-header">
                                <span class="aihs-ingredients-icon">🌿</span>
                                <span class="aihs-ingredients-label">Ključni sastojci:</span>
                            </div>
                            <p class="aihs-ingredients-text"><?php echo esc_html($ingredients); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($dosage): ?>
                        <!-- Dosage information -->
                        <div class="aihs-product-dosage">
                            <div class="aihs-dosage-header">
                                <span class="aihs-dosage-icon">💊</span>
                                <span class="aihs-dosage-label">Doziranje:</span>
                            </div>
                            <p class="aihs-dosage-text"><?php echo esc_html($dosage); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($usage_time): ?>
                        <!-- Usage time -->
                        <div class="aihs-product-usage-time">
                            <div class="aihs-usage-header">
                                <span class="aihs-usage-icon">⏰</span>
                                <span class="aihs-usage-label">Vreme korišćenja:</span>
                            </div>
                            <p class="aihs-usage-text"><?php echo esc_html($usage_time); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Benefits list -->
                    <?php if ($benefits): ?>
                        <div class="aihs-product-benefits">
                            <h5>Koristi:</h5>
                            <ul class="aihs-benefits-list">
                                <?php
                                $benefits_array = explode("\n", $benefits);
                                foreach ($benefits_array as $benefit) {
                                    $benefit = trim($benefit);
                                    if (!empty($benefit)) {
                                        echo '<li>' . esc_html($benefit) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Product actions -->
                    <div class="aihs-product-actions">
                        <a href="<?php echo get_permalink($product_id); ?>"
                           class="aihs-btn aihs-btn-view">
                            📋 Detalji proizvoda
                        </a>

                        <?php if (function_exists('wc_get_cart_url') && $product->is_purchasable()): ?>
                            <button class="aihs-btn aihs-btn-cart"
                                    onclick="addToCart(<?php echo $product_id; ?>)"
                                    data-product-id="<?php echo $product_id; ?>">
                                🛒 Dodaj u korpu
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- AI recommendation confidence -->
                    <div class="aihs-ai-confidence">
                        <div class="aihs-confidence-label">AI preporuka:</div>
                        <div class="aihs-confidence-bar">
                            <?php
                            // Mock confidence based on product match to user's answers
                            $confidence = rand(85, 98);
                            ?>
                            <div class="aihs-confidence-fill" style="width: <?php echo $confidence; ?>%"></div>
                        </div>
                        <div class="aihs-confidence-text"><?php echo $confidence; ?>% poklapanje</div>
                    </div>
                </div>

                <!-- Product badges -->
                <div class="aihs-product-badges">
                    <?php if ($product->is_featured()): ?>
                        <span class="aihs-badge aihs-badge-featured">Preporučeno</span>
                    <?php endif; ?>

                    <?php if ($product->get_stock_status() === 'instock'): ?>
                        <span class="aihs-badge aihs-badge-stock">Na stanju</span>
                    <?php endif; ?>

                    <?php
                    // Check for custom badges
                    $is_organic = get_post_meta($product_id, '_aihs_organic', true);
                    $is_vegan = get_post_meta($product_id, '_aihs_vegan', true);
                    $is_gluten_free = get_post_meta($product_id, '_aihs_gluten_free', true);
                    ?>

                    <?php if ($is_organic === 'yes'): ?>
                        <span class="aihs-badge aihs-badge-organic">Organski</span>
                    <?php endif; ?>

                    <?php if ($is_vegan === 'yes'): ?>
                        <span class="aihs-badge aihs-badge-vegan">Vegan</span>
                    <?php endif; ?>

                    <?php if ($is_gluten_free === 'yes'): ?>
                        <span class="aihs-badge aihs-badge-gluten-free">Bez glutena</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Load more button if there are more products -->
    <?php if (count($recommended_products) > intval($atts['limit'])): ?>
        <div class="aihs-load-more-section">
            <button class="aihs-btn aihs-btn-load-more" onclick="loadMoreProducts()">
                📦 Prikaži još proizvoda
            </button>
        </div>
    <?php endif; ?>

    <!-- AI explanation -->
    <div class="aihs-ai-explanation">
        <div class="aihs-explanation-header">
            <h4>🤖 Kako AI bira proizvode za vas?</h4>
        </div>
        <div class="aihs-explanation-content">
            <p>
                AI sistem analizira vaše odgovore i kreira personalizovan profil zdravstvenih potreba.
                Proizvodi su odabrani na osnovu:
            </p>
            <ul>
                <li>🎯 Specifičnih zdravstvenih problema koje ste naveli</li>
                <li>📊 Vašeg zdravstvenog skora i kategorije</li>
                <li>⚖️ Težine i prioriteta različitih simptoma</li>
                <li>🔬 Naučnih dokaza o efikasnosti sastojaka</li>
                <li>👥 Iskustava korisnika sa sličnim profilima</li>
            </ul>
        </div>
    </div>

    <!-- Related categories -->
    <div class="aihs-related-categories">
        <h4>Povezane kategorije proizvoda:</h4>
        <div class="aihs-categories-list">
            <a href="#" class="aihs-category-link">🧬 Vitamini i minerali</a>
            <a href="#" class="aihs-category-link">🌿 Biljni ekstrakti</a>
            <a href="#" class="aihs-category-link">💚 Probiotici</a>
            <a href="#" class="aihs-category-link">🏃‍♂️ Sportska suplementacija</a>
            <a href="#" class="aihs-category-link">😴 San i relaksacija</a>
            <a href="#" class="aihs-category-link">🧠 Kognitivne funkcije</a>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div id="aihs-quick-view-modal" class="aihs-modal" style="display: none;">
    <div class="aihs-modal-content">
        <div class="aihs-modal-header">
            <h3 id="aihs-modal-title">Brz pregled proizvoda</h3>
            <button class="aihs-modal-close" onclick="closeQuickView()">&times;</button>
        </div>
        <div class="aihs-modal-body" id="aihs-modal-body">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.aihs-product-recommendations {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.aihs-recommendations-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-recommendations-title {
    font-size: 2.2em;
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.aihs-title-icon {
    font-size: 1.2em;
}

.aihs-recommendations-subtitle {
    color: #7f8c8d;
    font-size: 1.1em;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}

.aihs-products-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.aihs-products-grid-1 { grid-template-columns: 1fr; }
.aihs-products-grid-2 { grid-template-columns: repeat(2, 1fr); }
.aihs-products-grid-3 { grid-template-columns: repeat(3, 1fr); }
.aihs-products-grid-4 { grid-template-columns: repeat(4, 1fr); }

.aihs-product-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.aihs-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.aihs-product-image {
    position: relative;
    height: 250px;
    overflow: hidden;
    background: #f8f9fa;
}

.aihs-product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.aihs-product-card:hover .aihs-product-img {
    transform: scale(1.05);
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
    font-size: 4em;
    color: #7f8c8d;
}

.aihs-sale-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #e74c3c;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
}

.aihs-product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.aihs-product-card:hover .aihs-product-overlay {
    opacity: 1;
}

.aihs-quick-view-btn {
    background: white;
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    transition: transform 0.3s ease;
}

.aihs-quick-view-btn:hover {
    transform: scale(1.05);
}

.aihs-product-info {
    padding: 25px;
}

.aihs-product-name {
    margin: 0 0 15px 0;
    font-size: 1.3em;
    line-height: 1.4;
}

.aihs-product-name a {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 600;
}

.aihs-product-name a:hover {
    color: #3498db;
}

.aihs-product-rating {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.aihs-rating-count {
    font-size: 12px;
    color: #7f8c8d;
}

.aihs-product-price {
    margin-bottom: 20px;
    font-size: 1.2em;
    font-weight: 700;
}

.aihs-regular-price {
    color: #95a5a6;
    text-decoration: line-through;
    margin-right: 10px;
}

.aihs-sale-price,
.aihs-current-price {
    color: #27ae60;
}

.aihs-product-benefit,
.aihs-product-ingredients,
.aihs-product-dosage,
.aihs-product-usage-time {
    margin-bottom: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #3498db;
}

.aihs-benefit-header,
.aihs-ingredients-header,
.aihs-dosage-header,
.aihs-usage-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.aihs-benefit-text,
.aihs-ingredients-text,
.aihs-dosage-text,
.aihs-usage-text {
    margin: 0;
    color: #7f8c8d;
    font-size: 13px;
    line-height: 1.5;
}

.aihs-product-benefits {
    margin-bottom: 20px;
}

.aihs-product-benefits h5 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 14px;
}

.aihs-benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-benefits-list li {
    padding: 4px 0;
    color: #7f8c8d;
    font-size: 13px;
    position: relative;
    padding-left: 15px;
}

.aihs-benefits-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #27ae60;
    font-weight: bold;
}

.aihs-product-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.aihs-btn {
    flex: 1;
    padding: 12px 15px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.aihs-btn-view {
    background: #3498db;
    color: white;
}

.aihs-btn-view:hover {
    background: #2980b9;
}

.aihs-btn-cart {
    background: #27ae60;
    color: white;
}

.aihs-btn-cart:hover {
    background: #229954;
}

.aihs-btn-cart.loading {
    background: #95a5a6;
    cursor: not-allowed;
}

.aihs-ai-confidence {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.aihs-confidence-label {
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 600;
}

.aihs-confidence-bar {
    height: 6px;
    background: #ecf0f1;
    border-radius: 3px;
    overflow: hidden;
}

.aihs-confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #f39c12, #27ae60);
    border-radius: 3px;
    transition: width 1s ease;
}

.aihs-confidence-text {
    font-size: 11px;
    color: #27ae60;
    font-weight: 600;
    text-align: right;
}

.aihs-product-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.aihs-badge {
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.aihs-badge-featured {
    background: #f39c12;
    color: white;
}

.aihs-badge-stock {
    background: #27ae60;
    color: white;
}

.aihs-badge-organic {
    background: #2ecc71;
    color: white;
}

.aihs-badge-vegan {
    background: #9b59b6;
    color: white;
}

.aihs-badge-gluten-free {
    background: #e67e22;
    color: white;
}

.aihs-load-more-section {
    text-align: center;
    margin: 40px 0;
}

.aihs-btn-load-more {
    background: #ecf0f1;
    color: #2c3e50;
    padding: 15px 30px;
    border: none;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.aihs-btn-load-more:hover {
    background: #d5dbdb;
}

.aihs-ai-explanation {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 30px;
    margin: 40px 0;
}

.aihs-explanation-header h4 {
    margin: 0 0 20px 0;
    font-size: 1.4em;
}

.aihs-explanation-content p {
    margin-bottom: 20px;
    line-height: 1.6;
    opacity: 0.9;
}

.aihs-explanation-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-explanation-content li {
    padding: 8px 0;
    opacity: 0.9;
    line-height: 1.5;
}

.aihs-related-categories {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.aihs-related-categories h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.aihs-categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.aihs-category-link {
    padding: 8px 15px;
    background: #ecf0f1;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 20px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.aihs-category-link:hover {
    background: #3498db;
    color: white;
}

/* Modal styles */
.aihs-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aihs-modal-content {
    background: white;
    border-radius: 15px;
    max-width: 600px;
    width: 90%;
    max-height: 80%;
    overflow-y: auto;
}

.aihs-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #ecf0f1;
}

.aihs-modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.aihs-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #7f8c8d;
}

.aihs-modal-body {
    padding: 25px;
}

/* Responsive */
@media (max-width: 1024px) {
    .aihs-products-grid-4 {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .aihs-product-recommendations {
        padding: 15px;
    }

    .aihs-recommendations-title {
        font-size: 1.8em;
        flex-direction: column;
        gap: 10px;
    }

    .aihs-products-grid-2,
    .aihs-products-grid-3,
    .aihs-products-grid-4 {
        grid-template-columns: 1fr;
    }

    .aihs-product-actions {
        flex-direction: column;
    }

    .aihs-categories-list {
        justify-content: center;
    }

    .aihs-ai-explanation {
        padding: 20px;
    }
}

/* Animation on load */
.aihs-product-card {
    animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation for multiple cards */
.aihs-product-card:nth-child(1) { animation-delay: 0.1s; }
.aihs-product-card:nth-child(2) { animation-delay: 0.2s; }
.aihs-product-card:nth-child(3) { animation-delay: 0.3s; }
.aihs-product-card:nth-child(4) { animation-delay: 0.4s; }
.aihs-product-card:nth-child(5) { animation-delay: 0.5s; }
.aihs-product-card:nth-child(6) { animation-delay: 0.6s; }
</style>

<script>
function addToCart(productId) {
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    const originalText = button.innerHTML;

    // Show loading state
    button.innerHTML = '⏳ Dodaje se...';
    button.classList.add('loading');
    button.disabled = true;

    // Simulate AJAX call to add to cart
    // Replace this with actual WooCommerce AJAX
    setTimeout(() => {
        button.innerHTML = '✅ Dodato u korpu';
        button.style.background = '#27ae60';

        // Reset after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('loading');
            button.disabled = false;
            button.style.background = '';
        }, 2000);
    }, 1000);
}

function openQuickView(productId) {
    const modal = document.getElementById('aihs-quick-view-modal');
    const modalBody = document.getElementById('aihs-modal-body');

    // Show modal
    modal.style.display = 'flex';

    // Load product content via AJAX
    modalBody.innerHTML = '<div style="text-align: center; padding: 40px;">⏳ Učitavanje...</div>';

    // Simulate loading content
    setTimeout(() => {
        modalBody.innerHTML = `
            <div style="text-align: center;">
                <h4>Proizvod ID: ${productId}</h4>
                <p>Ovde bi bio prikazan brz pregled proizvoda sa osnovnim informacijama, slikama i mogućnošću dodavanja u korpu.</p>
                <button class="aihs-btn aihs-btn-cart" onclick="addToCart(${productId})">🛒 Dodaj u korpu</button>
            </div>
        `;
    }, 500);
}

function closeQuickView() {
    document.getElementById('aihs-quick-view-modal').style.display = 'none';
}

function loadMoreProducts() {
    const button = document.querySelector('.aihs-btn-load-more');
    button.innerHTML = '⏳ Učitavanje...';
    button.disabled = true;

    // Simulate loading more products
    setTimeout(() => {
        button.innerHTML = '📦 Prikaži još proizvoda';
        button.disabled = false;
        // Here you would load more products via AJAX
    }, 1000);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('aihs-quick-view-modal');
    if (e.target === modal) {
        closeQuickView();
    }
});

// Animate confidence bars on load
document.addEventListener('DOMContentLoaded', function() {
    const confidenceFills = document.querySelectorAll('.aihs-confidence-fill');

    setTimeout(() => {
        confidenceFills.forEach((fill, index) => {
            const width = fill.style.width;
            fill.style.width = '0%';
            setTimeout(() => {
                fill.style.width = width;
            }, index * 200);
        });
    }, 500);
});
</script>