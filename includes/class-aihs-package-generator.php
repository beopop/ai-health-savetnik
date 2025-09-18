<?php
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_Package_Generator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Package generation hooks
        add_action('aihs_response_completed', array($this, 'generate_packages_for_response'), 10, 1);
        add_action('wp_ajax_aihs_regenerate_packages', array($this, 'regenerate_packages'));
        add_action('wp_ajax_aihs_create_custom_package', array($this, 'create_custom_package'));

        // Cleanup expired packages daily
        add_action('wp_scheduled_delete', array($this, 'cleanup_expired_packages'));
    }

    /**
     * Generate personalized packages for a response
     */
    public function generate_packages_for_response($response_id) {
        $response = AIHS_Database::get_response($response_id);
        if (!$response) {
            return false;
        }

        // Parse response data
        $answers = json_decode($response->answers, true) ?: array();
        $intensity_data = json_decode($response->intensity_data, true) ?: array();
        $score = intval($response->health_score);

        // Identify health issues and priorities
        $health_issues = $this->analyze_health_issues($answers, $intensity_data);
        $priority_categories = $this->get_priority_categories($health_issues, $score);

        // Get recommended products
        $products = AIHS_WooCommerce::get_products_for_health_score($score, $priority_categories);

        if (empty($products)) {
            error_log("AIHS Package Generator: No products found for score $score and categories " . implode(', ', $priority_categories));
            return false;
        }

        // Generate different package combinations
        $packages = $this->create_package_combinations($products, $response, $health_issues);

        // Save packages to database
        $saved_packages = array();
        foreach ($packages as $package) {
            $package_id = $this->save_package($package, $response_id);
            if ($package_id) {
                $saved_packages[] = $package_id;
            }
        }

        // Update response with package information
        if (!empty($saved_packages)) {
            $package_info = array(
                'generated_packages' => $saved_packages,
                'generation_date' => current_time('mysql'),
                'health_issues' => $health_issues,
                'priority_categories' => $priority_categories
            );

            AIHS_Database::update_response($response_id, array(
                'ai_custom_packages' => json_encode($package_info)
            ));

            error_log("AIHS Package Generator: Generated " . count($saved_packages) . " packages for response $response_id");
            return $saved_packages;
        }

        return false;
    }

    /**
     * Analyze health issues from answers
     */
    private function analyze_health_issues($answers, $intensity_data) {
        $questions = get_option('aihs_health_questions', array());
        $health_issues = array();

        foreach ($answers as $question_id => $answer) {
            $question = $this->find_question($questions, $question_id);
            if (!$question || $answer !== 'yes') {
                continue;
            }

            $intensity = $intensity_data[$question_id] ?? 'medium';
            $intensity_weight = $this->get_intensity_weight($question, $intensity);

            $health_issues[] = array(
                'question_id' => $question_id,
                'question_text' => $question['text'],
                'weight' => $question['weight'],
                'intensity' => $intensity,
                'intensity_weight' => $intensity_weight,
                'total_impact' => $question['weight'] * ($intensity_weight / 10),
                'ai_hint' => $question['ai_hint'] ?? '',
                'categories' => $this->map_question_to_categories($question)
            );
        }

        // Sort by total impact (highest first)
        usort($health_issues, function($a, $b) {
            return $b['total_impact'] - $a['total_impact'];
        });

        return $health_issues;
    }

    /**
     * Get priority health categories
     */
    private function get_priority_categories($health_issues, $score) {
        $categories = array();
        $category_weights = array();

        foreach ($health_issues as $issue) {
            foreach ($issue['categories'] as $category) {
                if (!isset($category_weights[$category])) {
                    $category_weights[$category] = 0;
                }
                $category_weights[$category] += $issue['total_impact'];
            }
        }

        // Sort categories by weight
        arsort($category_weights);

        // Select top categories based on score
        $max_categories = $score < 40 ? 4 : ($score < 70 ? 3 : 2);
        $categories = array_keys(array_slice($category_weights, 0, $max_categories, true));

        // Always include general categories for low scores
        if ($score < 50) {
            $categories = array_merge($categories, array('immune', 'energy'));
            $categories = array_unique($categories);
        }

        return $categories;
    }

    /**
     * Create different package combinations
     */
    private function create_package_combinations($products, $response, $health_issues) {
        $packages = array();
        $discounts = get_option('aihs_package_discounts', array());

        // Package 1: Essential Package (2-3 products)
        $essential_products = array_slice($products, 0, 3);
        if (count($essential_products) >= 2) {
            $packages[] = $this->build_package(
                'Osnovni Zdravstveni Paket',
                'Najvažniji proizvodi za vaše zdravstvene potrebe',
                $essential_products,
                $discounts['2_products'] ?? 10,
                1,
                $health_issues
            );
        }

        // Package 2: Comprehensive Package (4-5 products)
        if (count($products) >= 4) {
            $comprehensive_products = array_slice($products, 0, 5);
            $packages[] = $this->build_package(
                'Sveobuhvatni Zdravstveni Paket',
                'Kompletna podrška za optimalno zdravlje',
                $comprehensive_products,
                $discounts['4_products'] ?? 16,
                2,
                $health_issues
            );
        }

        // Package 3: Premium Package (6+ products) - only for low scores
        if (intval($response->health_score) < 60 && count($products) >= 6) {
            $premium_products = array_slice($products, 0, 6);
            $packages[] = $this->build_package(
                'Premium Zdravstveni Paket',
                'Maksimalna podrška za značajno poboljšanje zdravlja',
                $premium_products,
                $discounts['6_products'] ?? 20,
                3,
                $health_issues,
                true // featured
            );
        }

        // Package 4: Targeted Package (focus on main issues)
        if (!empty($health_issues)) {
            $main_categories = array_slice($this->get_priority_categories($health_issues, $response->health_score), 0, 2);
            $targeted_products = array_filter($products, function($item) use ($main_categories) {
                $product_categories = $item['categories'] ?? array();
                return !empty(array_intersect($product_categories, $main_categories));
            });

            if (count($targeted_products) >= 2) {
                $targeted_products = array_slice($targeted_products, 0, 3);
                $packages[] = $this->build_package(
                    'Ciljani Terapijski Paket',
                    'Fokusiran na vaše glavne zdravstvene probleme',
                    $targeted_products,
                    $discounts['3_products'] ?? 12,
                    4,
                    $health_issues
                );
            }
        }

        return $packages;
    }

    /**
     * Build a package structure
     */
    private function build_package($name, $description, $products, $discount_percentage, $priority, $health_issues, $featured = false) {
        $original_price = 0;
        $package_products = array();

        foreach ($products as $product_item) {
            $product = $product_item['product'];
            $price = floatval($product->get_price());
            $original_price += $price;

            $package_products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $price,
                'quantity' => 1,
                'ai_confidence' => $product_item['ai_confidence'],
                'is_featured' => $product_item['is_featured'],
                'categories' => $product_item['categories']
            );
        }

        $discount_amount = ($original_price * $discount_percentage) / 100;
        $final_price = $original_price - $discount_amount;

        // Calculate AI confidence for the package
        $total_confidence = 0;
        $confidence_count = 0;
        foreach ($package_products as $product) {
            if ($product['ai_confidence'] > 0) {
                $total_confidence += $product['ai_confidence'];
                $confidence_count++;
            }
        }
        $average_confidence = $confidence_count > 0 ? round($total_confidence / $confidence_count) : 75;

        // Generate benefits based on health issues
        $benefits = $this->generate_package_benefits($health_issues, $package_products);

        $package_data = array(
            'products' => $package_products,
            'original_price' => $original_price,
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'final_price' => $final_price,
            'ai_confidence' => $average_confidence,
            'featured' => $featured,
            'priority' => $priority,
            'benefits' => $benefits,
            'health_issues_addressed' => array_slice($health_issues, 0, 3),
            'delivery_info' => 'Besplatna dostava za porudžbine preko 5000 RSD',
            'estimated_savings' => $discount_amount,
            'recommended_duration' => $this->calculate_recommended_duration($health_issues)
        );

        return array(
            'name' => $name,
            'description' => $description,
            'package_data' => $package_data,
            'status' => 'generated'
        );
    }

    /**
     * Generate package benefits based on health issues
     */
    private function generate_package_benefits($health_issues, $products) {
        $benefits = array();

        // Based on health issues
        $issue_categories = array();
        foreach ($health_issues as $issue) {
            $issue_categories = array_merge($issue_categories, $issue['categories']);
        }
        $issue_categories = array_unique($issue_categories);

        $benefit_map = array(
            'sleep' => 'Poboljšanje kvaliteta sna i odmora',
            'stress' => 'Smanjenje stresa i anksioznosti',
            'energy' => 'Povećanje energije i vitalnosti',
            'digestive' => 'Bolje digestivno zdravlje',
            'immune' => 'Jačanje imuniteta',
            'cardiovascular' => 'Podrška kardiovaskularnom sistemu',
            'mental' => 'Poboljšanje mentalnog zdravlja',
            'joints' => 'Zdravlje zglobova i kostiju',
            'weight' => 'Podrška u kontroli težine',
            'detox' => 'Detoksikacija organizma'
        );

        foreach ($issue_categories as $category) {
            if (isset($benefit_map[$category])) {
                $benefits[] = $benefit_map[$category];
            }
        }

        // Add general benefits
        if (count($products) >= 3) {
            $benefits[] = 'Sveobuhvatan pristup zdravlju';
        }

        if (count($products) >= 4) {
            $benefits[] = 'Sinergijski efekat kombinovanih proizvoda';
        }

        $benefits[] = 'Praćenje napretka i podrška stručnjaka';
        $benefits[] = '30 dana garancije povraćaja novca';

        return array_slice(array_unique($benefits), 0, 6);
    }

    /**
     * Calculate recommended duration
     */
    private function calculate_recommended_duration($health_issues) {
        if (empty($health_issues)) {
            return '2-3 meseca';
        }

        $avg_impact = array_sum(array_column($health_issues, 'total_impact')) / count($health_issues);

        if ($avg_impact > 20) {
            return '3-6 meseci';
        } elseif ($avg_impact > 15) {
            return '2-4 meseca';
        } else {
            return '1-3 meseca';
        }
    }

    /**
     * Save package to database
     */
    private function save_package($package, $response_id) {
        $data = array(
            'response_id' => $response_id,
            'name' => $package['name'],
            'description' => $package['description'],
            'package_data' => json_encode($package['package_data']),
            'status' => $package['status'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        return AIHS_Database::create_package($data);
    }

    /**
     * Regenerate packages for response (AJAX)
     */
    public function regenerate_packages() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_admin_nonce')) {
            wp_send_json_error(array('message' => 'Sigurnosna provera neuspešna.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nemate dozvolu.'));
        }

        $response_id = intval($_POST['response_id']);

        // Delete existing packages for this response
        $existing_packages = AIHS_Database::get_packages_for_response($response_id);
        foreach ($existing_packages as $package) {
            AIHS_Database::delete_package($package->id);
        }

        // Generate new packages
        $result = $this->generate_packages_for_response($response_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Paketi su uspešno regenerisani.',
                'packages_count' => count($result)
            ));
        } else {
            wp_send_json_error(array('message' => 'Greška pri generisanju paketa.'));
        }
    }

    /**
     * Create custom package (Admin)
     */
    public function create_custom_package() {
        if (!wp_verify_nonce($_POST['nonce'], 'aihs_admin_nonce')) {
            wp_send_json_error(array('message' => 'Sigurnosna provera neuspešna.'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nemate dozvolu.'));
        }

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $product_ids = array_map('intval', $_POST['product_ids']);
        $discount_percentage = floatval($_POST['discount_percentage']);

        if (empty($name) || empty($product_ids)) {
            wp_send_json_error(array('message' => 'Nedostaju obavezna polja.'));
        }

        // Build products array
        $products = array();
        $original_price = 0;

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) continue;

            $price = floatval($product->get_price());
            $original_price += $price;

            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'price' => $price,
                'quantity' => 1,
                'ai_confidence' => get_post_meta($product_id, '_aihs_ai_confidence', true) ?: 75,
                'is_featured' => get_post_meta($product_id, '_aihs_is_featured', true) === '1',
                'categories' => get_post_meta($product_id, '_aihs_health_categories', true) ?: array()
            );
        }

        $discount_amount = ($original_price * $discount_percentage) / 100;
        $final_price = $original_price - $discount_amount;

        $package_data = array(
            'products' => $products,
            'original_price' => $original_price,
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'final_price' => $final_price,
            'ai_confidence' => 0, // Manual package
            'featured' => false,
            'priority' => 99, // Low priority for manual packages
            'benefits' => array('Ručno kreirani paket', 'Stručno odabrani proizvodi'),
            'delivery_info' => 'Besplatna dostava za porudžbine preko 5000 RSD',
            'estimated_savings' => $discount_amount,
            'recommended_duration' => '2-3 meseca'
        );

        $data = array(
            'response_id' => null, // Global package
            'name' => $name,
            'description' => $description,
            'package_data' => json_encode($package_data),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $package_id = AIHS_Database::create_package($data);

        if ($package_id) {
            wp_send_json_success(array(
                'message' => 'Paket je uspešno kreiran.',
                'package_id' => $package_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Greška pri kreiranju paketa.'));
        }
    }

    /**
     * Cleanup expired packages
     */
    public function cleanup_expired_packages() {
        $deleted = AIHS_Database::cleanup_expired_packages();
        if ($deleted > 0) {
            error_log("AIHS Package Generator: Cleaned up $deleted expired packages");
        }
    }

    /**
     * Helper methods
     */
    private function find_question($questions, $question_id) {
        foreach ($questions as $question) {
            if ($question['id'] == $question_id) {
                return $question;
            }
        }
        return null;
    }

    private function get_intensity_weight($question, $intensity) {
        if (!isset($question['intensity_options']) || !isset($question['intensity_weights'])) {
            return 10; // Default weight
        }

        $index = array_search($intensity, $question['intensity_options']);
        if ($index !== false && isset($question['intensity_weights'][$index])) {
            return $question['intensity_weights'][$index];
        }

        return 10; // Default weight
    }

    private function map_question_to_categories($question) {
        // Map questions to health categories based on AI hints
        $hint = strtolower($question['ai_hint'] ?? '');
        $categories = array();

        $category_keywords = array(
            'sleep' => array('sleep', 'insomnia', 'rest'),
            'stress' => array('stress', 'anxiety', 'mental', 'psychological'),
            'energy' => array('energy', 'fatigue', 'tiredness', 'vitality'),
            'digestive' => array('digestive', 'stomach', 'gut', 'digestion'),
            'immune' => array('immune', 'immunity', 'allergies'),
            'cardiovascular' => array('cardio', 'heart', 'blood', 'circulation'),
            'joints' => array('joints', 'back pain', 'muscle', 'posture'),
            'mental' => array('concentration', 'focus', 'cognitive', 'brain'),
            'weight' => array('weight', 'metabolism', 'obesity'),
            'detox' => array('detox', 'cleanse', 'toxin')
        );

        foreach ($category_keywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($hint, $keyword) !== false) {
                    $categories[] = $category;
                    break;
                }
            }
        }

        return array_unique($categories);
    }
}

// Initialize package generator
AIHS_Package_Generator::get_instance();