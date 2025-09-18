<?php
/**
 * Helper functions for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get or create session ID for guest users
 */
function aihs_get_session_id() {
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }

    // Start session if not already started
    if (!session_id()) {
        session_start();
    }

    // Get existing session ID or create new one
    if (!isset($_SESSION['aihs_session_id'])) {
        $_SESSION['aihs_session_id'] = 'guest_' . wp_generate_password(16, false, false) . '_' . time();
    }

    return $_SESSION['aihs_session_id'];
}

/**
 * Get current user's latest response
 */
function aihs_get_user_response($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return null;
    }

    return AIHS_Database::get_user_latest_response($user_id);
}

/**
 * Get response by session ID
 */
function aihs_get_session_response($session_id = null) {
    if (!$session_id) {
        $session_id = aihs_get_session_id();
    }

    return AIHS_Database::get_response_by_session($session_id);
}

/**
 * Format health score with category styling
 */
function aihs_format_score($score, $include_category = true) {
    $categories = get_option('aihs_score_categories', array());
    $category_data = null;

    foreach ($categories as $key => $cat) {
        if ($score >= $cat['min_score'] && $score <= $cat['max_score']) {
            $category_data = $cat;
            break;
        }
    }

    $output = '<span class="aihs-score" style="color: ' . ($category_data['color'] ?? '#333') . '">';
    $output .= $score . '/100';

    if ($include_category && $category_data) {
        $output .= ' <span class="aihs-category">(' . $category_data['label'] . ')</span>';
    }

    $output .= '</span>';

    return $output;
}

/**
 * Get score category data
 */
function aihs_get_score_category($score) {
    $categories = get_option('aihs_score_categories', array());

    foreach ($categories as $key => $category) {
        if ($score >= $category['min_score'] && $score <= $category['max_score']) {
            return array(
                'key' => $key,
                'data' => $category
            );
        }
    }

    return null;
}

/**
 * Generate unique response ID for public sharing
 */
function aihs_generate_public_id($response_id) {
    return wp_hash($response_id . NONCE_SALT . 'aihs_public');
}

/**
 * Get response by public ID
 */
function aihs_get_response_by_public_id($public_id) {
    global $wpdb;
    $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

    $responses = $wpdb->get_results("SELECT * FROM $table WHERE completion_status = 'analysis_completed'");

    foreach ($responses as $response) {
        if (aihs_generate_public_id($response->id) === $public_id) {
            return $response;
        }
    }

    return null;
}

/**
 * Sanitize quiz data
 */
function aihs_sanitize_quiz_data($data) {
    if (!is_array($data)) {
        return array();
    }

    $sanitized = array();

    foreach ($data as $key => $value) {
        $key = intval($key);
        $value = sanitize_text_field($value);

        if ($key > 0 && !empty($value)) {
            $sanitized[$key] = $value;
        }
    }

    return $sanitized;
}

/**
 * Get health questions
 */
function aihs_get_health_questions() {
    $questions = get_option('aihs_health_questions', array());

    // Sort by priority
    usort($questions, function($a, $b) {
        return ($a['priority'] ?? 999) - ($b['priority'] ?? 999);
    });

    return $questions;
}

/**
 * Get question by ID
 */
function aihs_get_question($question_id) {
    $questions = aihs_get_health_questions();

    foreach ($questions as $question) {
        if ($question['id'] == $question_id) {
            return $question;
        }
    }

    return null;
}

/**
 * Check if user has VIP status
 */
function aihs_is_vip_user($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Check if user has VIP role or meta
    $user = get_user_by('ID', $user_id);

    if (!$user) {
        return false;
    }

    // Check for VIP role
    if (in_array('vip', $user->roles)) {
        return true;
    }

    // Check for VIP meta
    $vip_status = get_user_meta($user_id, 'is_vip', true);
    if ($vip_status === 'yes' || $vip_status === '1' || $vip_status === true) {
        return true;
    }

    // Check WooCommerce customer purchase history or other criteria
    if (function_exists('wc_get_customer_total_spent')) {
        $total_spent = wc_get_customer_total_spent($user_id);
        $vip_threshold = get_option('aihs_vip_threshold', 1000); // Default 1000 currency units

        if ($total_spent >= $vip_threshold) {
            return true;
        }
    }

    return false;
}

/**
 * Calculate package discount
 */
function aihs_calculate_package_discount($package_type, $original_price, $user_id = null) {
    $discounts = get_option('aihs_package_discounts', array());
    $discount_percentage = $discounts[$package_type] ?? 0;

    // Add VIP discount if applicable
    if (aihs_is_vip_user($user_id)) {
        $vip_discount = $discounts['vip_additional'] ?? 0;
        $discount_percentage += $vip_discount;
    }

    $discount_amount = ($original_price * $discount_percentage) / 100;
    $final_price = $original_price - $discount_amount;

    return array(
        'original_price' => $original_price,
        'discount_percentage' => $discount_percentage,
        'discount_amount' => $discount_amount,
        'final_price' => $final_price,
        'savings' => $discount_amount
    );
}

/**
 * Get package types configuration
 */
