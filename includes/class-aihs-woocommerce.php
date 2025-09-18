<?php
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_WooCommerce {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Product metaboxes
        add_action('add_meta_boxes', array($this, 'add_product_metaboxes'));
        add_action('save_post', array($this, 'save_product_meta'));

        // Product display
        add_action('woocommerce_single_product_summary', array($this, 'display_health_info'), 25);
        add_filter('woocommerce_product_tabs', array($this, 'add_health_tab'));

        // Cart and checkout
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_data'), 10, 4);

        // Package products
        add_action('wp_ajax_aihs_add_package_to_cart', array($this, 'add_package_to_cart'));
        add_action('wp_ajax_nopriv_aihs_add_package_to_cart', array($this, 'add_package_to_cart'));

        // Product recommendations
        add_action('wp_ajax_aihs_get_product_quick_view', array($this, 'get_product_quick_view'));
        add_action('wp_ajax_nopriv_aihs_get_product_quick_view', array($this, 'get_product_quick_view'));

        // Package management
        add_action('wp_ajax_aihs_save_package', array($this, 'save_package_for_user'));
        add_action('wp_ajax_aihs_remove_saved_package', array($this, 'remove_saved_package'));
        add_action('wp_ajax_aihs_get_analysis', array($this, 'get_response_analysis'));

        // Admin product list
        add_filter('manage_edit-product_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);

        // Bulk actions for health categories
        add_filter('bulk_actions-edit-product', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_actions'), 10, 3);

        // Package creation hooks
        add_action('woocommerce_new_order', array($this, 'track_package_orders'));
        add_action('woocommerce_order_status_completed', array($this, 'complete_package_order'));
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('AI Health Savetnik zahteva WooCommerce plugin da bi funkcionisao.', 'ai-health-savetnik');
        echo '</p></div>';
    }

    public function add_product_metaboxes() {
        add_meta_box(
            'aihs_product_health_info',
            __('Zdravstvene Informacije', 'ai-health-savetnik'),
            array($this, 'render_health_metabox'),
            'product',
            'normal',
            'high'
        );
    }

    public function render_health_metabox($post) {
        wp_nonce_field('aihs_save_product_meta', 'aihs_product_nonce');

        $health_categories = get_post_meta($post->ID, '_aihs_health_categories', true);
        $health_benefits = get_post_meta($post->ID, '_aihs_health_benefits', true);
        $recommended_for = get_post_meta($post->ID, '_aihs_recommended_for', true);
        $contraindications = get_post_meta($post->ID, '_aihs_contraindications', true);
        $dosage_instructions = get_post_meta($post->ID, '_aihs_dosage_instructions', true);
        $ingredients = get_post_meta($post->ID, '_aihs_ingredients', true);
        $ai_confidence = get_post_meta($post->ID, '_aihs_ai_confidence', true);
        $health_score_range = get_post_meta($post->ID, '_aihs_health_score_range', true);
        $is_featured = get_post_meta($post->ID, '_aihs_is_featured', true);

        $available_categories = array(
            'cardiovascular' => __('Kardiovaskularno zdravlje', 'ai-health-savetnik'),
            'digestive' => __('Digestivno zdravlje', 'ai-health-savetnik'),
            'immune' => __('Imunitet', 'ai-health-savetnik'),
            'mental' => __('Mentalno zdravlje', 'ai-health-savetnik'),
            'energy' => __('Energija i vitalnost', 'ai-health-savetnik'),
            'sleep' => __('Kvalitet sna', 'ai-health-savetnik'),
            'stress' => __('Upravljanje stresom', 'ai-health-savetnik'),
            'weight' => __('Kontrola težine', 'ai-health-savetnik'),
            'joints' => __('Zdravlje zglobova', 'ai-health-savetnik'),
            'skin' => __('Zdravlje kože', 'ai-health-savetnik'),
            'detox' => __('Detoksikacija', 'ai-health-savetnik'),
            'hormones' => __('Hormonalna ravnoteža', 'ai-health-savetnik')
        );

        if (!is_array($health_categories)) {
            $health_categories = array();
        }

        if (!is_array($health_score_range)) {
            $health_score_range = array('min' => '', 'max' => '');
        }
        ?>

        <div class="aihs-metabox-container">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Zdravstvene kategorije', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <div class="aihs-checkbox-grid">
                            <?php foreach ($available_categories as $key => $label): ?>
                            <label class="aihs-checkbox-item">
                                <input type="checkbox" name="aihs_health_categories[]" value="<?php echo esc_attr($key); ?>"
                                       <?php checked(in_array($key, $health_categories)); ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">
                            <?php _e('Izaberite zdravstvene kategorije za koje je ovaj proizvod preporučen.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_health_benefits"><?php _e('Zdravstvene koristi', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <textarea id="aihs_health_benefits" name="aihs_health_benefits" rows="4" class="large-text"
                                  placeholder="<?php _e('Navedite glavne zdravstvene koristi ovog proizvoda...', 'ai-health-savetnik'); ?>"><?php echo esc_textarea($health_benefits); ?></textarea>
                        <p class="description">
                            <?php _e('Opišite kako ovaj proizvod može poboljšati zdravlje korisnika.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_recommended_for"><?php _e('Preporučeno za', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <textarea id="aihs_recommended_for" name="aihs_recommended_for" rows="3" class="large-text"
                                  placeholder="<?php _e('Osobe sa visokim nivoom stresa, problemi sa spavanjem...', 'ai-health-savetnik'); ?>"><?php echo esc_textarea($recommended_for); ?></textarea>
                        <p class="description">
                            <?php _e('Specifične grupe ili uslovi za koje je proizvod posebno koristan.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_contraindications"><?php _e('Kontraindikacije', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <textarea id="aihs_contraindications" name="aihs_contraindications" rows="3" class="large-text"
                                  placeholder="<?php _e('Ne koristiti tokom trudnoće, kod alergije na...', 'ai-health-savetnik'); ?>"><?php echo esc_textarea($contraindications); ?></textarea>
                        <p class="description">
                            <?php _e('Upozorenja i situacije kada proizvod ne treba koristiti.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_dosage_instructions"><?php _e('Uputstvo za doziranje', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <textarea id="aihs_dosage_instructions" name="aihs_dosage_instructions" rows="3" class="large-text"
                                  placeholder="<?php _e('2 kapsule dnevno, najbolje uz obrok...', 'ai-health-savetnik'); ?>"><?php echo esc_textarea($dosage_instructions); ?></textarea>
                        <p class="description">
                            <?php _e('Detaljno uputstvo kako i kada koristiti proizvod.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_ingredients"><?php _e('Ključni sastojci', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <textarea id="aihs_ingredients" name="aihs_ingredients" rows="4" class="large-text"
                                  placeholder="<?php _e('Vitamin C - 500mg, Cink - 15mg, Ehinacea ekstrakt - 200mg...', 'ai-health-savetnik'); ?>"><?php echo esc_textarea($ingredients); ?></textarea>
                        <p class="description">
                            <?php _e('Navedite ključne aktivne sastojke sa dozama.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_ai_confidence"><?php _e('AI Preporuka Confidence (%)', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="aihs_ai_confidence" name="aihs_ai_confidence"
                               value="<?php echo esc_attr($ai_confidence); ?>" min="0" max="100" step="1" class="small-text">
                        <p class="description">
                            <?php _e('Procenat pouzdanosti AI preporuke za ovaj proizvod (0-100).', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label><?php _e('Preporučeno za health score', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <div class="aihs-score-range">
                            <input type="number" name="aihs_health_score_range[min]"
                                   value="<?php echo esc_attr($health_score_range['min']); ?>"
                                   min="0" max="100" step="1" class="small-text" placeholder="Min">
                            <span> - </span>
                            <input type="number" name="aihs_health_score_range[max]"
                                   value="<?php echo esc_attr($health_score_range['max']); ?>"
                                   min="0" max="100" step="1" class="small-text" placeholder="Max">
                        </div>
                        <p class="description">
                            <?php _e('Opseg health score-a za koji je ovaj proizvod preporučen.', 'ai-health-savetnik'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="aihs_is_featured"><?php _e('Istaknuti proizvod', 'ai-health-savetnik'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="aihs_is_featured" name="aihs_is_featured" value="1"
                                   <?php checked($is_featured, '1'); ?>>
                            <?php _e('Prikaži ovaj proizvod kao istaknuti u preporukama', 'ai-health-savetnik'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <style>
        .aihs-metabox-container .form-table th {
            width: 200px;
            vertical-align: top;
            padding-top: 15px;
        }

        .aihs-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }

        .aihs-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
            cursor: pointer;
        }

        .aihs-checkbox-item:hover {
            background: #f0f0f0;
        }

        .aihs-checkbox-item input[type="checkbox"] {
            margin: 0;
        }

        .aihs-score-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aihs-score-range input {
            width: 80px;
        }
        </style>
        <?php
    }

    public function save_product_meta($post_id) {
        if (!isset($_POST['aihs_product_nonce']) || !wp_verify_nonce($_POST['aihs_product_nonce'], 'aihs_save_product_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save health categories
        if (isset($_POST['aihs_health_categories'])) {
            $health_categories = array_map('sanitize_text_field', $_POST['aihs_health_categories']);
            update_post_meta($post_id, '_aihs_health_categories', $health_categories);
        } else {
            delete_post_meta($post_id, '_aihs_health_categories');
        }

        // Save other fields
        $fields = array(
            'aihs_health_benefits' => '_aihs_health_benefits',
            'aihs_recommended_for' => '_aihs_recommended_for',
            'aihs_contraindications' => '_aihs_contraindications',
            'aihs_dosage_instructions' => '_aihs_dosage_instructions',
            'aihs_ingredients' => '_aihs_ingredients'
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = sanitize_textarea_field($_POST[$field]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Save AI confidence
        if (isset($_POST['aihs_ai_confidence'])) {
            $confidence = intval($_POST['aihs_ai_confidence']);
            $confidence = max(0, min(100, $confidence));
            update_post_meta($post_id, '_aihs_ai_confidence', $confidence);
        }

        // Save health score range
        if (isset($_POST['aihs_health_score_range'])) {
            $range = array(
                'min' => intval($_POST['aihs_health_score_range']['min']),
                'max' => intval($_POST['aihs_health_score_range']['max'])
            );
            update_post_meta($post_id, '_aihs_health_score_range', $range);
        }

        // Save featured status
        if (isset($_POST['aihs_is_featured'])) {
            update_post_meta($post_id, '_aihs_is_featured', '1');
        } else {
            delete_post_meta($post_id, '_aihs_is_featured');
        }
    }

    public function display_health_info() {
        global $product;

        $health_categories = get_post_meta($product->get_id(), '_aihs_health_categories', true);
        $health_benefits = get_post_meta($product->get_id(), '_aihs_health_benefits', true);
        $ai_confidence = get_post_meta($product->get_id(), '_aihs_ai_confidence', true);

        if (empty($health_categories) && empty($health_benefits)) {
            return;
        }

        $category_labels = array(
            'cardiovascular' => __('Kardiovaskularno zdravlje', 'ai-health-savetnik'),
            'digestive' => __('Digestivno zdravlje', 'ai-health-savetnik'),
            'immune' => __('Imunitet', 'ai-health-savetnik'),
            'mental' => __('Mentalno zdravlje', 'ai-health-savetnik'),
            'energy' => __('Energija i vitalnost', 'ai-health-savetnik'),
            'sleep' => __('Kvalitet sna', 'ai-health-savetnik'),
            'stress' => __('Upravljanje stresom', 'ai-health-savetnik'),
            'weight' => __('Kontrola težine', 'ai-health-savetnik'),
            'joints' => __('Zdravlje zglobova', 'ai-health-savetnik'),
            'skin' => __('Zdravlje kože', 'ai-health-savetnik'),
            'detox' => __('Detoksikacija', 'ai-health-savetnik'),
            'hormones' => __('Hormonalna ravnoteža', 'ai-health-savetnik')
        );
        ?>

        <div class="aihs-product-health-info">
            <?php if (!empty($health_categories) && is_array($health_categories)): ?>
            <div class="aihs-health-categories">
                <h4><?php _e('Zdravstvene kategorije:', 'ai-health-savetnik'); ?></h4>
                <div class="aihs-category-tags">
                    <?php foreach ($health_categories as $category): ?>
                        <?php if (isset($category_labels[$category])): ?>
                        <span class="aihs-category-tag aihs-category-<?php echo esc_attr($category); ?>">
                            <?php echo esc_html($category_labels[$category]); ?>
                        </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($ai_confidence && $ai_confidence > 0): ?>
            <div class="aihs-ai-recommendation">
                <h4><?php _e('AI Preporuka:', 'ai-health-savetnik'); ?></h4>
                <div class="aihs-confidence-display">
                    <div class="aihs-confidence-bar">
                        <div class="aihs-confidence-fill" style="width: <?php echo esc_attr($ai_confidence); ?>%"></div>
                    </div>
                    <span class="aihs-confidence-text"><?php echo esc_html($ai_confidence); ?>% poklapanje sa vašim potrebama</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .aihs-product-health-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }

        .aihs-product-health-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .aihs-category-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .aihs-category-tag {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .aihs-confidence-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aihs-confidence-bar {
            flex: 1;
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            max-width: 200px;
        }

        .aihs-confidence-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.5s ease;
        }

        .aihs-confidence-text {
            font-size: 0.9rem;
            color: #28a745;
            font-weight: 600;
        }
        </style>
        <?php
    }

    public function add_health_tab($tabs) {
        global $product;

        $health_benefits = get_post_meta($product->get_id(), '_aihs_health_benefits', true);
        $recommended_for = get_post_meta($product->get_id(), '_aihs_recommended_for', true);
        $contraindications = get_post_meta($product->get_id(), '_aihs_contraindications', true);
        $dosage_instructions = get_post_meta($product->get_id(), '_aihs_dosage_instructions', true);
        $ingredients = get_post_meta($product->get_id(), '_aihs_ingredients', true);

        if ($health_benefits || $recommended_for || $contraindications || $dosage_instructions || $ingredients) {
            $tabs['health_info'] = array(
                'title' => __('Zdravstvene Informacije', 'ai-health-savetnik'),
                'priority' => 50,
                'callback' => array($this, 'health_tab_content')
            );
        }

        return $tabs;
    }

    public function health_tab_content() {
        global $product;

        $health_benefits = get_post_meta($product->get_id(), '_aihs_health_benefits', true);
        $recommended_for = get_post_meta($product->get_id(), '_aihs_recommended_for', true);
        $contraindications = get_post_meta($product->get_id(), '_aihs_contraindications', true);
        $dosage_instructions = get_post_meta($product->get_id(), '_aihs_dosage_instructions', true);
        $ingredients = get_post_meta($product->get_id(), '_aihs_ingredients', true);
        ?>

        <div class="aihs-health-tab-content">
            <?php if ($health_benefits): ?>
            <div class="aihs-health-section">
                <h3><?php _e('Zdravstvene koristi', 'ai-health-savetnik'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($health_benefits)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($ingredients): ?>
            <div class="aihs-health-section">
                <h3><?php _e('Ključni sastojci', 'ai-health-savetnik'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($ingredients)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($dosage_instructions): ?>
            <div class="aihs-health-section">
                <h3><?php _e('Uputstvo za doziranje', 'ai-health-savetnik'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($dosage_instructions)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($recommended_for): ?>
            <div class="aihs-health-section">
                <h3><?php _e('Posebno preporučeno za', 'ai-health-savetnik'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($recommended_for)); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($contraindications): ?>
            <div class="aihs-health-section aihs-warning">
                <h3><?php _e('Upozorenja i kontraindikacije', 'ai-health-savetnik'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($contraindications)); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .aihs-health-tab-content {
            padding: 20px 0;
        }

        .aihs-health-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .aihs-health-section.aihs-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .aihs-health-section h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .aihs-health-section p {
            color: #495057;
            line-height: 1.6;
            margin: 0;
        }
        </style>
        <?php
    }

    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['aihs_package_id'])) {
            $package_id = intval($_POST['aihs_package_id']);
            $package = AIHS_Database::get_package($package_id);

            if ($package) {
                $cart_item_data['aihs_package_id'] = $package_id;
                $cart_item_data['aihs_package_name'] = $package->name;
                $cart_item_data['unique_key'] = md5(microtime() . rand());
            }
        }

        if (isset($_POST['aihs_response_id'])) {
            $response_id = intval($_POST['aihs_response_id']);
            $cart_item_data['aihs_response_id'] = $response_id;
        }

        return $cart_item_data;
    }

    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['aihs_package_name'])) {
            $item_data[] = array(
                'key' => __('Zdravstveni paket', 'ai-health-savetnik'),
                'value' => esc_html($cart_item['aihs_package_name']),
                'display' => ''
            );
        }

        if (isset($cart_item['aihs_response_id'])) {
            $item_data[] = array(
                'key' => __('Personalizovano za', 'ai-health-savetnik'),
                'value' => __('Vašu zdravstvenu procenu', 'ai-health-savetnik'),
                'display' => ''
            );
        }

        return $item_data;
    }

    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['aihs_package_id'])) {
            $item->add_meta_data('_aihs_package_id', $values['aihs_package_id']);
        }

        if (isset($values['aihs_package_name'])) {
            $item->add_meta_data('_aihs_package_name', $values['aihs_package_name']);
        }

        if (isset($values['aihs_response_id'])) {
            $item->add_meta_data('_aihs_response_id', $values['aihs_response_id']);
        }
    }

    public function add_package_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_add_package_' . $_POST['package_id'])) {
            wp_send_json_error(array('message' => __('Sigurnosna provera neuspešna.', 'ai-health-savetnik')));
        }

        $package_id = intval($_POST['package_id']);
        $package = AIHS_Database::get_package($package_id);

        if (!$package) {
            wp_send_json_error(array('message' => __('Paket nije pronađen.', 'ai-health-savetnik')));
        }

        $package_data = json_decode($package->package_data, true);
        if (!$package_data || empty($package_data['products'])) {
            wp_send_json_error(array('message' => __('Paket nema definisane proizvode.', 'ai-health-savetnik')));
        }

        // Create package product if it doesn't exist
        $package_product_id = $this->create_package_product($package);

        if (!$package_product_id) {
            wp_send_json_error(array('message' => __('Greška pri kreiranju paketa.', 'ai-health-savetnik')));
        }

        // Add package product to cart
        $cart_item_data = array(
            'aihs_package_id' => $package_id,
            'aihs_package_name' => $package->name,
            'unique_key' => md5(microtime() . rand())
        );

        $cart_item_key = WC()->cart->add_to_cart($package_product_id, 1, 0, array(), $cart_item_data);

        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => __('Paket je dodat u korpu!', 'ai-health-savetnik'),
                'cart_url' => wc_get_cart_url()
            ));
        } else {
            wp_send_json_error(array('message' => __('Greška pri dodavanju u korpu.', 'ai-health-savetnik')));
        }
    }

    public function get_product_quick_view() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera neuspešna.', 'ai-health-savetnik')));
        }

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => __('Proizvod nije pronađen.', 'ai-health-savetnik')));
        }

        ob_start();
        ?>
        <div class="aihs-quick-view-content">
            <div class="aihs-product-image">
                <?php echo $product->get_image('medium'); ?>
            </div>
            <div class="aihs-product-details">
                <h4><?php echo esc_html($product->get_name()); ?></h4>
                <div class="aihs-product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
                <div class="aihs-product-description">
                    <?php echo wp_trim_words($product->get_short_description(), 30); ?>
                </div>

                <?php
                $health_benefits = get_post_meta($product_id, '_aihs_health_benefits', true);
                if ($health_benefits):
                ?>
                <div class="aihs-health-benefits">
                    <h5><?php _e('Zdravstvene koristi:', 'ai-health-savetnik'); ?></h5>
                    <p><?php echo esc_html(wp_trim_words($health_benefits, 20)); ?></p>
                </div>
                <?php endif; ?>

                <div class="aihs-quick-view-actions">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="aihs-btn-secondary">
                        <?php _e('Pogledaj detalje', 'ai-health-savetnik'); ?>
                    </a>
                    <button class="aihs-btn-primary aihs-add-to-cart-quick" data-product-id="<?php echo esc_attr($product_id); ?>">
                        <?php _e('Dodaj u korpu', 'ai-health-savetnik'); ?>
                    </button>
                </div>
            </div>
        </div>

        <style>
        .aihs-quick-view-content {
            display: flex;
            gap: 20px;
        }

        .aihs-product-image {
            flex: 0 0 200px;
        }

        .aihs-product-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .aihs-product-details {
            flex: 1;
        }

        .aihs-product-details h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .aihs-product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #27ae60;
            margin-bottom: 15px;
        }

        .aihs-product-description {
            color: #7f8c8d;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .aihs-health-benefits {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .aihs-health-benefits h5 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .aihs-health-benefits p {
            color: #495057;
            margin: 0;
            font-size: 0.8rem;
        }

        .aihs-quick-view-actions {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 600px) {
            .aihs-quick-view-content {
                flex-direction: column;
            }

            .aihs-product-image {
                flex: none;
            }
        }
        </style>
        <?php
        $content = ob_get_clean();

        wp_send_json_success(array(
            'title' => $product->get_name(),
            'content' => $content
        ));
    }

    private function create_package_product($package) {
        $package_data = json_decode($package->package_data, true);

        // Check if package product already exists
        $existing_product_id = get_post_meta(0, '_aihs_package_product_' . $package->id, true);
        if ($existing_product_id && get_post($existing_product_id)) {
            return $existing_product_id;
        }

        $original_price = floatval($package_data['original_price'] ?? 0);
        $discount_percentage = floatval($package_data['discount_percentage'] ?? 0);
        $final_price = $original_price * (1 - $discount_percentage / 100);

        $product_data = array(
            'post_title' => $package->name,
            'post_content' => $package->description,
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => array(
                '_visibility' => 'visible',
                '_stock_status' => 'instock',
                '_manage_stock' => 'no',
                '_regular_price' => $original_price,
                '_sale_price' => $final_price,
                '_price' => $final_price,
                '_virtual' => 'yes',
                '_sold_individually' => 'yes',
                '_aihs_package_id' => $package->id,
                '_aihs_is_package_product' => 'yes'
            )
        );

        $product_id = wp_insert_post($product_data);

        if ($product_id) {
            // Set product categories
            wp_set_object_terms($product_id, 'zdravstveni-paketi', 'product_cat');

            // Store package product ID for future reference
            update_option('_aihs_package_product_' . $package->id, $product_id);

            return $product_id;
        }

        return false;
    }

    public function add_product_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;

            if ($key === 'name') {
                $new_columns['aihs_health_categories'] = __('Zdravstvene kategorije', 'ai-health-savetnik');
                $new_columns['aihs_ai_confidence'] = __('AI Confidence', 'ai-health-savetnik');
            }
        }

        return $new_columns;
    }

    public function populate_product_columns($column, $post_id) {
        switch ($column) {
            case 'aihs_health_categories':
                $categories = get_post_meta($post_id, '_aihs_health_categories', true);
                if ($categories && is_array($categories)) {
                    echo '<span class="aihs-category-count">' . count($categories) . ' kategorija</span>';
                } else {
                    echo '<span class="aihs-no-categories">—</span>';
                }
                break;

            case 'aihs_ai_confidence':
                $confidence = get_post_meta($post_id, '_aihs_ai_confidence', true);
                if ($confidence) {
                    echo '<span class="aihs-confidence-badge">' . esc_html($confidence) . '%</span>';
                } else {
                    echo '<span class="aihs-no-confidence">—</span>';
                }
                break;
        }
    }

    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['aihs_set_featured'] = __('Označi kao istaknute proizvode', 'ai-health-savetnik');
        $bulk_actions['aihs_unset_featured'] = __('Ukloni oznaku istaknutog proizvoda', 'ai-health-savetnik');
        return $bulk_actions;
    }

    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'aihs_set_featured') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_aihs_is_featured', '1');
            }
            $redirect_to = add_query_arg('bulk_featured_products', count($post_ids), $redirect_to);
        }

        if ($doaction === 'aihs_unset_featured') {
            foreach ($post_ids as $post_id) {
                delete_post_meta($post_id, '_aihs_is_featured');
            }
            $redirect_to = add_query_arg('bulk_unfeatured_products', count($post_ids), $redirect_to);
        }

        return $redirect_to;
    }

    public function track_package_orders($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        foreach ($order->get_items() as $item) {
            $package_id = $item->get_meta('_aihs_package_id');
            if ($package_id) {
                // Track package order in database
                AIHS_Database::track_package_order($package_id, $order_id, $order->get_user_id());
            }
        }
    }

    public function complete_package_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        foreach ($order->get_items() as $item) {
            $package_id = $item->get_meta('_aihs_package_id');
            $response_id = $item->get_meta('_aihs_response_id');

            if ($package_id && $response_id) {
                // Update response with completed package info
                AIHS_Database::update_response_package_status($response_id, $package_id, 'completed');

                // Send follow-up email (optional)
                $this->send_package_completion_email($order->get_billing_email(), $package_id);
            }
        }
    }

    private function send_package_completion_email($email, $package_id) {
        $package = AIHS_Database::get_package($package_id);
        if (!$package) return;

        $subject = sprintf(__('Vaš zdravstveni paket "%s" je uspešno dostavljen', 'ai-health-savetnik'), $package->name);

        $message = sprintf(
            __('Zdravo!\n\nVaš zdravstveni paket "%s" je uspešno dostavljen.\n\nPreporučujemo da posetite vaš korisnički panel na %s da pratite svoj napredak.\n\nSrdačan pozdrav,\nTim Eliksir Vitalnosti', 'ai-health-savetnik'),
            $package->name,
            home_url('/moj-nalog')
        );

        wp_mail($email, $subject, $message);
    }

    public function save_package_for_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_save_package_' . $_POST['package_id'])) {
            wp_send_json_error(array('message' => __('Sigurnosna provera neuspešna.', 'ai-health-savetnik')));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti ulogovani da biste sačuvali paket.', 'ai-health-savetnik')));
        }

        $package_id = intval($_POST['package_id']);
        $user_id = get_current_user_id();

        $result = AIHS_Database::save_package_for_user($user_id, $package_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Paket je sačuvan!', 'ai-health-savetnik')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri čuvanju paketa.', 'ai-health-savetnik')));
        }
    }

    public function remove_saved_package() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_remove_saved_package_' . $_POST['package_id'])) {
            wp_send_json_error(array('message' => __('Sigurnosna provera neuspešna.', 'ai-health-savetnik')));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Morate biti ulogovani.', 'ai-health-savetnik')));
        }

        $package_id = intval($_POST['package_id']);
        $user_id = get_current_user_id();

        $result = AIHS_Database::remove_saved_package($user_id, $package_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Paket je uklonjen.', 'ai-health-savetnik')));
        } else {
            wp_send_json_error(array('message' => __('Greška pri uklanjanju paketa.', 'ai-health-savetnik')));
        }
    }

    public function get_response_analysis() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_nonce')) {
            wp_send_json_error(array('message' => __('Sigurnosna provera neuspešna.', 'ai-health-savetnik')));
        }

        $response_id = intval($_POST['response_id']);
        $response = AIHS_Database::get_response($response_id);

        if (!$response) {
            wp_send_json_error(array('message' => __('Procena nije pronađena.', 'ai-health-savetnik')));
        }

        // Check if user has access to this response
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($response->user_id && $response->user_id != $user_id) {
                wp_send_json_error(array('message' => __('Nemate dozvolu za pristup ovoj proceni.', 'ai-health-savetnik')));
            }
        }

        $analysis = $response->ai_analysis;
        if (empty($analysis)) {
            wp_send_json_error(array('message' => __('AI analiza još nije generisana za ovu procenu.', 'ai-health-savetnik')));
        }

        // Format analysis for display
        $formatted_analysis = '<div class="aihs-analysis-content">' . wp_kses_post(nl2br($analysis)) . '</div>';

        wp_send_json_success(array('analysis' => $formatted_analysis));
    }

    public static function get_products_for_health_score($score, $categories = array()) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND'
            )
        );

        // Filter by health score range
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => '_aihs_health_score_range',
                'value' => '',
                'compare' => '='
            ),
            array(
                'key' => '_aihs_health_score_range',
                'value' => serialize(array('min' => '', 'max' => '')),
                'compare' => '='
            ),
            array(
                'key' => '_aihs_health_score_range',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => '_aihs_health_score_range',
                    'value' => '"min";i:' . ($score - 10),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_aihs_health_score_range',
                    'value' => '"max";i:' . ($score + 10),
                    'compare' => 'LIKE'
                )
            )
        );

        $products = get_posts($args);
        $filtered_products = array();

        foreach ($products as $product) {
            $product_categories = get_post_meta($product->ID, '_aihs_health_categories', true);
            $score_range = get_post_meta($product->ID, '_aihs_health_score_range', true);

            // Check score range
            $score_match = true;
            if ($score_range && is_array($score_range) && !empty($score_range['min']) && !empty($score_range['max'])) {
                $score_match = ($score >= $score_range['min'] && $score <= $score_range['max']);
            }

            // Check category match
            $category_match = empty($categories) || empty($product_categories);
            if (!empty($categories) && !empty($product_categories) && is_array($product_categories)) {
                $category_match = !empty(array_intersect($categories, $product_categories));
            }

            if ($score_match && $category_match) {
                $ai_confidence = get_post_meta($product->ID, '_aihs_ai_confidence', true);
                $is_featured = get_post_meta($product->ID, '_aihs_is_featured', true);

                $filtered_products[] = array(
                    'product' => wc_get_product($product->ID),
                    'ai_confidence' => intval($ai_confidence),
                    'is_featured' => ($is_featured === '1'),
                    'categories' => $product_categories
                );
            }
        }

        // Sort by AI confidence and featured status
        usort($filtered_products, function($a, $b) {
            if ($a['is_featured'] && !$b['is_featured']) return -1;
            if (!$a['is_featured'] && $b['is_featured']) return 1;
            return $b['ai_confidence'] - $a['ai_confidence'];
        });

        return $filtered_products;
    }
}

// Initialize WooCommerce integration
AIHS_WooCommerce::get_instance();