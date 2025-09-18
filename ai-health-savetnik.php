<?php
/**
 * Plugin Name: AI Health Savetnik
 * Plugin URI: https://github.com/beopop/ai-health-savetnik
 * Description: AI-powered health advisor plugin with OpenAI integration, health quiz, WooCommerce product recommendations, and dynamic package creation.
 * Version: 1.0.0
 * Author: AI Health Team
 * Author URI: https://github.com/beopop
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: ai-health-savetnik
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * Requires Plugins: woocommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIHS_VERSION', '1.0.0');
define('AIHS_PLUGIN_FILE', __FILE__);
define('AIHS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIHS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIHS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database table names
define('AIHS_RESPONSES_TABLE', 'aihs_responses');
define('AIHS_PACKAGES_TABLE', 'aihs_packages');

/**
 * Main plugin class
 */
class AI_Health_Savetnik {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Declare WooCommerce HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_woocommerce_compatibility'));

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('ai-health-savetnik', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Include required files
        $this->includes();

        // WooCommerce integration
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-woocommerce.php';
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-package-generator.php';

        // Initialize components
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-database.php';
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-rest-api.php';
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-openai.php';
        require_once AIHS_PLUGIN_DIR . 'includes/class-aihs-scoring.php';
        require_once AIHS_PLUGIN_DIR . 'includes/aihs-functions.php';

        // Admin classes
        if (is_admin()) {
            require_once AIHS_PLUGIN_DIR . 'admin/class-aihs-admin.php';
        }

        // Shortcodes
        require_once AIHS_PLUGIN_DIR . 'includes/aihs-shortcodes.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Initialize components
        if (class_exists('AIHS_Database')) {
            new AIHS_Database();
        }

        if (class_exists('AIHS_REST_API')) {
            new AIHS_REST_API();
        }

        if (is_admin() && class_exists('AIHS_Admin')) {
            new AIHS_Admin();
        }

        if (!is_admin() && class_exists('AIHS_Frontend')) {
            // Frontend will be initialized when needed
        }

        // WooCommerce integration
        add_action('woocommerce_init', array($this, 'woocommerce_integration'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('AIHS_Database')) {
            AIHS_Database::create_tables();
        }

        // Create default questions
        $this->create_default_questions();

        // Set default options
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create default health questions
     */
    private function create_default_questions() {
        $existing_questions = get_option('aihs_health_questions', array());

        if (empty($existing_questions)) {
            $default_questions = array(
                array(
                    'id' => 1,
                    'text' => 'Da li imate problema sa spavanjem?',
                    'type' => 'binary',
                    'weight' => 10,
                    'sub_question' => 'Koliko često imate problema sa spavanjem?',
                    'intensity_options' => array('Retko', 'Povremeno', 'Često'),
                    'intensity_weights' => array(5, 10, 15),
                    'recommended_products' => array(),
                    'ai_hint' => 'Sleep related issues, insomnia, sleep quality',
                    'priority' => 1
                ),
                array(
                    'id' => 2,
                    'text' => 'Da li često osećate umor tokom dana?',
                    'type' => 'binary',
                    'weight' => 8,
                    'sub_question' => 'Koliko je izražen vaš umor?',
                    'intensity_options' => array('Blago', 'Umereno', 'Jako'),
                    'intensity_weights' => array(5, 10, 15),
                    'recommended_products' => array(),
                    'ai_hint' => 'Fatigue, energy levels, tiredness during day',
                    'priority' => 2
                ),
                array(
                    'id' => 3,
                    'text' => 'Da li imate glavobolje?',
                    'type' => 'binary',
                    'weight' => 12,
                    'sub_question' => 'Koliko su česte vaše glavobolje?',
                    'intensity_options' => array('Retko', 'Nedeljno', 'Svakodnevno'),
                    'intensity_weights' => array(5, 10, 20),
                    'recommended_products' => array(),
                    'ai_hint' => 'Headaches, migraines, head pain frequency',
                    'priority' => 3
                ),
                array(
                    'id' => 4,
                    'text' => 'Da li imate probleme sa varenjem?',
                    'type' => 'binary',
                    'weight' => 10,
                    'sub_question' => 'Kakvi su vaši digestivni problemi?',
                    'intensity_options' => array('Blagi', 'Umereni', 'Ozbiljni'),
                    'intensity_weights' => array(5, 12, 18),
                    'recommended_products' => array(),
                    'ai_hint' => 'Digestive issues, stomach problems, gut health',
                    'priority' => 4
                ),
                array(
                    'id' => 5,
                    'text' => 'Da li osećate stres ili anksioznost?',
                    'type' => 'binary',
                    'weight' => 15,
                    'sub_question' => 'Koliko je izražen vaš stres?',
                    'intensity_options' => array('Povremeno', 'Često', 'Konstantno'),
                    'intensity_weights' => array(8, 15, 25),
                    'recommended_products' => array(),
                    'ai_hint' => 'Stress, anxiety, mental health, psychological wellbeing',
                    'priority' => 5
                ),
                array(
                    'id' => 6,
                    'text' => 'Da li imate bolove u leđima?',
                    'type' => 'binary',
                    'weight' => 8,
                    'sub_question' => 'Koliko su jaki bolovi u leđima?',
                    'intensity_options' => array('Blagi', 'Umereni', 'Jaki'),
                    'intensity_weights' => array(5, 10, 15),
                    'recommended_products' => array(),
                    'ai_hint' => 'Back pain, spinal issues, posture problems',
                    'priority' => 6
                ),
                array(
                    'id' => 7,
                    'text' => 'Da li imate probleme sa koncentracijom?',
                    'type' => 'binary',
                    'weight' => 10,
                    'sub_question' => 'Koliko često ne možete da se koncentrišete?',
                    'intensity_options' => array('Retko', 'Ponekad', 'Često'),
                    'intensity_weights' => array(5, 10, 15),
                    'recommended_products' => array(),
                    'ai_hint' => 'Concentration problems, focus issues, cognitive function',
                    'priority' => 7
                ),
                array(
                    'id' => 8,
                    'text' => 'Da li često osećate napetost u mišićima?',
                    'type' => 'binary',
                    'weight' => 7,
                    'sub_question' => 'Gde najčešće osećate napetost?',
                    'intensity_options' => array('Vrat/ramena', 'Leđa', 'Celo telo'),
                    'intensity_weights' => array(5, 10, 15),
                    'recommended_products' => array(),
                    'ai_hint' => 'Muscle tension, physical stress, body stiffness',
                    'priority' => 8
                ),
                array(
                    'id' => 9,
                    'text' => 'Da li imate problema sa alergijama?',
                    'type' => 'binary',
                    'weight' => 6,
                    'sub_question' => 'Kakve alergije imate?',
                    'intensity_options' => array('Sezonske', 'Hrana', 'Ostalo'),
                    'intensity_weights' => array(5, 10, 8),
                    'recommended_products' => array(),
                    'ai_hint' => 'Allergies, immune system, sensitivities',
                    'priority' => 9
                ),
                array(
                    'id' => 10,
                    'text' => 'Da li osećate da vam nedostaje energije?',
                    'type' => 'binary',
                    'weight' => 12,
                    'sub_question' => 'Kada najčešće osećate nedostatak energije?',
                    'intensity_options' => array('Ujutru', 'Posle podne', 'Ceo dan'),
                    'intensity_weights' => array(8, 10, 18),
                    'recommended_products' => array(),
                    'ai_hint' => 'Energy levels, vitality, overall wellness',
                    'priority' => 10
                )
            );

            update_option('aihs_health_questions', $default_questions);
        }
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        // Score categories
        $default_categories = array(
            'excellent' => array(
                'label' => 'Odlično',
                'min_score' => 80,
                'max_score' => 100,
                'color' => '#28a745',
                'description' => 'Vaše zdravlje je u odličnom stanju!'
            ),
            'good' => array(
                'label' => 'Dobro',
                'min_score' => 60,
                'max_score' => 79,
                'color' => '#ffc107',
                'description' => 'Vaše zdravlje je dobro, ali može biti bolje.'
            ),
            'moderate' => array(
                'label' => 'Umereno',
                'min_score' => 40,
                'max_score' => 59,
                'color' => '#fd7e14',
                'description' => 'Potrebno je obratiti pažnju na zdravlje.'
            ),
            'risky' => array(
                'label' => 'Rizično',
                'min_score' => 0,
                'max_score' => 39,
                'color' => '#dc3545',
                'description' => 'Preporučujemo konsultaciju sa stručnjakom.'
            )
        );

        add_option('aihs_score_categories', $default_categories);

        // Default package discounts
        $default_discounts = array(
            '2_products' => 10,
            '3_products' => 12,
            '4_products' => 16,
            '6_products' => 20,
            'vip_additional' => 5
        );

        add_option('aihs_package_discounts', $default_discounts);

        // Default OpenAI settings
        $default_openai = array(
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000
        );

        add_option('aihs_openai_settings', $default_openai);

        // UI Settings
        $default_ui = array(
            'form_title' => 'Analiza Zdravstvenog Stanja',
            'form_description' => 'Popunite formu da biste dobili personalizovane preporuke za vaše zdravlje',
            'progress_bar_style' => 'gradient',
            'show_progress_percentage' => true
        );

        add_option('aihs_ui_settings', $default_ui);
    }

    /**
     * WooCommerce integration
     */
    public function woocommerce_integration() {
        // Add product metaboxes
        if (class_exists('AIHS_Products')) {
            new AIHS_Products();
        }

        // Package integration
        if (class_exists('AIHS_Packages')) {
            new AIHS_Packages();
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on pages with our shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'aihs_health_quiz')) {
            return;
        }

        wp_enqueue_style(
            'aihs-frontend',
            AIHS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            AIHS_VERSION
        );

        wp_enqueue_script(
            'aihs-frontend',
            AIHS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            AIHS_VERSION,
            true
        );

        wp_localize_script('aihs-frontend', 'aihs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('aihs/v1/'),
            'nonce' => wp_create_nonce('aihs_nonce'),
            'questions' => get_option('aihs_health_questions', array()),
            'ui_settings' => get_option('aihs_ui_settings', array())
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'aihs') === false) {
            return;
        }

        wp_enqueue_style(
            'aihs-admin',
            AIHS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AIHS_VERSION
        );

        wp_enqueue_script(
            'aihs-admin',
            AIHS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            AIHS_VERSION,
            true
        );

        wp_localize_script('aihs-admin', 'aihsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aihs_admin_nonce')
        ));
    }

    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_woocommerce_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error">';
        echo '<p><strong>AI Health Savetnik:</strong> ' . __('WooCommerce plugin is required and must be activated.', 'ai-health-savetnik') . '</p>';
        echo '</div>';
    }
}

// Initialize plugin
AI_Health_Savetnik::get_instance();
?>