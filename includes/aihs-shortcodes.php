<?php
/**
 * Shortcodes for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main health quiz shortcode
 */
add_shortcode('aihs_health_quiz', 'aihs_health_quiz_shortcode');
function aihs_health_quiz_shortcode($atts) {
    $atts = shortcode_atts(array(
        'style' => 'default',
        'show_progress' => 'yes',
        'auto_advance' => 'no',
        'theme' => 'light'
    ), $atts, 'aihs_health_quiz');

    // Check if user already has a completed response
    $existing_response = null;
    if (is_user_logged_in()) {
        $existing_response = aihs_get_user_response();
    } else {
        $session_id = aihs_get_session_id();
        $existing_response = aihs_get_session_response($session_id);
    }

    ob_start();

    // Include the main quiz template
    include AIHS_PLUGIN_DIR . 'frontend/templates/quiz-main.php';

    return ob_get_clean();
}

/**
 * Health quiz results shortcode
 */
add_shortcode('aihs_quiz_results', 'aihs_quiz_results_shortcode');
function aihs_quiz_results_shortcode($atts) {
    $atts = shortcode_atts(array(
        'response_id' => '',
        'public_id' => '',
        'show_details' => 'yes',
        'show_recommendations' => 'yes',
        'show_products' => 'yes'
    ), $atts, 'aihs_quiz_results');

    $response = null;

    // Get response by public ID or response ID
    if (!empty($atts['public_id'])) {
        $response = aihs_get_response_by_public_id($atts['public_id']);
    } elseif (!empty($atts['response_id'])) {
        $response = AIHS_Database::get_response(intval($atts['response_id']));
    } else {
        // Get current user's latest response
        if (is_user_logged_in()) {
            $response = aihs_get_user_response();
        } else {
            $session_id = aihs_get_session_id();
            $response = aihs_get_session_response($session_id);
        }
    }

    if (!$response || $response->completion_status !== 'analysis_completed') {
        return '<div class="aihs-error">Rezultati nisu dostupni.</div>';
    }

    ob_start();

    // Include the results template
    include AIHS_PLUGIN_DIR . 'frontend/templates/quiz-results.php';

    return ob_get_clean();
}

/**
 * Health quiz form only (without questions)
 */
add_shortcode('aihs_health_form', 'aihs_health_form_shortcode');
function aihs_health_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'redirect_url' => '',
        'show_optional_fields' => 'yes'
    ), $atts, 'aihs_health_form');

    ob_start();

    // Include the form template
    include AIHS_PLUGIN_DIR . 'frontend/templates/health-form.php';

    return ob_get_clean();
}

/**
 * Health quiz questions only
 */
add_shortcode('aihs_health_questions', 'aihs_health_questions_shortcode');
function aihs_health_questions_shortcode($atts) {
    $atts = shortcode_atts(array(
        'questions_per_page' => '0', // 0 = all questions on one page
        'show_progress' => 'yes',
        'require_form_first' => 'yes'
    ), $atts, 'aihs_health_questions');

    // Check if form is completed first
    if ($atts['require_form_first'] === 'yes') {
        $session_id = aihs_get_session_id();
        $response = aihs_get_session_response($session_id);

        if (!$response || $response->completion_status === 'draft') {
            return '<div class="aihs-notice">Molimo prvo popunite osnovne podatke.</div>';
        }
    }

    ob_start();

    // Include the questions template
    include AIHS_PLUGIN_DIR . 'frontend/templates/quiz-questions.php';

    return ob_get_clean();
}

/**
 * Health score display
 */
add_shortcode('aihs_health_score', 'aihs_health_score_shortcode');
function aihs_health_score_shortcode($atts) {
    $atts = shortcode_atts(array(
        'response_id' => '',
        'public_id' => '',
        'show_category' => 'yes',
        'show_gauge' => 'yes',
        'size' => 'medium'
    ), $atts, 'aihs_health_score');

    $response = null;

    if (!empty($atts['public_id'])) {
        $response = aihs_get_response_by_public_id($atts['public_id']);
    } elseif (!empty($atts['response_id'])) {
        $response = AIHS_Database::get_response(intval($atts['response_id']));
    } else {
        if (is_user_logged_in()) {
            $response = aihs_get_user_response();
        } else {
            $session_id = aihs_get_session_id();
            $response = aihs_get_session_response($session_id);
        }
    }

    if (!$response || $response->calculated_score <= 0) {
        return '<div class="aihs-error">Skor nije dostupan.</div>';
    }

    ob_start();

    // Include the score template
    include AIHS_PLUGIN_DIR . 'frontend/templates/health-score.php';

    return ob_get_clean();
}

/**
 * Product recommendations display
 */