function aihs_get_package_types() {
    return array(
        '2_products' => array(
            'label' => '2 Proizvoda',
            'description' => 'Osnovni paket sa 2 proizvoda',
            'product_count' => 2
        ),
        '3_products' => array(
            'label' => '3 Proizvoda',
            'description' => 'Standardni paket sa 3 proizvoda',
            'product_count' => 3
        ),
        '4_products' => array(
            'label' => '4 Proizvoda',
            'description' => 'Prošireni paket sa 4 proizvoda',
            'product_count' => 4
        ),
        '6_products' => array(
            'label' => '6 Proizvoda',
            'description' => 'Kompletni paket sa 6 proizvoda',
            'product_count' => 6
        )
    );
}

/**
 * Format currency amount
 */
function aihs_format_price($amount) {
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }

    return number_format($amount, 2) . ' RSD';
}

/**
 * Log plugin activity
 */
function aihs_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("AIHS [{$level}]: {$message}");
    }
}

/**
 * Get plugin settings
 */
function aihs_get_setting($key, $default = null) {
    $settings = get_option('aihs_settings', array());
    return $settings[$key] ?? $default;
}

/**
 * Update plugin setting
 */
function aihs_update_setting($key, $value) {
    $settings = get_option('aihs_settings', array());
    $settings[$key] = $value;
    return update_option('aihs_settings', $settings);
}

/**
 * Check if OpenAI is configured
 */
function aihs_is_openai_configured() {
    $settings = get_option('aihs_openai_settings', array());
    return !empty($settings['api_key']);
}

/**
 * Get localized strings for JavaScript
 */
function aihs_get_js_strings() {
    return array(
        'saving' => __('Čuvanje...', 'ai-health-savetnik'),
        'saved' => __('Sačuvano', 'ai-health-savetnik'),
        'error' => __('Greška', 'ai-health-savetnik'),
        'network_error' => __('Greška mreže', 'ai-health-savetnik'),
        'required_field' => __('Ovo polje je obavezno', 'ai-health-savetnik'),
        'invalid_email' => __('Unesite validnu email adresu', 'ai-health-savetnik'),
        'loading' => __('Učitavanje...', 'ai-health-savetnik'),
        'confirm_action' => __('Da li ste sigurni?', 'ai-health-savetnik'),
        'analysis_generating' => __('Generiše se analiza...', 'ai-health-savetnik'),
        'packages_generating' => __('Kreiraju se paketi...', 'ai-health-savetnik')
    );
}

/**
 * Validate email address
 */
function aihs_validate_email($email) {
    return is_email($email);
}

/**
 * Validate phone number
 */
function aihs_validate_phone($phone) {
    // Basic phone validation for Serbian numbers
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    if (empty($phone)) {
        return false;
    }

    // Allow various formats
    if (preg_match('/^(\+381|0)[0-9]{8,9}$/', $phone)) {
        return true;
    }

    return false;
}

/**
 * Generate CSV export data
 */
function aihs_generate_csv_export($responses) {
    $csv_data = array();

    // Headers
    $headers = array(
        'ID',
        'Datum',
        'Ime',
        'Prezime',
        'Email',
        'Telefon',
        'Godine',
        'Pol',
        'Skor',
        'Kategorija',
        'Status',
        'Odgovori',
        'AI Analiza'
    );

    $csv_data[] = $headers;

    foreach ($responses as $response) {
        $answers = json_decode($response->answers, true) ?: array();
        $answers_text = '';

        $questions = aihs_get_health_questions();
        foreach ($answers as $question_id => $answer) {
            $question = aihs_get_question($question_id);
            if ($question) {
                $answers_text .= $question['text'] . ': ' . $answer . '; ';
            }
        }

        $row = array(
            $response->id,
            $response->created_at,
            $response->first_name,
            $response->last_name,
            $response->email,
            $response->phone,
            $response->age ?: '',
            $response->gender ?: '',
            $response->calculated_score,
            $response->score_category,
            $response->completion_status,
            trim($answers_text, '; '),
            strip_tags($response->ai_analysis ?: '')
        );

        $csv_data[] = $row;
    }

    return $csv_data;
}

/**
 * Send CSV download headers
 */
function aihs_send_csv_headers($filename = 'health_quiz_export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/**
 * Output CSV data
 */
function aihs_output_csv($data) {
    $output = fopen('php://output', 'w');

    // Add BOM for proper UTF-8 encoding in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    foreach ($data as $row) {
        fputcsv($output, $row, ',', '"');
    }

    fclose($output);
}

/**
 * Clean up expired sessions and data
 */
function aihs_cleanup_expired_data() {
    // Clean up expired packages
    if (class_exists('AIHS_Database')) {
        AIHS_Database::cleanup_expired_packages();
    }

    // Clean up old draft responses (older than 7 days)
    global $wpdb;
    $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

    $wpdb->query(
        "DELETE FROM $table
         WHERE completion_status = 'draft'
         AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );

    aihs_log('Expired data cleanup completed');
}

// Schedule cleanup if not already scheduled
if (!wp_next_scheduled('aihs_cleanup_expired_data')) {
    wp_schedule_event(time(), 'daily', 'aihs_cleanup_expired_data');
}

add_action('aihs_cleanup_expired_data', 'aihs_cleanup_expired_data');
?>