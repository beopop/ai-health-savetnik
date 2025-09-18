<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$response_id = isset($_GET['response_id']) ? intval($_GET['response_id']) : 0;
$packages = array();
$user_response = null;

if ($response_id) {
    $user_response = AIHS_Database::get_response($response_id);
    if ($user_response) {
        $packages = AIHS_Database::get_packages_for_response($response_id);
    }
}

if (empty($packages)) {
    $packages = AIHS_Database::get_all_packages();
}

$currency_symbol = get_woocommerce_currency_symbol();
?>

<div id="aihs-packages-container" class="aihs-container">
    <div class="aihs-packages-header">
        <h2 class="aihs-packages-title">
            <?php _e('Zdravstveni Paketi Prilagođeni Vama', 'ai-health-savetnik'); ?>
        </h2>
        <p class="aihs-packages-subtitle">
            <?php _e('Odaberite paket koji najbolje odgovara vašim zdravstvenim potrebama', 'ai-health-savetnik'); ?>
        </p>
    </div>

    <?php if ($user_response): ?>
    <div class="aihs-user-context">
        <div class="aihs-score-summary">
            <span class="aihs-score-label"><?php _e('Vaš zdravstveni skor:', 'ai-health-savetnik'); ?></span>
            <span class="aihs-score-value"><?php echo esc_html($user_response->health_score); ?>/100</span>
        </div>
        <div class="aihs-recommendations-note">
            <i class="aihs-icon-info"></i>
            <span><?php _e('Paketi su prilagođeni na osnovu vaših odgovora', 'ai-health-savetnik'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="aihs-packages-grid">
        <?php foreach ($packages as $package):
            $package_data = json_decode($package->package_data, true) ?: array();
            $products = $package_data['products'] ?? array();
            $original_price = floatval($package_data['original_price'] ?? 0);
            $discount_percentage = floatval($package_data['discount_percentage'] ?? 0);
            $final_price = $original_price * (1 - $discount_percentage / 100);
            $savings = $original_price - $final_price;
            $is_featured = ($package_data['featured'] ?? false);
            $ai_confidence = floatval($package_data['ai_confidence'] ?? 0);
        ?>
        <div class="aihs-package-card <?php echo $is_featured ? 'featured' : ''; ?>" data-package-id="<?php echo esc_attr($package->id); ?>">
            <?php if ($is_featured): ?>
            <div class="aihs-package-badge">
                <span><?php _e('Preporučeno', 'ai-health-savetnik'); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($ai_confidence > 0): ?>
            <div class="aihs-ai-confidence">
                <span class="aihs-ai-label">AI Preporuka</span>
                <div class="aihs-confidence-bar">
                    <div class="aihs-confidence-fill" style="width: <?php echo esc_attr($ai_confidence); ?>%"></div>
                </div>
                <span class="aihs-confidence-text"><?php echo round($ai_confidence); ?>% poklapanje</span>
            </div>
            <?php endif; ?>

            <div class="aihs-package-header">
                <h3 class="aihs-package-name"><?php echo esc_html($package->name); ?></h3>
                <p class="aihs-package-description"><?php echo esc_html($package->description); ?></p>
            </div>

            <div class="aihs-package-pricing">
                <?php if ($discount_percentage > 0): ?>
                <div class="aihs-original-price">
                    <span class="aihs-price-label"><?php _e('Regularna cena:', 'ai-health-savetnik'); ?></span>
                    <span class="aihs-price-value crossed"><?php echo $currency_symbol . number_format($original_price, 2); ?></span>
                </div>
                <div class="aihs-discount-info">
                    <span class="aihs-discount-badge">-<?php echo round($discount_percentage); ?>%</span>
                    <span class="aihs-savings"><?php _e('Ušteda:', 'ai-health-savetnik'); ?> <?php echo $currency_symbol . number_format($savings, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="aihs-final-price">
                    <span class="aihs-price-label"><?php _e('Vaša cena:', 'ai-health-savetnik'); ?></span>
                    <span class="aihs-price-value"><?php echo $currency_symbol . number_format($final_price, 2); ?></span>
                </div>
            </div>

            <div class="aihs-package-products">
                <h4 class="aihs-products-title"><?php _e('Uključeni proizvodi:', 'ai-health-savetnik'); ?></h4>
                <ul class="aihs-products-list">
                    <?php foreach ($products as $product_data):
                        $product_id = $product_data['id'] ?? 0;
                        $product = wc_get_product($product_id);
                        if (!$product) continue;

                        $quantity = intval($product_data['quantity'] ?? 1);
                        $individual_price = floatval($product_data['price'] ?? $product->get_price());
                        $total_price = $individual_price * $quantity;
                    ?>
                    <li class="aihs-product-item">
                        <div class="aihs-product-image">
                            <?php echo $product->get_image('thumbnail'); ?>
                        </div>
                        <div class="aihs-product-details">
                            <span class="aihs-product-name"><?php echo esc_html($product->get_name()); ?></span>
                            <span class="aihs-product-quantity">x<?php echo $quantity; ?></span>
                            <span class="aihs-product-price"><?php echo $currency_symbol . number_format($total_price, 2); ?></span>
                        </div>
                        <div class="aihs-product-actions">
                            <button class="aihs-btn-secondary aihs-view-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <?php _e('Detalji', 'ai-health-savetnik'); ?>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if (!empty($package_data['benefits'])): ?>
            <div class="aihs-package-benefits">
                <h4 class="aihs-benefits-title"><?php _e('Dodatne pogodnosti:', 'ai-health-savetnik'); ?></h4>
                <ul class="aihs-benefits-list">
                    <?php foreach ($package_data['benefits'] as $benefit): ?>
                    <li class="aihs-benefit-item">
                        <i class="aihs-icon-check"></i>
                        <span><?php echo esc_html($benefit); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="aihs-package-actions">
                <button class="aihs-btn-primary aihs-add-package-to-cart"
                        data-package-id="<?php echo esc_attr($package->id); ?>"
                        data-nonce="<?php echo wp_create_nonce('aihs_add_package_' . $package->id); ?>">
                    <i class="aihs-icon-cart"></i>
                    <?php _e('Dodaj u korpu', 'ai-health-savetnik'); ?>
                </button>
                <button class="aihs-btn-secondary aihs-save-package"
                        data-package-id="<?php echo esc_attr($package->id); ?>"
                        data-nonce="<?php echo wp_create_nonce('aihs_save_package_' . $package->id); ?>">
                    <i class="aihs-icon-heart"></i>
                    <?php _e('Sačuvaj za kasnije', 'ai-health-savetnik'); ?>
                </button>
            </div>

            <?php if (!empty($package_data['delivery_info'])): ?>
            <div class="aihs-package-delivery">
                <div class="aihs-delivery-info">
                    <i class="aihs-icon-truck"></i>
                    <span><?php echo esc_html($package_data['delivery_info']); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($packages)): ?>
    <div class="aihs-no-packages">
        <div class="aihs-no-packages-icon">
            <i class="aihs-icon-package"></i>
        </div>
        <h3><?php _e('Nema dostupnih paketa', 'ai-health-savetnik'); ?></h3>
        <p><?php _e('Trenutno nema kreiranih zdravstvenih paketa. Molimo vas pokušajte ponovo kasnije.', 'ai-health-savetnik'); ?></p>
        <a href="<?php echo home_url('/zdravstveni-upitnik'); ?>" class="aihs-btn-primary">
            <?php _e('Nazad na upitnik', 'ai-health-savetnik'); ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="aihs-packages-footer">
        <div class="aihs-support-info">
            <h4><?php _e('Potrebna vam je pomoć?', 'ai-health-savetnik'); ?></h4>
            <p><?php _e('Naš tim stručnjaka je tu da vam pomogne u odabiru najboljeg paketa za vaše potrebe.', 'ai-health-savetnik'); ?></p>
            <div class="aihs-contact-options">
                <a href="tel:+381111234567" class="aihs-contact-btn">
                    <i class="aihs-icon-phone"></i>
                    <?php _e('Pozovite nas', 'ai-health-savetnik'); ?>
                </a>
                <a href="mailto:podrska@eliksirvitalnosti.com" class="aihs-contact-btn">
                    <i class="aihs-icon-email"></i>
                    <?php _e('Pošaljite email', 'ai-health-savetnik'); ?>
                </a>
            </div>
        </div>

        <div class="aihs-guarantee-info">
            <div class="aihs-guarantee-badge">
                <i class="aihs-icon-shield"></i>
                <div class="aihs-guarantee-text">
                    <strong><?php _e('30 dana garancije', 'ai-health-savetnik'); ?></strong>
                    <span><?php _e('povraćaja novca', 'ai-health-savetnik'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Quick View Modal -->
<div id="aihs-product-modal" class="aihs-modal" style="display: none;">
    <div class="aihs-modal-overlay"></div>
    <div class="aihs-modal-content">
        <div class="aihs-modal-header">
            <h3 id="aihs-modal-title"></h3>
            <button class="aihs-modal-close">&times;</button>
        </div>
        <div class="aihs-modal-body">
            <div id="aihs-modal-loading" class="aihs-loading">
                <div class="aihs-spinner"></div>
                <span><?php _e('Učitavanje...', 'ai-health-savetnik'); ?></span>
            </div>
            <div id="aihs-modal-product-content" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
.aihs-packages-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.aihs-packages-header {
    text-align: center;
    margin-bottom: 40px;
}

.aihs-packages-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
}

.aihs-packages-subtitle {
    font-size: 1.2rem;
    color: #7f8c8d;
    max-width: 600px;
    margin: 0 auto;
}

.aihs-user-context {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aihs-score-summary {
    display: flex;
    align-items: center;
    gap: 10px;
}

.aihs-score-value {
    font-size: 1.5rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 15px;
    border-radius: 25px;
}

.aihs-recommendations-note {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    opacity: 0.9;
}

.aihs-packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.aihs-package-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.aihs-package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.aihs-package-card.featured {
    border-color: #f39c12;
    transform: scale(1.05);
}

.aihs-package-card.featured::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f39c12, #e67e22);
}

.aihs-package-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #f39c12;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.aihs-ai-confidence {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.aihs-ai-label {
    font-size: 0.8rem;
    color: #6c757d;
    display: block;
    margin-bottom: 5px;
}

