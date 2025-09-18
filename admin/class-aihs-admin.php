<?php
/**
 * Admin interface for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this, 'admin_notices'));

        // AJAX handlers
        add_action('wp_ajax_aihs_reorder_questions', array($this, 'ajax_reorder_questions'));
        add_action('wp_ajax_aihs_preview_score', array($this, 'ajax_preview_score'));
        add_action('wp_ajax_aihs_cleanup_packages', array($this, 'ajax_cleanup_packages'));
        add_action('wp_ajax_aihs_regenerate_prices', array($this, 'ajax_regenerate_prices'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI Health Savetnik', 'ai-health-savetnik'),
            __('AI Health Savetnik', 'ai-health-savetnik'),
            'manage_options',
            'aihs-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-heart',
            30
        );

        // Dashboard (same as main menu)
        add_submenu_page(
            'aihs-dashboard',
            __('Dashboard', 'ai-health-savetnik'),
            __('Dashboard', 'ai-health-savetnik'),
            'manage_options',
            'aihs-dashboard',
            array($this, 'dashboard_page')
        );

        // Questions
        add_submenu_page(
            'aihs-dashboard',
            __('Health Questions', 'ai-health-savetnik'),
            __('Questions', 'ai-health-savetnik'),
            'manage_options',
            'aihs-questions',
            array($this, 'questions_page')
        );

        // Responses/Results
        add_submenu_page(
            'aihs-dashboard',
            __('Quiz Results', 'ai-health-savetnik'),
            __('Results', 'ai-health-savetnik'),
            'manage_options',
            'aihs-results',
            array($this, 'results_page')
        );

        // Packages
        add_submenu_page(
            'aihs-dashboard',
            __('Health Packages', 'ai-health-savetnik'),
            __('Packages', 'ai-health-savetnik'),
            'manage_options',
            'aihs-packages',
            array($this, 'packages_page')
        );

        // Reports
        add_submenu_page(
            'aihs-dashboard',
            __('Reports & Analytics', 'ai-health-savetnik'),
            __('Reports', 'ai-health-savetnik'),
            'manage_options',
            'aihs-reports',
            array($this, 'reports_page')
        );

        // Settings
        add_submenu_page(
            'aihs-dashboard',
            __('Settings', 'ai-health-savetnik'),
            __('Settings', 'ai-health-savetnik'),
            'manage_options',
            'aihs-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();

        // Handle admin actions
        $this->handle_admin_actions();
    }

    /**
     * Register plugin settings
     */
    private function register_settings() {
        // OpenAI Settings
        register_setting('aihs_openai_settings', 'aihs_openai_settings', array(
            'sanitize_callback' => array($this, 'sanitize_openai_settings')
        ));

        // UI Settings
        register_setting('aihs_ui_settings', 'aihs_ui_settings', array(
            'sanitize_callback' => array($this, 'sanitize_ui_settings')
        ));

        // Score Categories
        register_setting('aihs_score_categories', 'aihs_score_categories', array(
            'sanitize_callback' => array($this, 'sanitize_score_categories')
        ));

        // Package Discounts
        register_setting('aihs_package_discounts', 'aihs_package_discounts', array(
            'sanitize_callback' => array($this, 'sanitize_package_discounts')
        ));

        // Health Questions
        register_setting('aihs_health_questions', 'aihs_health_questions', array(
            'sanitize_callback' => array($this, 'sanitize_health_questions')
        ));
    }

    /**
     * Handle admin actions
     */
    private function handle_admin_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'aihs-') !== 0) {
            return;
        }

        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        switch ($action) {
            case 'test_openai':
                $this->handle_test_openai();
                break;

            case 'export_csv':
                $this->handle_export_csv();
                break;

            case 'delete_response':
                $this->handle_delete_response();
                break;

            case 'regenerate_analysis':
                $this->handle_regenerate_analysis();
                break;

            case 'save_question':
                $this->handle_save_question();
                break;

            case 'delete_question':
                $this->handle_delete_question();
                break;

            case 'reorder_questions':
                $this->handle_reorder_questions();
                break;

            case 'preview_score':
                $this->handle_preview_score();
                break;

            case 'cleanup_packages':
                $this->handle_cleanup_packages();
                break;

            case 'regenerate_prices':
                $this->handle_regenerate_prices();
                break;

            case 'generate_report':
                $this->handle_generate_report();
                break;
        }
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = AIHS_Database::get_statistics();

        // Get recent responses
        $recent_responses = AIHS_Database::get_responses(array(
            'per_page' => 10,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ));

        // Get score trends if scoring class exists
        $score_trends = array();
        if (class_exists('AIHS_Scoring')) {
            $scoring = new AIHS_Scoring();
            $score_stats = $scoring->get_score_statistics();
            $score_trends = $score_stats['trend'] ?? array();
        }

        include AIHS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Questions management page
     */
    public function questions_page() {
        $questions = get_option('aihs_health_questions', array());
        $action = $_GET['action'] ?? 'list';
        $question_id = $_GET['question_id'] ?? 0;

        if ($action === 'edit' && $question_id) {
            $question = null;
            foreach ($questions as $q) {
                if ($q['id'] == $question_id) {
                    $question = $q;
                    break;
                }
            }

            if ($question) {
                include AIHS_PLUGIN_DIR . 'admin/views/question-edit.php';
                return;
            }
        } elseif ($action === 'add') {
            $question = array(
                'id' => 0,
                'text' => '',
                'type' => 'binary',
                'weight' => 10,
                'sub_question' => '',
                'intensity_options' => array('Blago', 'Umereno', 'Jako'),
                'intensity_weights' => array(5, 10, 15),
                'recommended_products' => array(),
                'ai_hint' => '',
                'priority' => count($questions) + 1
            );

            include AIHS_PLUGIN_DIR . 'admin/views/question-edit.php';
            return;
        }

        include AIHS_PLUGIN_DIR . 'admin/views/questions-list.php';
    }

    /**
     * Results page
     */
    public function results_page() {
        $action = $_GET['action'] ?? 'list';
        $response_id = $_GET['response_id'] ?? 0;

        if ($action === 'view' && $response_id) {
            $response = AIHS_Database::get_response($response_id);
            if ($response) {
                include AIHS_PLUGIN_DIR . 'admin/views/response-detail.php';
                return;
            }
        }

        // List view
        $page = $_GET['paged'] ?? 1;
        $search = $_GET['s'] ?? '';
        $status = $_GET['status'] ?? '';

        $args = array(
            'page' => $page,
            'per_page' => 20,
            'search' => $search,
            'status' => $status
        );

        $responses = AIHS_Database::get_responses($args);
        $total_responses = AIHS_Database::get_responses_count($args);

        include AIHS_PLUGIN_DIR . 'admin/views/results-list.php';
    }

    /**
     * Packages page
     */
    public function packages_page() {
        include AIHS_PLUGIN_DIR . 'admin/views/packages.php';
    }

    /**
     * Reports page
     */
    public function reports_page() {
        $stats = AIHS_Database::get_statistics();

        // Get score distribution data
        $score_distribution = array();
        if (class_exists('AIHS_Scoring')) {
            $scoring = new AIHS_Scoring();
            $score_stats = $scoring->get_score_statistics();
            $score_distribution = $score_stats['distribution'] ?? array();
        }

        include AIHS_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        $active_tab = $_GET['tab'] ?? 'general';

        $openai_settings = get_option('aihs_openai_settings', array());
        $ui_settings = get_option('aihs_ui_settings', array());
        $score_categories = get_option('aihs_score_categories', array());
        $package_discounts = get_option('aihs_package_discounts', array());

        include AIHS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if OpenAI is configured
        if (!aihs_is_openai_configured() && isset($_GET['page']) && strpos($_GET['page'], 'aihs-') === 0) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>AI Health Savetnik:</strong> ';
            echo __('OpenAI API key is not configured. AI analysis features will not work.', 'ai-health-savetnik');
            echo ' <a href="' . admin_url('admin.php?page=aihs-settings&tab=openai') . '">' . __('Configure now', 'ai-health-savetnik') . '</a>';
            echo '</p>';
            echo '</div>';
        }

        // Show success/error messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $type = $_GET['type'] ?? 'success';

            $messages = array(
                'openai_tested' => __('OpenAI connection test successful!', 'ai-health-savetnik'),
                'openai_failed' => __('OpenAI connection test failed. Please check your API key.', 'ai-health-savetnik'),
                'settings_saved' => __('Settings saved successfully!', 'ai-health-savetnik'),
                'question_saved' => __('Question saved successfully!', 'ai-health-savetnik'),
                'question_deleted' => __('Question deleted successfully!', 'ai-health-savetnik'),
                'response_deleted' => __('Response deleted successfully!', 'ai-health-savetnik'),
                'export_completed' => __('Export completed successfully!', 'ai-health-savetnik'),
                'analysis_regenerated' => __('AI analysis regenerated successfully!', 'ai-health-savetnik')
            );

            if (isset($messages[$message])) {
                $class = $type === 'error' ? 'notice-error' : 'notice-success';
                echo '<div class="notice ' . $class . ' is-dismissible">';
                echo '<p>' . $messages[$message] . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Handle OpenAI connection test
     */
    private function handle_test_openai() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_test_openai')) {
            wp_die('Security check failed');
        }

        if (class_exists('AIHS_OpenAI')) {
            $openai = new AIHS_OpenAI();
            $result = $openai->test_connection();

            if (is_wp_error($result)) {
                $redirect_url = add_query_arg(array(
                    'page' => 'aihs-settings',
                    'tab' => 'openai',
                    'message' => 'openai_failed',
                    'type' => 'error'
                ), admin_url('admin.php'));
            } else {
                $redirect_url = add_query_arg(array(
                    'page' => 'aihs-settings',
                    'tab' => 'openai',
                    'message' => 'openai_tested'
                ), admin_url('admin.php'));
            }

            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Handle CSV export
     */
    private function handle_export_csv() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_export_csv')) {
            wp_die('Security check failed');
        }

        $responses = AIHS_Database::get_responses(array('per_page' => -1));
        $csv_data = aihs_generate_csv_export($responses);

        $filename = 'health_quiz_export_' . date('Y-m-d') . '.csv';

        aihs_send_csv_headers($filename);
        aihs_output_csv($csv_data);
        exit;
    }

    /**
     * Handle response deletion
     */
    private function handle_delete_response() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_delete_response')) {
            wp_die('Security check failed');
        }

        $response_id = intval($_GET['response_id'] ?? 0);
        if ($response_id > 0) {
            AIHS_Database::delete_response($response_id);
        }

        $redirect_url = add_query_arg(array(
            'page' => 'aihs-results',
            'message' => 'response_deleted'
        ), admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle AI analysis regeneration
     */
    private function handle_regenerate_analysis() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_regenerate_analysis')) {
            wp_die('Security check failed');
        }

        $response_id = intval($_GET['response_id'] ?? 0);
        if ($response_id > 0) {
            $response = AIHS_Database::get_response($response_id);
            if ($response && class_exists('AIHS_OpenAI')) {
                $openai = new AIHS_OpenAI();
                $analysis_result = $openai->generate_analysis($response);

                if (!is_wp_error($analysis_result)) {
                    AIHS_Database::update_response($response_id, array(
                        'ai_analysis' => $analysis_result['analysis'],
                        'completion_status' => 'analysis_completed',
                        'recommended_products' => wp_json_encode($analysis_result['recommended_products'])
                    ));

                    $redirect_url = add_query_arg(array(
                        'page' => 'aihs-results',
                        'action' => 'view',
                        'response_id' => $response_id,
                        'message' => 'analysis_regenerated'
                    ), admin_url('admin.php'));
                } else {
                    $redirect_url = add_query_arg(array(
                        'page' => 'aihs-results',
                        'message' => 'openai_failed',
                        'type' => 'error'
                    ), admin_url('admin.php'));
                }
            } else {
                $redirect_url = add_query_arg(array(
                    'page' => 'aihs-results',
                    'message' => 'openai_failed',
                    'type' => 'error'
                ), admin_url('admin.php'));
            }
        } else {
            $redirect_url = admin_url('admin.php?page=aihs-results');
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle question saving
     */
    private function handle_save_question() {
        if (!wp_verify_nonce($_POST['aihs_question_nonce'] ?? '', 'aihs_save_question')) {
            wp_die('Security check failed');
        }

        $question_id = intval($_POST['question_id'] ?? 0);
        $questions = get_option('aihs_health_questions', array());

        $question_data = array(
            'id' => $question_id ?: (count($questions) > 0 ? max(array_column($questions, 'id')) + 1 : 1),
            'text' => sanitize_textarea_field($_POST['question_text'] ?? ''),
            'type' => sanitize_text_field($_POST['question_type'] ?? 'binary'),
            'weight' => intval($_POST['question_weight'] ?? 10),
            'sub_question' => sanitize_textarea_field($_POST['sub_question'] ?? ''),
            'intensity_options' => array_map('sanitize_text_field', $_POST['intensity_options'] ?? array()),
            'intensity_weights' => array_map('intval', $_POST['intensity_weights'] ?? array()),
            'recommended_products' => array_map('intval', $_POST['recommended_products'] ?? array()),
            'ai_hint' => sanitize_textarea_field($_POST['ai_hint'] ?? ''),
            'priority' => intval($_POST['question_priority'] ?? 999)
        );

        // Remove empty intensity options
        $question_data['intensity_options'] = array_filter($question_data['intensity_options']);
        $question_data['intensity_weights'] = array_slice($question_data['intensity_weights'], 0, count($question_data['intensity_options']));

        if ($question_id > 0) {
            // Update existing question
            foreach ($questions as $index => $question) {
                if ($question['id'] == $question_id) {
                    $questions[$index] = $question_data;
                    break;
                }
            }
        } else {
            // Add new question
            $questions[] = $question_data;
        }

        update_option('aihs_health_questions', $questions);

        $redirect_url = add_query_arg(array(
            'page' => 'aihs-questions',
            'message' => 'question_saved'
        ), admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle question deletion
     */
    private function handle_delete_question() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_delete_question')) {
            wp_die('Security check failed');
        }

        $question_id = intval($_GET['question_id'] ?? 0);
        if ($question_id > 0) {
            $questions = get_option('aihs_health_questions', array());
            $questions = array_filter($questions, function($q) use ($question_id) {
                return $q['id'] != $question_id;
            });
            update_option('aihs_health_questions', array_values($questions));
        }

        $redirect_url = add_query_arg(array(
            'page' => 'aihs-questions',
            'message' => 'question_deleted'
        ), admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle question reordering
     */
    private function handle_reorder_questions() {
        // This is handled via AJAX - see AJAX handlers below
    }

    /**
     * Handle score preview
     */
    private function handle_preview_score() {
        // This is handled via AJAX - see AJAX handlers below
    }

    /**
     * Handle package cleanup
     */
    private function handle_cleanup_packages() {
        // This is handled via AJAX - see AJAX handlers below
    }

    /**
     * Handle price regeneration
     */
    private function handle_regenerate_prices() {
        // This is handled via AJAX - see AJAX handlers below
    }

    /**
     * Handle report generation
     */
    private function handle_generate_report() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'aihs_generate_report')) {
            wp_die('Security check failed');
        }

        $period = sanitize_text_field($_GET['period'] ?? '30');

        // Generate and download report
        $this->generate_analytics_report($period);
        exit;
    }

    /**
     * Generate analytics report
     */
    private function generate_analytics_report($period) {
        global $wpdb;
        $responses_table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        $date_condition = '';
        if ($period !== 'all') {
            $days = intval($period);
            $date_condition = $wpdb->prepare("AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        $responses = $wpdb->get_results(
            "SELECT * FROM $responses_table WHERE completion_status = 'analysis_completed' $date_condition ORDER BY created_at DESC"
        );

        $csv_data = array();
        $csv_data[] = array(
            'Period',
            'Total Responses',
            'Average Score',
            'Completion Rate',
            'Generated At'
        );

        $csv_data[] = array(
            $period === 'all' ? 'All Time' : "Last $period days",
            count($responses),
            count($responses) > 0 ? round(array_sum(array_column($responses, 'calculated_score')) / count($responses), 1) : 0,
            // Calculate completion rate if needed
            '100%', // Since we're only selecting completed responses
            date('Y-m-d H:i:s')
        );

        $filename = 'health_analytics_' . $period . '_' . date('Y-m-d') . '.csv';

        aihs_send_csv_headers($filename);
        aihs_output_csv($csv_data);
    }

    /**
     * Sanitize OpenAI settings
     */
    public function sanitize_openai_settings($input) {
        $sanitized = array();

        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['model'] = sanitize_text_field($input['model'] ?? 'gpt-3.5-turbo');
        $sanitized['temperature'] = floatval($input['temperature'] ?? 0.7);
        $sanitized['max_tokens'] = intval($input['max_tokens'] ?? 1000);

        // Validate API key if provided
        if (!empty($sanitized['api_key'])) {
            $openai = new AIHS_OpenAI();
            $validation = $openai->validate_api_key($sanitized['api_key']);
            if (is_wp_error($validation)) {
                add_settings_error('aihs_openai_settings', 'invalid_api_key', $validation->get_error_message());
                $sanitized['api_key'] = ''; // Clear invalid key
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize UI settings
     */
    public function sanitize_ui_settings($input) {
        return array(
            'form_title' => sanitize_text_field($input['form_title'] ?? ''),
            'form_description' => sanitize_textarea_field($input['form_description'] ?? ''),
            'progress_bar_style' => sanitize_text_field($input['progress_bar_style'] ?? 'gradient'),
            'show_progress_percentage' => !empty($input['show_progress_percentage'])
        );
    }

    /**
     * Sanitize score categories
     */
    public function sanitize_score_categories($input) {
        $sanitized = array();

        if (is_array($input)) {
            foreach ($input as $key => $category) {
                $sanitized[sanitize_key($key)] = array(
                    'label' => sanitize_text_field($category['label'] ?? ''),
                    'min_score' => intval($category['min_score'] ?? 0),
                    'max_score' => intval($category['max_score'] ?? 100),
                    'color' => sanitize_hex_color($category['color'] ?? '#333'),
                    'description' => sanitize_textarea_field($category['description'] ?? '')
                );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize package discounts
     */
    public function sanitize_package_discounts($input) {
        $sanitized = array();

        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $sanitized[sanitize_key($key)] = floatval($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize health questions
     */
    public function sanitize_health_questions($input) {
        $sanitized = array();

        if (is_array($input)) {
            foreach ($input as $question) {
                $sanitized[] = array(
                    'id' => intval($question['id'] ?? 0),
                    'text' => sanitize_textarea_field($question['text'] ?? ''),
                    'type' => sanitize_text_field($question['type'] ?? 'binary'),
                    'weight' => intval($question['weight'] ?? 10),
                    'sub_question' => sanitize_textarea_field($question['sub_question'] ?? ''),
                    'intensity_options' => array_map('sanitize_text_field', $question['intensity_options'] ?? array()),
                    'intensity_weights' => array_map('intval', $question['intensity_weights'] ?? array()),
                    'recommended_products' => array_map('intval', $question['recommended_products'] ?? array()),
                    'ai_hint' => sanitize_textarea_field($question['ai_hint'] ?? ''),
                    'priority' => intval($question['priority'] ?? 999)
                );
            }
        }

        return $sanitized;
    }

    /**
     * AJAX handler for question reordering
     */
    public function ajax_reorder_questions() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aihs_reorder_questions')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $order = $_POST['order'] ?? array();
        $questions = get_option('aihs_health_questions', array());

        foreach ($order as $item) {
            $question_id = intval($item['id']);
            $priority = intval($item['priority']);

            foreach ($questions as &$question) {
                if ($question['id'] == $question_id) {
                    $question['priority'] = $priority;
                    break;
                }
            }
        }

        update_option('aihs_health_questions', $questions);
        wp_send_json_success();
    }

    /**
     * AJAX handler for score preview
     */
    public function ajax_preview_score() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aihs_preview_score')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $answers = $_POST['answers'] ?? array();
        $intensities = $_POST['intensities'] ?? array();

        if (class_exists('AIHS_Scoring')) {
            $scoring = new AIHS_Scoring();
            $result = $scoring->simulate_score($answers, $intensities);
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Scoring class not available');
        }
    }

    /**
     * AJAX handler for package cleanup
     */
    public function ajax_cleanup_packages() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aihs_cleanup_packages')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        // Delete packages older than 30 days that don't have associated WooCommerce orders
        $deleted = $wpdb->query(
            "DELETE FROM $packages_table
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
             AND wc_product_id IS NOT NULL
             AND wc_product_id NOT IN (
                 SELECT DISTINCT meta_value
                 FROM {$wpdb->postmeta}
                 WHERE meta_key = '_product_id'
                 AND post_id IN (
                     SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'shop_order'
                     AND post_status IN ('wc-completed', 'wc-processing')
                 )
             )"
        );

        wp_send_json_success(array('deleted' => $deleted));
    }

    /**
     * AJAX handler for price regeneration
     */
    public function ajax_regenerate_prices() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aihs_regenerate_prices')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        $packages = $wpdb->get_results("SELECT * FROM $packages_table WHERE wc_product_id IS NOT NULL");
        $updated = 0;

        foreach ($packages as $package) {
            $product_ids = json_decode($package->product_ids, true);
            if (empty($product_ids)) continue;

            $original_price = 0;
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product && $product->exists()) {
                    $original_price += floatval($product->get_price());
                }
            }

            $discount_data = aihs_calculate_package_discount(
                $package->package_type,
                $original_price,
                null // No specific user for bulk update
            );

            $wpdb->update(
                $packages_table,
                array(
                    'original_price' => $discount_data['original_price'],
                    'final_price' => $discount_data['final_price'],
                    'discount_percentage' => $discount_data['discount_percentage']
                ),
                array('id' => $package->id)
            );

            // Update WooCommerce product price
            if ($package->wc_product_id) {
                update_post_meta($package->wc_product_id, '_price', $discount_data['final_price']);
                update_post_meta($package->wc_product_id, '_regular_price', $discount_data['original_price']);
                if ($discount_data['discount_percentage'] > 0) {
                    update_post_meta($package->wc_product_id, '_sale_price', $discount_data['final_price']);
                }
            }

            $updated++;
        }

        wp_send_json_success(array('updated' => $updated));
    }
}

error_log('AI Health Savetnik: Admin class loaded');
?>