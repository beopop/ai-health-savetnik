<?php
/**
 * OpenAI integration for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_OpenAI {

    private $api_key;
    private $model;
    private $temperature;
    private $max_tokens;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('aihs_openai_settings', array());

        $this->api_key = $settings['api_key'] ?? '';
        $this->model = $settings['model'] ?? 'gpt-3.5-turbo';
        $this->temperature = floatval($settings['temperature'] ?? 0.7);
        $this->max_tokens = intval($settings['max_tokens'] ?? 1000);
    }

    /**
     * Test OpenAI API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured');
        }

        $response = $this->make_api_request(array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Test connection. Please respond with "OK".'
                )
            ),
            'max_tokens' => 10
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return array(
            'success' => true,
            'model' => $this->model,
            'response' => $response['choices'][0]['message']['content'] ?? 'No response'
        );
    }

    /**
     * Generate AI analysis based on health quiz response
     */
    public function generate_analysis($response) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key is not configured');
        }

        try {
            // Prepare the prompt
            $prompt = $this->build_analysis_prompt($response);

            $api_request = array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $this->get_system_prompt()
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens
            );

            $api_response = $this->make_api_request($api_request);

            if (is_wp_error($api_response)) {
                return $api_response;
            }

            $content = $api_response['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                return new WP_Error('empty_response', 'OpenAI returned empty response');
            }

            // Parse the structured response
            $parsed_analysis = $this->parse_ai_response($content);

            // Get recommended products based on answers
            $recommended_products = array();
            if (class_exists('AIHS_Scoring')) {
                $scoring = new AIHS_Scoring();
                $recommended_products = $scoring->get_recommended_products($response);
            }

            return array(
                'analysis' => $parsed_analysis['analysis'],
                'recommendations' => $parsed_analysis['recommendations'],
                'lifestyle_tips' => $parsed_analysis['lifestyle_tips'] ?? array(),
                'recommended_products' => $recommended_products,
                'raw_response' => $content
            );

        } catch (Exception $e) {
            error_log('AIHS OpenAI Error: ' . $e->getMessage());
            return new WP_Error('openai_error', 'Failed to generate AI analysis: ' . $e->getMessage());
        }
    }

    /**
     * Build the analysis prompt
     */
    private function build_analysis_prompt($response) {
        $answers = json_decode($response->answers, true) ?: array();
        $intensity_data = json_decode($response->intensity_data, true) ?: array();
        $questions = get_option('aihs_health_questions', array());

        $prompt = "Please analyze the following health survey results and provide personalized recommendations:\n\n";

        // Personal information
        $prompt .= "PERSONAL INFORMATION:\n";
        if (!empty($response->age)) {
            $prompt .= "- Age: {$response->age}\n";
        }
        if (!empty($response->gender)) {
            $prompt .= "- Gender: {$response->gender}\n";
        }
        $prompt .= "- Health Score: {$response->calculated_score}/100\n";
        $prompt .= "- Score Category: {$response->score_category}\n\n";

        // Health issues reported
        $prompt .= "HEALTH ISSUES REPORTED:\n";
        $reported_issues = array();

        foreach ($answers as $question_id => $answer) {
            if (strtolower($answer) === 'da') {
                $question = null;
                foreach ($questions as $q) {
                    if ($q['id'] == $question_id) {
                        $question = $q;
                        break;
                    }
                }

                if ($question) {
                    $issue_text = "- {$question['text']} (Answer: {$answer}";

                    if (isset($intensity_data[$question_id])) {
                        $issue_text .= ", Intensity: {$intensity_data[$question_id]}";
                    }

                    $issue_text .= ")";

                    if (!empty($question['ai_hint'])) {
                        $issue_text .= " [Context: {$question['ai_hint']}]";
                    }

                    $reported_issues[] = $issue_text;
                }
            }
        }

        if (empty($reported_issues)) {
            $prompt .= "- No significant health issues reported\n";
        } else {
            $prompt .= implode("\n", $reported_issues) . "\n";
        }

        $prompt .= "\nPlease provide your analysis in the following structured format:\n\n";
        $prompt .= "ANALYSIS:\n";
        $prompt .= "[Provide a comprehensive analysis of the health status based on the reported issues. Be specific about potential connections between symptoms and overall health patterns.]\n\n";

        $prompt .= "RECOMMENDATIONS:\n";
        $prompt .= "[Provide 3-5 specific, actionable recommendations prioritized by importance. Focus on natural approaches, lifestyle changes, and general wellness advice.]\n\n";

        $prompt .= "LIFESTYLE_TIPS:\n";
        $prompt .= "[Provide 3-4 practical daily lifestyle tips that can help address the identified issues.]\n\n";

        $prompt .= "Please write in Serbian language (srpski) and maintain a professional yet caring tone. Focus on natural health approaches and general wellness advice. Do not provide specific medical diagnoses or replace professional medical consultation.";

        return $prompt;
    }

    /**
     * Get system prompt for AI
     */
    private function get_system_prompt() {
        return "You are a knowledgeable health and wellness advisor specializing in natural health approaches and lifestyle medicine. Your role is to analyze health survey responses and provide personalized, evidence-based recommendations focusing on nutrition, lifestyle changes, and natural wellness strategies.

Key guidelines:
- Always emphasize that your advice complements but does not replace professional medical care
- Focus on natural, holistic approaches to health and wellness
- Provide specific, actionable recommendations
- Consider the interconnections between different health symptoms
- Maintain a supportive and encouraging tone
- Write responses in Serbian language (srpski)
- Structure your responses clearly with the requested sections
- Avoid making definitive medical diagnoses
- Recommend professional consultation for serious or persistent symptoms

Your expertise includes nutrition, stress management, sleep optimization, digestive health, immune system support, and natural approaches to common health concerns.";
    }

    /**
     * Parse AI response into structured format
     */
    private function parse_ai_response($content) {
        $parsed = array(
            'analysis' => '',
            'recommendations' => array(),
            'lifestyle_tips' => array()
        );

        // Split content by sections
        $sections = preg_split('/\n(?=ANALYSIS:|RECOMMENDATIONS:|LIFESTYLE_TIPS:)/i', $content);

        foreach ($sections as $section) {
            $section = trim($section);

            if (preg_match('/^ANALYSIS:\s*(.*)/is', $section, $matches)) {
                $parsed['analysis'] = trim($matches[1]);
            } elseif (preg_match('/^RECOMMENDATIONS:\s*(.*)/is', $section, $matches)) {
                $recommendations_text = trim($matches[1]);
                $parsed['recommendations'] = $this->parse_list_items($recommendations_text);
            } elseif (preg_match('/^LIFESTYLE_TIPS:\s*(.*)/is', $section, $matches)) {
                $tips_text = trim($matches[1]);
                $parsed['lifestyle_tips'] = $this->parse_list_items($tips_text);
            }
        }

        // Fallback: if sections not found, treat entire content as analysis
        if (empty($parsed['analysis']) && empty($parsed['recommendations'])) {
            $parsed['analysis'] = $content;
        }

        return $parsed;
    }

    /**
     * Parse list items from text
     */
    private function parse_list_items($text) {
        $items = array();

        // Split by lines and look for list patterns
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) continue;

            // Remove list markers (-, *, numbers, etc.)
            $line = preg_replace('/^[\-\*\d\.\)\s]+/', '', $line);
            $line = trim($line);

            if (!empty($line)) {
                $items[] = $line;
            }
        }

        return $items;
    }

    /**
     * Generate package description using AI
     */
    public function generate_package_description($package_products, $health_issues) {
        if (empty($this->api_key)) {
            return 'AI-generated package description (API key not configured)';
        }

        try {
            $prompt = "Create a compelling product package description for a health supplement bundle.\n\n";
            $prompt .= "PRODUCTS IN PACKAGE:\n";

            foreach ($package_products as $product) {
                $why_good = get_post_meta($product->get_id(), '_aihs_why_good', true);
                $dosage = get_post_meta($product->get_id(), '_aihs_dosage', true);

                $prompt .= "- {$product->get_name()}";
                if (!empty($why_good)) {
                    $prompt .= " - {$why_good}";
                }
                if (!empty($dosage)) {
                    $prompt .= " (Doziranje: {$dosage})";
                }
                $prompt .= "\n";
            }

            $prompt .= "\nTARGETED HEALTH ISSUES:\n";
            foreach ($health_issues as $issue) {
                $prompt .= "- {$issue}\n";
            }

            $prompt .= "\nPlease create a compelling package description (2-3 sentences) in Serbian that:\n";
            $prompt .= "- Highlights the synergistic benefits of combining these products\n";
            $prompt .= "- Addresses the specific health concerns\n";
            $prompt .= "- Uses persuasive but professional language\n";
            $prompt .= "- Emphasizes natural wellness approach\n";

            $api_request = array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.8,
                'max_tokens' => 200
            );

            $response = $this->make_api_request($api_request);

            if (is_wp_error($response)) {
                return 'Kompletna podrška za vaše zdravlje kroz pažljivo odabrane prirodne proizvode.';
            }

            $description = $response['choices'][0]['message']['content'] ?? '';
            return !empty($description) ? trim($description) : 'Kompletna podrška za vaše zdravlje kroz pažljivo odabrane prirodne proizvode.';

        } catch (Exception $e) {
            error_log('AIHS OpenAI Package Description Error: ' . $e->getMessage());
            return 'Kompletna podrška za vaše zdravlje kroz pažljivo odabrane prirodne proizvode.';
        }
    }

    /**
     * Make API request to OpenAI
     */
    private function make_api_request($data) {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        );

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => 60,
            'method' => 'POST'
        );

        $response = wp_remote_request('https://api.openai.com/v1/chat/completions', $args);

        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'Failed to connect to OpenAI API: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = $error_data['error']['message'] ?? 'Unknown API error';

            return new WP_Error('api_error', "OpenAI API error ({$response_code}): {$error_message}");
        }

        $decoded_response = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Failed to decode API response');
        }

        if (!isset($decoded_response['choices']) || empty($decoded_response['choices'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }

        return $decoded_response;
    }

    /**
     * Get API usage statistics
     */
    public function get_usage_stats() {
        // This would require implementing usage tracking
        // For now, return basic info
        return array(
            'api_key_configured' => !empty($this->api_key),
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens
        );
    }

    /**
     * Validate API key format
     */
    public function validate_api_key($api_key) {
        // Basic validation - OpenAI keys start with 'sk-'
        if (empty($api_key)) {
            return new WP_Error('empty_key', 'API key cannot be empty');
        }

        if (!preg_match('/^sk-[a-zA-Z0-9]{48}$/', $api_key)) {
            return new WP_Error('invalid_format', 'Invalid API key format. OpenAI keys should start with "sk-" followed by 48 characters.');
        }

        return true;
    }
}

error_log('AI Health Savetnik: OpenAI class loaded');
?>