.aihs-confidence-bar {
    background: #e9ecef;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.aihs-confidence-fill {
    background: linear-gradient(90deg, #28a745, #20c997);
    height: 100%;
    transition: width 0.5s ease;
}

.aihs-confidence-text {
    font-size: 0.8rem;
    color: #28a745;
    font-weight: 600;
}

.aihs-package-header {
    margin-bottom: 20px;
}

.aihs-package-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}

.aihs-package-description {
    color: #7f8c8d;
    line-height: 1.5;
}

.aihs-package-pricing {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.aihs-original-price {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.aihs-price-value.crossed {
    text-decoration: line-through;
    color: #6c757d;
}

.aihs-discount-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.aihs-discount-badge {
    background: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.aihs-savings {
    color: #28a745;
    font-weight: 600;
    font-size: 0.9rem;
}

.aihs-final-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #dee2e6;
    padding-top: 10px;
}

.aihs-final-price .aihs-price-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #27ae60;
}

.aihs-package-products {
    margin-bottom: 20px;
}

.aihs-products-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.aihs-products-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-product-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 8px;
}

.aihs-product-image img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.aihs-product-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.aihs-product-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.aihs-product-quantity {
    font-size: 0.8rem;
    color: #6c757d;
}

.aihs-product-price {
    font-weight: 600;
    color: #27ae60;
    font-size: 0.9rem;
}

