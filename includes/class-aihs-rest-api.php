<?php
/**
 * REST API endpoints for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_REST_API {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_aihs_autosave', array($this, 'ajax_autosave'));
        add_action('wp_ajax_nopriv_aihs_autosave', array($this, 'ajax_autosave'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'aihs/v1';

        // Autosave endpoint
        register_rest_route($namespace, '/autosave', array(
            'methods' => 'POST',
            'callback' => array($this, 'autosave_endpoint'),
            'permission_callback' => array($this, 'autosave_permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Finish quiz endpoint
        register_rest_route($namespace, '/finish', array(
            'methods' => 'POST',
            'callback' => array($this, 'finish_quiz_endpoint'),
            'permission_callback' => array($this, 'autosave_permission_check'),
            'args' => array(
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // AI Analysis endpoint
        register_rest_route($namespace, '/analyze', array(
            'methods' => 'POST',
            'callback' => array($this, 'ai_analysis_endpoint'),
            'permission_callback' => array($this, 'autosave_permission_check'),
            'args' => array(
                'response_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        // Generate packages endpoint
        register_rest_route($namespace, '/packages', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_packages_endpoint'),
            'permission_callback' => array($this, 'autosave_permission_check'),
            'args' => array(
                'response_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));

        // Get response data endpoint
        register_rest_route($namespace, '/response/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_response_endpoint'),
            'permission_callback' => array($this, 'get_response_permission_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
    }

    /**
     * Permission check for autosave endpoints
     */
    public function autosave_permission_check($request) {
        // Check nonce
        $nonce = $request->get_header('X-WP-Nonce') ?: $request->get_param('nonce');

        if (!wp_verify_nonce($nonce, 'aihs_nonce')) {
            return new WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
        }

        return true;
    }

    /**
     * Permission check for getting response data
     */
    public function get_response_permission_check($request) {
        $response_id = $request->get_param('id');
        $session_id = $request->get_param('session_id');

        // Allow access if user owns the response or has session access
        if (is_user_logged_in()) {
            $response = AIHS_Database::get_response($response_id);
            if ($response && $response->user_id == get_current_user_id()) {
                return true;
            }
        }

        if (!empty($session_id)) {
            $response = AIHS_Database::get_response($response_id);
            if ($response && $response->session_id === $session_id) {
                return true;
            }
        }

        return new WP_Error('access_denied', 'Access denied', array('status' => 403));
    }

    /**
     * AJAX autosave handler (fallback for non-REST environments)
     */
    public function ajax_autosave() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aihs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        $request_data = array(
            'session_id' => sanitize_text_field($_POST['session_id'] ?? ''),
            'step' => sanitize_text_field($_POST['step'] ?? 'form'),
            'data' => $_POST['data'] ?? array()
        );

        $result = $this->process_autosave($request_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * REST autosave endpoint
     */
    public function autosave_endpoint($request) {
        $request_data = array(
            'session_id' => $request->get_param('session_id'),
            'step' => $request->get_param('step') ?: 'form',
            'data' => $request->get_param('data') ?: array()
        );

        $result = $this->process_autosave($request_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Process autosave data
     */
    private function process_autosave($data) {
        try {
            $session_id = $data['session_id'];
            $step = $data['step'];
            $form_data = $data['data'];

            if (empty($session_id)) {
                return new WP_Error('missing_session', 'Session ID is required');
            }

            // Get or create response record
            $response = AIHS_Database::get_response_by_session($session_id);

            if (!$response) {
                // Create new response
                $response_data = array(
                    'session_id' => $session_id,
                    'user_id' => is_user_logged_in() ? get_current_user_id() : null,
                    'completion_status' => 'draft'
                );

                $response_id = AIHS_Database::create_response($response_data);

                if (!$response_id) {
                    return new WP_Error('creation_failed', 'Failed to create response record');
                }

                $response = AIHS_Database::get_response($response_id);
            }

            // Prepare update data based on step
            $update_data = array();

            if ($step === 'form') {
                // Form data mapping to WooCommerce fields
                $field_mapping = array(
                    'first_name' => 'first_name',
                    'last_name' => 'last_name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'address' => 'address',
                    'city' => 'city',
                    'postcode' => 'postcode',
                    'country' => 'country',
                    'age' => 'age',
                    'gender' => 'gender'
                );

                foreach ($field_mapping as $form_field => $db_field) {
                    if (isset($form_data[$form_field]) && !empty($form_data[$form_field])) {
                        $update_data[$db_field] = sanitize_text_field($form_data[$form_field]);
                    }
                }

                // GDPR consent fields
                if (isset($form_data['gdpr_consent'])) {
                    $update_data['gdpr_consent'] = (int) $form_data['gdpr_consent'];
                }
                if (isset($form_data['privacy_policy_accepted'])) {
                    $update_data['privacy_policy_accepted'] = (int) $form_data['privacy_policy_accepted'];
                }
                if (isset($form_data['email_notifications_consent'])) {
                    $update_data['email_notifications_consent'] = (int) $form_data['email_notifications_consent'];
                }

                $update_data['form_step'] = 2; // Move to next step
                $update_data['completion_status'] = 'form_completed';

                // Sync with user profile if logged in
                if (is_user_logged_in() && !empty($update_data['email'])) {
                    $this->sync_user_profile($update_data);
                }

            } elseif ($step === 'quiz') {
                // Quiz answers and intensity data
                if (isset($form_data['answers'])) {
                    $answers = $this->sanitize_quiz_answers($form_data['answers']);
                    $update_data['answers'] = wp_json_encode($answers);
                }

                if (isset($form_data['intensity_data'])) {
                    $intensity_data = $this->sanitize_quiz_answers($form_data['intensity_data']);
                    $update_data['intensity_data'] = wp_json_encode($intensity_data);
                }

                // Calculate progress
                $questions = get_option('aihs_health_questions', array());
                $total_questions = count($questions);
                $answered_questions = count(json_decode($update_data['answers'] ?? '{}', true));

                $progress = $total_questions > 0 ? round(($answered_questions / $total_questions) * 100) : 0;
                $update_data['quiz_progress'] = $progress;

                if ($progress >= 100) {
                    $update_data['completion_status'] = 'quiz_completed';
                }
            }

            // Update response
            $updated = AIHS_Database::update_response($response->id, $update_data);

            if (!$updated) {
                return new WP_Error('update_failed', 'Failed to update response');
            }

            // Return success response
            return array(
                'success' => true,
                'response_id' => $response->id,
                'session_id' => $session_id,
                'step' => $step,
                'progress' => $update_data['quiz_progress'] ?? 0,
                'message' => 'Data saved successfully'
            );

        } catch (Exception $e) {
            error_log('AIHS Autosave Error: ' . $e->getMessage());
            return new WP_Error('autosave_error', 'An error occurred while saving data');
        }
    }

    /**
     * Finish quiz endpoint
     */
    public function finish_quiz_endpoint($request) {
        $session_id = $request->get_param('session_id');
        $response = AIHS_Database::get_response_by_session($session_id);

        if (!$response) {
            return new WP_Error('response_not_found', 'Response not found', array('status' => 404));
        }

        // Calculate final score
        $score_data = $this->calculate_health_score($response);

        $update_data = array(
            'calculated_score' => $score_data['score'],
            'score_category' => $score_data['category'],
            'completion_status' => 'analysis_completed',
            'completed_at' => current_time('mysql')
        );

        $updated = AIHS_Database::update_response($response->id, $update_data);

        if (!$updated) {
            return new WP_Error('update_failed', 'Failed to update response');
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response_id' => $response->id,
            'score' => $score_data['score'],
            'category' => $score_data['category'],
            'category_data' => $score_data['category_data']
        ), 200);
    }

    /**
     * AI Analysis endpoint
     */
    public function ai_analysis_endpoint($request) {
        $response_id = $request->get_param('response_id');
        $response = AIHS_Database::get_response($response_id);

        if (!$response) {
            return new WP_Error('response_not_found', 'Response not found', array('status' => 404));
        }

        // Generate AI analysis
        if (class_exists('AIHS_OpenAI')) {
            $openai = new AIHS_OpenAI();
            $analysis = $openai->generate_analysis($response);

            if (is_wp_error($analysis)) {
                return $analysis;
            }

            // Update response with AI analysis
            $update_data = array(
                'ai_analysis' => $analysis['analysis'],
                'ai_recommendations' => $analysis['recommendations'],
                'recommended_products' => wp_json_encode($analysis['recommended_products'] ?? array())
            );

            AIHS_Database::update_response($response_id, $update_data);

            return new WP_REST_Response($analysis, 200);
        }

        return new WP_Error('openai_not_available', 'OpenAI integration not available');
    }

    /**
     * Generate packages endpoint
     */
    public function generate_packages_endpoint($request) {
        $response_id = $request->get_param('response_id');
        $response = AIHS_Database::get_response($response_id);

        if (!$response) {
            return new WP_Error('response_not_found', 'Response not found', array('status' => 404));
        }

        if (class_exists('AIHS_Packages')) {
            $packages_handler = new AIHS_Packages();
            $packages = $packages_handler->generate_packages($response);

            if (is_wp_error($packages)) {
                return $packages;
            }

            return new WP_REST_Response($packages, 200);
        }

        return new WP_Error('packages_not_available', 'Package generation not available');
    }

    /**
     * Get response endpoint
     */
    public function get_response_endpoint($request) {
        $response_id = $request->get_param('id');
        $response = AIHS_Database::get_response($response_id);

        if (!$response) {
            return new WP_Error('response_not_found', 'Response not found', array('status' => 404));
        }

        // Prepare response data
        $response_data = array(
            'id' => $response->id,
            'session_id' => $response->session_id,
            'personal_data' => array(
                'first_name' => $response->first_name,
                'last_name' => $response->last_name,
                'email' => $response->email,
                'phone' => $response->phone,
                'address' => $response->address,
                'city' => $response->city,
                'postcode' => $response->postcode,
                'country' => $response->country,
                'age' => $response->age,
                'gender' => $response->gender
            ),
            'quiz_data' => array(
                'answers' => json_decode($response->answers, true),
                'intensity_data' => json_decode($response->intensity_data, true),
                'progress' => $response->quiz_progress
            ),
            'results' => array(
                'score' => $response->calculated_score,
                'category' => $response->score_category,
                'ai_analysis' => $response->ai_analysis,
                'ai_recommendations' => $response->ai_recommendations,
                'recommended_products' => json_decode($response->recommended_products, true)
            ),
            'status' => array(
                'completion_status' => $response->completion_status,
                'form_step' => $response->form_step,
                'created_at' => $response->created_at,
                'updated_at' => $response->updated_at,
                'completed_at' => $response->completed_at
            )
        );

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Sanitize quiz answers
     */
    private function sanitize_quiz_answers($answers) {
        if (!is_array($answers)) {
            return array();
        }

        $sanitized = array();
        foreach ($answers as $question_id => $answer) {
            $sanitized[intval($question_id)] = sanitize_text_field($answer);
        }

        return $sanitized;
    }

    /**
     * Calculate health score
     */
    private function calculate_health_score($response) {
        if (class_exists('AIHS_Scoring')) {
            $scoring = new AIHS_Scoring();
            return $scoring->calculate_score($response);
        }

        // Fallback basic scoring
        $answers = json_decode($response->answers, true) ?: array();
        $intensity_data = json_decode($response->intensity_data, true) ?: array();
        $questions = get_option('aihs_health_questions', array());

        $total_penalty = 0;

        foreach ($answers as $question_id => $answer) {
            $question = null;
            foreach ($questions as $q) {
                if ($q['id'] == $question_id) {
                    $question = $q;
                    break;
                }
            }

            if (!$question) continue;

            if (strtolower($answer) === 'da') {
                $total_penalty += $question['weight'];

                // Add intensity penalty
                if (isset($intensity_data[$question_id]) && isset($question['intensity_weights'])) {
                    $intensity_index = array_search($intensity_data[$question_id], $question['intensity_options']);
                    if ($intensity_index !== false && isset($question['intensity_weights'][$intensity_index])) {
                        $total_penalty += $question['intensity_weights'][$intensity_index];
                    }
                }
            }
        }

        $score = max(0, 100 - $total_penalty);

        // Determine category
        $categories = get_option('aihs_score_categories', array());
        $category = 'moderate';
        $category_data = null;

        foreach ($categories as $key => $cat) {
            if ($score >= $cat['min_score'] && $score <= $cat['max_score']) {
                $category = $key;
                $category_data = $cat;
                break;
            }
        }

        return array(
            'score' => $score,
            'category' => $category,
            'category_data' => $category_data,
            'total_penalty' => $total_penalty
        );
    }

    /**
     * Sync user profile with form data
     */
    private function sync_user_profile($data) {
        $user_id = get_current_user_id();
        if (!$user_id) return;

        // Update billing fields
        $billing_fields = array(
            'first_name' => 'billing_first_name',
            'last_name' => 'billing_last_name',
            'email' => 'billing_email',
            'phone' => 'billing_phone',
            'address' => 'billing_address_1',
            'city' => 'billing_city',
            'postcode' => 'billing_postcode',
            'country' => 'billing_country'
        );

        foreach ($billing_fields as $form_field => $meta_key) {
            if (!empty($data[$form_field])) {
                update_user_meta($user_id, $meta_key, $data[$form_field]);
            }
        }

        // Update custom fields
        if (!empty($data['age'])) {
            update_user_meta($user_id, 'aihs_age', $data['age']);
        }
        if (!empty($data['gender'])) {
            update_user_meta($user_id, 'aihs_gender', $data['gender']);
        }
    }
}

error_log('AI Health Savetnik: REST API class loaded');
?>