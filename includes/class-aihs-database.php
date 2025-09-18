<?php
/**
 * Database handler for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_Database {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_upgrade_database'));
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Responses table
        $responses_table = $wpdb->prefix . AIHS_RESPONSES_TABLE;
        $responses_sql = "CREATE TABLE $responses_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(100) NOT NULL,

            -- Personal data (WooCommerce mapping)
            first_name varchar(100) NOT NULL DEFAULT '',
            last_name varchar(100) NOT NULL DEFAULT '',
            email varchar(100) NOT NULL DEFAULT '',
            phone varchar(20) NOT NULL DEFAULT '',
            address varchar(255) NOT NULL DEFAULT '',
            city varchar(100) NOT NULL DEFAULT '',
            postcode varchar(20) NOT NULL DEFAULT '',
            country varchar(100) NOT NULL DEFAULT '',
            age int(3) DEFAULT NULL,
            gender enum('male', 'female', 'other') DEFAULT NULL,

            -- Quiz data
            answers longtext DEFAULT NULL COMMENT 'JSON: question_id => answer_value',
            intensity_data longtext DEFAULT NULL COMMENT 'JSON: question_id => intensity_value',
            calculated_score int(3) DEFAULT 0,
            score_category varchar(50) DEFAULT '',

            -- AI Analysis
            ai_analysis longtext DEFAULT NULL,
            ai_recommendations longtext DEFAULT NULL,
            recommended_products longtext DEFAULT NULL COMMENT 'JSON: array of product IDs',
            suggested_packages longtext DEFAULT NULL COMMENT 'JSON: array of package data',

            -- Status and tracking
            completion_status enum('draft', 'form_completed', 'quiz_completed', 'analysis_completed') DEFAULT 'draft',
            form_step int(2) DEFAULT 1,
            quiz_progress int(3) DEFAULT 0,

            -- GDPR and consent
            gdpr_consent tinyint(1) DEFAULT 0,
            privacy_policy_accepted tinyint(1) DEFAULT 0,
            email_notifications_consent tinyint(1) DEFAULT 0,

            -- Timestamps
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,

            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_email (email),
            INDEX idx_completion_status (completion_status),
            INDEX idx_created_at (created_at),
            INDEX idx_score (calculated_score)
        ) $charset_collate;";

        // Packages table
        $packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;
        $packages_sql = "CREATE TABLE $packages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            response_id bigint(20) NOT NULL,

            -- Package details
            package_type enum('2_products', '3_products', '4_products', '6_products') NOT NULL,
            package_theme varchar(100) NOT NULL DEFAULT '' COMMENT 'e.g. Detox, Immunity, Digestive',
            package_name varchar(255) NOT NULL DEFAULT '',
            package_description text DEFAULT NULL,

            -- Products and pricing
            product_ids longtext NOT NULL COMMENT 'JSON: array of WC product IDs',
            original_price decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_percentage decimal(5,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            final_price decimal(10,2) NOT NULL DEFAULT 0.00,

            -- VIP discounts
            vip_discount_applied tinyint(1) DEFAULT 0,
            vip_discount_percentage decimal(5,2) DEFAULT 0.00,
            vip_discount_amount decimal(10,2) DEFAULT 0.00,

            -- WooCommerce integration
            wc_product_id bigint(20) DEFAULT NULL COMMENT 'Generated WC product ID for this package',
            wc_cart_item_key varchar(32) DEFAULT NULL,

            -- Status
            status enum('generated', 'in_cart', 'purchased', 'expired') DEFAULT 'generated',
            expires_at datetime DEFAULT NULL,

            -- Timestamps
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            FOREIGN KEY (response_id) REFERENCES $responses_table(id) ON DELETE CASCADE,
            INDEX idx_response_id (response_id),
            INDEX idx_package_type (package_type),
            INDEX idx_status (status),
            INDEX idx_wc_product_id (wc_product_id),
            INDEX idx_expires_at (expires_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($responses_sql);
        dbDelta($packages_sql);

        // Save database version
        update_option('aihs_db_version', AIHS_VERSION);

        error_log('AI Health Savetnik: Database tables created/updated');
    }

    /**
     * Check if database needs upgrading
     */
    public function maybe_upgrade_database() {
        $current_version = get_option('aihs_db_version', '0.0.0');

        if (version_compare($current_version, AIHS_VERSION, '<')) {
            self::create_tables();
        }
    }

    /**
     * Get response by ID
     */
    public static function get_response($id) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Get response by session ID
     */
    public static function get_response_by_session($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
            $session_id
        ));
    }

    /**
     * Get response by user ID (latest)
     */
    public static function get_user_latest_response($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }

    /**
     * Create new response
     */
    public static function create_response($data) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        $defaults = array(
            'session_id' => '',
            'user_id' => null,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'city' => '',
            'postcode' => '',
            'country' => '',
            'age' => null,
            'gender' => null,
            'answers' => '{}',
            'intensity_data' => '{}',
            'calculated_score' => 0,
            'score_category' => '',
            'completion_status' => 'draft',
            'form_step' => 1,
            'quiz_progress' => 0,
            'gdpr_consent' => 0,
            'privacy_policy_accepted' => 0,
            'email_notifications_consent' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update response
     */
    public static function update_response($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        // Always update the updated_at timestamp
        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete response
     */
    public static function delete_response($id) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }

    /**
     * Get responses with pagination
     */
    public static function get_responses($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => '',
            'user_id' => '',
            'search' => ''
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $prepare_values = array();

        if (!empty($args['status'])) {
            $where[] = 'completion_status = %s';
            $prepare_values[] = $args['status'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        $offset = ($args['page'] - 1) * $args['per_page'];

        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $prepare_values[] = $args['per_page'];
        $prepare_values[] = $offset;

        if (!empty($prepare_values)) {
            $query = $wpdb->prepare($query, $prepare_values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get total responses count
     */
    public static function get_responses_count($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        $where = array('1=1');
        $prepare_values = array();

        if (!empty($args['status'])) {
            $where[] = 'completion_status = %s';
            $prepare_values[] = $args['status'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }

        if (!empty($args['search'])) {
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table WHERE $where_clause";

        if (!empty($prepare_values)) {
            $query = $wpdb->prepare($query, $prepare_values);
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Create package
     */
    public static function create_package($data) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        $defaults = array(
            'response_id' => 0,
            'package_type' => '2_products',
            'package_theme' => '',
            'package_name' => '',
            'package_description' => '',
            'product_ids' => '[]',
            'original_price' => 0.00,
            'discount_percentage' => 0.00,
            'discount_amount' => 0.00,
            'final_price' => 0.00,
            'vip_discount_applied' => 0,
            'vip_discount_percentage' => 0.00,
            'vip_discount_amount' => 0.00,
            'status' => 'generated'
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get packages for response
     */
    public static function get_response_packages($response_id) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE response_id = %d ORDER BY created_at ASC",
            $response_id
        ));
    }

    /**
     * Update package
     */
    public static function update_package($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        $data['updated_at'] = current_time('mysql');

        return $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            null,
            array('%d')
        ) !== false;
    }

    /**
     * Delete expired packages
     */
    public static function cleanup_expired_packages() {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        return $wpdb->query(
            "DELETE FROM $table WHERE expires_at IS NOT NULL AND expires_at < NOW() AND status = 'generated'"
        );
    }

    /**
     * Get statistics
     */
    public static function get_statistics() {
        global $wpdb;
        $responses_table = $wpdb->prefix . AIHS_RESPONSES_TABLE;
        $packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

        $stats = array();

        // Total responses
        $stats['total_responses'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $responses_table");

        // Completed responses
        $stats['completed_responses'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $responses_table WHERE completion_status = 'analysis_completed'"
        );

        // This week responses
        $stats['week_responses'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $responses_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // This month responses
        $stats['month_responses'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $responses_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Average score
        $stats['average_score'] = (float) $wpdb->get_var(
            "SELECT AVG(calculated_score) FROM $responses_table WHERE calculated_score > 0"
        );

        // Score distribution
        $score_categories = get_option('aihs_score_categories', array());
        foreach ($score_categories as $key => $category) {
            $stats['score_distribution'][$key] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $responses_table WHERE calculated_score BETWEEN %d AND %d",
                $category['min_score'],
                $category['max_score']
            ));
        }

        // Package statistics
        $stats['total_packages'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $packages_table");
        $stats['packages_in_cart'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $packages_table WHERE status = 'in_cart'"
        );
        $stats['packages_purchased'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $packages_table WHERE status = 'purchased'"
        );

        return $stats;
    }
}

error_log('AI Health Savetnik: Database class loaded');
?>