.aihs-product-actions .aihs-btn-secondary {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.aihs-package-benefits {
    margin-bottom: 20px;
}

.aihs-benefits-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.aihs-benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-benefit-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 0.9rem;
    color: #2c3e50;
}

.aihs-icon-check {
    color: #27ae60;
    font-weight: 600;
}

.aihs-package-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.aihs-package-actions .aihs-btn-primary {
    flex: 1;
}

.aihs-package-actions .aihs-btn-secondary {
    min-width: auto;
    padding: 12px 15px;
}

.aihs-package-delivery {
    background: #e8f5e8;
    padding: 10px;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
}

.aihs-delivery-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #27ae60;
}

.aihs-no-packages {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 15px;
}

.aihs-no-packages-icon {
    font-size: 4rem;
    color: #bdc3c7;
    margin-bottom: 20px;
}

.aihs-packages-footer {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 40px;
    align-items: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 15px;
    margin-top: 40px;
}

.aihs-support-info h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.aihs-support-info p {
    color: #7f8c8d;
    margin-bottom: 15px;
}

.aihs-contact-options {
    display: flex;
    gap: 15px;
}

.aihs-contact-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    background: white;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.aihs-contact-btn:hover {
    background: #2c3e50;
    color: white;
    text-decoration: none;
}