add_shortcode('aihs_product_recommendations', 'aihs_product_recommendations_shortcode');
function aihs_product_recommendations_shortcode($atts) {
    $atts = shortcode_atts(array(
        'response_id' => '',
        'limit' => '6',
        'show_price' => 'yes',
        'show_description' => 'yes',
        'columns' => '3'
    ), $atts, 'aihs_product_recommendations');

    $response = null;

    if (!empty($atts['response_id'])) {
        $response = AIHS_Database::get_response(intval($atts['response_id']));
    } else {
        if (is_user_logged_in()) {
            $response = aihs_get_user_response();
        } else {
            $session_id = aihs_get_session_id();
            $response = aihs_get_session_response($session_id);
        }
    }

    if (!$response) {
        return '<div class="aihs-error">Preporuke proizvoda nisu dostupne.</div>';
    }

    $recommended_products = json_decode($response->recommended_products, true) ?: array();

    if (empty($recommended_products)) {
        return '<div class="aihs-notice">Trenutno nema preporučenih proizvoda.</div>';
    }

    // Limit the number of products
    $limit = intval($atts['limit']);
    if ($limit > 0) {
        $recommended_products = array_slice($recommended_products, 0, $limit);
    }

    ob_start();

    // Include the product recommendations template
    include AIHS_PLUGIN_DIR . 'frontend/templates/product-recommendations.php';

    return ob_get_clean();
}

/**
 * Health packages display
 */
add_shortcode('aihs_health_packages', 'aihs_health_packages_shortcode');
function aihs_health_packages_shortcode($atts) {
    $atts = shortcode_atts(array(
        'response_id' => '',
        'package_types' => '2_products,3_products,4_products,6_products',
        'show_savings' => 'yes',
        'show_vip_discount' => 'yes'
    ), $atts, 'aihs_health_packages');

    $response = null;

    if (!empty($atts['response_id'])) {
        $response = AIHS_Database::get_response(intval($atts['response_id']));
    } else {
        if (is_user_logged_in()) {
            $response = aihs_get_user_response();
        } else {
            $session_id = aihs_get_session_id();
            $response = aihs_get_session_response($session_id);
        }
    }

    if (!$response) {
        return '<div class="aihs-error">Paketi nisu dostupni.</div>';
    }

    // Get packages for this response
    $packages = AIHS_Database::get_response_packages($response->id);

    if (empty($packages)) {
        // Generate packages if they don't exist
        if (class_exists('AIHS_Packages')) {
            $packages_handler = new AIHS_Packages();
            $packages = $packages_handler->generate_packages($response);

            if (is_wp_error($packages)) {
                return '<div class="aihs-error">Greška pri kreiranju paketa: ' . $packages->get_error_message() . '</div>';
            }
        } else {
            return '<div class="aihs-error">Sistem paketa nije dostupan.</div>';
        }
    }

    // Filter packages by requested types
    $allowed_types = array_map('trim', explode(',', $atts['package_types']));
    $filtered_packages = array();

    foreach ($packages as $package) {
        if (in_array($package->package_type, $allowed_types)) {
            $filtered_packages[] = $package;
        }
    }

    ob_start();

    // Include the packages template
    include AIHS_PLUGIN_DIR . 'frontend/templates/health-packages.php';

    return ob_get_clean();
}

/**
 * Simple health quiz button/link
 */
add_shortcode('aihs_quiz_button', 'aihs_quiz_button_shortcode');
function aihs_quiz_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'Započni Analizu Zdravlja',
        'url' => '',
        'class' => 'aihs-quiz-button',
        'style' => 'button',
        'target' => '_self'
    ), $atts, 'aihs_quiz_button');

    $url = !empty($atts['url']) ? $atts['url'] : '#aihs-health-quiz';

    $classes = array('aihs-quiz-btn');
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    if ($atts['style'] === 'button') {
        $classes[] = 'aihs-btn-primary';
    }

    $html = sprintf(
        '<a href="%s" class="%s" target="%s">%s</a>',
        esc_url($url),
        esc_attr(implode(' ', $classes)),
        esc_attr($atts['target']),
        esc_html($atts['text'])
    );

    return $html;
}

/**
 * User health dashboard (for logged-in users)
 */
add_shortcode('aihs_user_dashboard', 'aihs_user_dashboard_shortcode');
function aihs_user_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div class="aihs-notice">Molimo prijavite se da vidite vaš zdravstveni dashboard.</div>';
    }

    $atts = shortcode_atts(array(
        'show_history' => 'yes',
        'show_recommendations' => 'yes',
        'history_limit' => '5'
    ), $atts, 'aihs_user_dashboard');

    $user_id = get_current_user_id();

    // Get user's health responses
    global $wpdb;
    $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

    $responses = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
        $user_id,
        intval($atts['history_limit'])
    ));

    ob_start();

    // Include the dashboard template
    include AIHS_PLUGIN_DIR . 'frontend/templates/user-dashboard.php';

    return ob_get_clean();
}

/**
 * Health statistics (admin or public)
 */
add_shortcode('aihs_health_stats', 'aihs_health_stats_shortcode');
function aihs_health_stats_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'public', // public or admin
        'show_total' => 'yes',
        'show_average_score' => 'yes',
        'show_distribution' => 'yes'
    ), $atts, 'aihs_health_stats');

    // Check permissions for admin stats
    if ($atts['type'] === 'admin' && !current_user_can('manage_options')) {
        return '<div class="aihs-error">Nemate dozvolu za pristup ovim statistikama.</div>';
    }

    $stats = AIHS_Database::get_statistics();

    ob_start();

    // Include the stats template
    include AIHS_PLUGIN_DIR . 'frontend/templates/health-stats.php';

    return ob_get_clean();
}

?>