.aihs-guarantee-info {
    text-align: center;
}

.aihs-guarantee-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    border: 2px solid #27ae60;
}

.aihs-guarantee-badge .aihs-icon-shield {
    font-size: 1.5rem;
    color: #27ae60;
}

.aihs-guarantee-text {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.aihs-guarantee-text strong {
    color: #2c3e50;
    font-size: 0.9rem;
}

.aihs-guarantee-text span {
    color: #7f8c8d;
    font-size: 0.8rem;
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
    max-width: 600px;
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
    .aihs-packages-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .aihs-package-card.featured {
        transform: none;
    }

    .aihs-user-context {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .aihs-package-actions {
        flex-direction: column;
    }

    .aihs-packages-footer {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }

    .aihs-contact-options {
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add package to cart
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
                    $button.text('<?php _e("Dodato u korpu!", "ai-health-savetnik"); ?>');

                    // Redirect to cart after short delay
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

    // Save package for later
    $('.aihs-save-package').on('click', function() {
        const $button = $(this);
        const packageId = $button.data('package-id');
        const nonce = $button.data('nonce');

        $.ajax({
            url: aihs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aihs_save_package',
                package_id: packageId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.html('<i class="aihs-icon-heart-filled"></i> <?php _e("Sačuvano", "ai-health-savetnik"); ?>');
                    $button.addClass('saved');
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e("Došlo je do greške. Molimo pokušajte ponovo.", "ai-health-savetnik"); ?>');
            }
        });
    });

    // Product quick view
    $('.aihs-view-product').on('click', function() {
        const productId = $(this).data('product-id');

        $('#aihs-product-modal').show();
        $('#aihs-modal-loading').show();
        $('#aihs-modal-product-content').hide();

        $.ajax({
            url: aihs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aihs_get_product_quick_view',
                product_id: productId,
                nonce: aihs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#aihs-modal-title').text(response.data.title);
                    $('#aihs-modal-product-content').html(response.data.content);
                    $('#aihs-modal-loading').hide();
                    $('#aihs-modal-product-content').show();
                } else {
                    alert(response.data.message);
                    $('#aihs-product-modal').hide();
                }
            },
            error: function() {
                alert('<?php _e("Došlo je do greške. Molimo pokušajte ponovo.", "ai-health-savetnik"); ?>');
                $('#aihs-product-modal').hide();
            }
        });
    });

    // Close modal
    $('.aihs-modal-close, .aihs-modal-overlay').on('click', function() {
        $('#aihs-product-modal').hide();
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) {
            $('#aihs-product-modal').hide();
        }
    });
});
</script>