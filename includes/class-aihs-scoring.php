<?php
/**
 * Health scoring system for AI Health Savetnik
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AIHS_Scoring {

    /**
     * Calculate health score based on answers
     */
    public function calculate_score($response) {
        $answers = json_decode($response->answers, true) ?: array();
        $intensity_data = json_decode($response->intensity_data, true) ?: array();
        $questions = get_option('aihs_health_questions', array());

        $total_penalty = 0;
        $answered_questions = 0;
        $breakdown = array();

        foreach ($questions as $question) {
            $question_id = $question['id'];

            if (!isset($answers[$question_id])) {
                continue;
            }

            $answer = $answers[$question_id];
            $answered_questions++;

            $question_penalty = 0;
            $details = array(
                'question' => $question['text'],
                'answer' => $answer,
                'base_weight' => $question['weight'],
                'intensity_penalty' => 0,
                'total_penalty' => 0
            );

            // Apply base penalty for "Da" answers
            if (strtolower($answer) === 'da') {
                $question_penalty += $question['weight'];

                // Apply intensity penalty if available
                if (isset($intensity_data[$question_id]) &&
                    isset($question['intensity_options']) &&
                    isset($question['intensity_weights'])) {

                    $selected_intensity = $intensity_data[$question_id];
                    $intensity_index = array_search($selected_intensity, $question['intensity_options']);

                    if ($intensity_index !== false && isset($question['intensity_weights'][$intensity_index])) {
                        $intensity_penalty = $question['intensity_weights'][$intensity_index];
                        $question_penalty += $intensity_penalty;
                        $details['intensity'] = $selected_intensity;
                        $details['intensity_penalty'] = $intensity_penalty;
                    }
                }
            }

            $details['total_penalty'] = $question_penalty;
            $breakdown[] = $details;
            $total_penalty += $question_penalty;
        }

        // Calculate final score (100 - total penalties, minimum 0)
        $score = max(0, 100 - $total_penalty);

        // Get category and its data
        $category_info = $this->get_score_category($score);

        return array(
            'score' => $score,
            'category' => $category_info['key'],
            'category_data' => $category_info['data'],
            'total_penalty' => $total_penalty,
            'answered_questions' => $answered_questions,
            'total_questions' => count($questions),
            'completion_percentage' => count($questions) > 0 ? round(($answered_questions / count($questions)) * 100) : 0,
            'breakdown' => $breakdown,
            'recommendations' => $this->get_score_recommendations($score, $breakdown)
        );
    }

    /**
     * Get score category based on score value
     */
    public function get_score_category($score) {
        $categories = get_option('aihs_score_categories', array());

        foreach ($categories as $key => $category) {
            if ($score >= $category['min_score'] && $score <= $category['max_score']) {
                return array(
                    'key' => $key,
                    'data' => $category
                );
            }
        }

        // Fallback to moderate if no category matches
        return array(
            'key' => 'moderate',
            'data' => array(
                'label' => 'Umereno',
                'min_score' => 40,
                'max_score' => 59,
                'color' => '#fd7e14',
                'description' => 'Potrebno je obratiti pažnju na zdravlje.'
            )
        );
    }

    /**
     * Get recommendations based on score and answered questions
     */
    public function get_score_recommendations($score, $breakdown) {
        $recommendations = array();

        // General recommendations based on score
        if ($score >= 80) {
            $recommendations[] = array(
                'type' => 'general',
                'priority' => 'low',
                'title' => 'Odličan rezultat!',
                'message' => 'Vaše zdravlje je u odličnom stanju. Nastavite sa zdravim navikama.',
                'action' => 'maintain'
            );
        } elseif ($score >= 60) {
            $recommendations[] = array(
                'type' => 'general',
                'priority' => 'medium',
                'title' => 'Dobro zdravlje',
                'message' => 'Vaše zdravlje je dobro, ali postoji prostor za poboljšanje.',
                'action' => 'improve'
            );
        } elseif ($score >= 40) {
            $recommendations[] = array(
                'type' => 'general',
                'priority' => 'high',
                'title' => 'Potrebna pažnja',
                'message' => 'Preporučujemo da obratite pažnju na određene aspekte vašeg zdravlja.',
                'action' => 'address'
            );
        } else {
            $recommendations[] = array(
                'type' => 'general',
                'priority' => 'urgent',
                'title' => 'Konzultacija preporučena',
                'message' => 'Preporučujemo konsultaciju sa zdravstvenim stručnjakom.',
                'action' => 'consult'
            );
        }

        // Specific recommendations based on problematic areas
        $problem_areas = array();

        foreach ($breakdown as $item) {
            if ($item['total_penalty'] > 0) {
                $problem_areas[] = $item;
            }
        }

        // Sort by penalty (highest first)
        usort($problem_areas, function($a, $b) {
            return $b['total_penalty'] - $a['total_penalty'];
        });

        // Add specific recommendations for top 3 problems
        $top_problems = array_slice($problem_areas, 0, 3);

        foreach ($top_problems as $problem) {
            $recommendations[] = array(
                'type' => 'specific',
                'priority' => $this->get_problem_priority($problem['total_penalty']),
                'title' => $this->get_problem_title($problem),
                'message' => $this->get_problem_message($problem),
                'action' => 'address_specific',
                'question' => $problem['question'],
                'penalty' => $problem['total_penalty']
            );
        }

        return $recommendations;
    }

    /**
     * Get problem priority based on penalty
     */
    private function get_problem_priority($penalty) {
        if ($penalty >= 25) return 'urgent';
        if ($penalty >= 15) return 'high';
        if ($penalty >= 10) return 'medium';
        return 'low';
    }

    /**
     * Get problem title based on question content
     */
    private function get_problem_title($problem) {
        $question = strtolower($problem['question']);

        if (strpos($question, 'spavanje') !== false) {
            return 'Problemi sa spavanjem';
        } elseif (strpos($question, 'umor') !== false) {
            return 'Hronični umor';
        } elseif (strpos($question, 'glavobolje') !== false) {
            return 'Česte glavobolje';
        } elseif (strpos($question, 'varenje') !== false) {
            return 'Digestivni problemi';
        } elseif (strpos($question, 'stres') !== false || strpos($question, 'anksioznost') !== false) {
            return 'Stres i anksioznost';
        } elseif (strpos($question, 'leđa') !== false) {
            return 'Bolovi u leđima';
        } elseif (strpos($question, 'koncentracija') !== false) {
            return 'Problemi sa koncentracijom';
        } elseif (strpos($question, 'napetost') !== false) {
            return 'Mišićna napetost';
        } elseif (strpos($question, 'alergije') !== false) {
            return 'Alergijske reakcije';
        } elseif (strpos($question, 'energija') !== false) {
            return 'Nedostatak energije';
        }

        return 'Zdravstveni problem';
    }

    /**
     * Get problem message with advice
     */
    private function get_problem_message($problem) {
        $question = strtolower($problem['question']);

        if (strpos($question, 'spavanje') !== false) {
            return 'Kvalitet sna utiče na celokupno zdravlje. Preporučujemo uspostavljanje redovne rutine spavanja.';
        } elseif (strpos($question, 'umor') !== false) {
            return 'Hronični umor može ukazivati na različite zdravstvene probleme. Važno je identifikovati uzrok.';
        } elseif (strpos($question, 'glavobolje') !== false) {
            return 'Česte glavobolje mogu biti povezane sa stresom, dehidracijom ili drugim faktorima.';
        } elseif (strpos($question, 'varenje') !== false) {
            return 'Zdravlje digestivnog sistema je ključno za opšte blagostanje. Razmotrite promenu ishrane.';
        } elseif (strpos($question, 'stres') !== false || strpos($question, 'anksioznost') !== false) {
            return 'Upravljanje stresom je važno za mentalno i fizičko zdravlje. Razmotriti tehnike relaksacije.';
        } elseif (strpos($question, 'leđa') !== false) {
            return 'Bolovi u leđima mogu biti povezani sa posturom i stilom života. Važne su vežbe i ergonomija.';
        } elseif (strpos($question, 'koncentracija') !== false) {
            return 'Problemi sa koncentracijom mogu uticati na produktivnost. Važan je kvalitetan odmor i ishrana.';
        } elseif (strpos($question, 'napetost') !== false) {
            return 'Mišićna napetost često je povezana sa stresom. Preporučujemo redovnu fizičku aktivnost.';
        } elseif (strpos($question, 'alergije') !== false) {
            return 'Alergije mogu značajno uticati na kvalitet života. Važno je identifikovati uzročnike.';
        } elseif (strpos($question, 'energija') !== false) {
            return 'Nedostatak energije može biti povezan sa ishranom, spavanjem ili zdravstvenim stanjem.';
        }

        return 'Ovaj zdravstveni problem zahteva pažnju i možda konsultaciju sa stručnjakom.';
    }

    /**
     * Get recommended products based on answers
     */
    public function get_recommended_products($response) {
        $answers = json_decode($response->answers, true) ?: array();
        $questions = get_option('aihs_health_questions', array());
        $recommended_product_ids = array();

        foreach ($answers as $question_id => $answer) {
            if (strtolower($answer) === 'da') {
                // Find the question and get its recommended products
                foreach ($questions as $question) {
                    if ($question['id'] == $question_id && !empty($question['recommended_products'])) {
                        $recommended_product_ids = array_merge($recommended_product_ids, $question['recommended_products']);
                    }
                }
            }
        }

        // Remove duplicates and get unique products
        $recommended_product_ids = array_unique($recommended_product_ids);

        // Filter to only include products that exist and are enabled for AI recommendations
        $valid_products = array();

        foreach ($recommended_product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && $product->exists()) {
                $allow_ai_recommendations = get_post_meta($product_id, '_aihs_allow_ai_recommendations', true);
                if ($allow_ai_recommendations === 'yes') {
                    $valid_products[] = $product_id;
                }
            }
        }

        return $valid_products;
    }

    /**
     * Simulate score for testing (admin preview)
     */
    public function simulate_score($test_answers, $test_intensities = array()) {
        $questions = get_option('aihs_health_questions', array());
        $total_penalty = 0;
        $breakdown = array();

        foreach ($test_answers as $question_id => $answer) {
            $question = null;
            foreach ($questions as $q) {
                if ($q['id'] == $question_id) {
                    $question = $q;
                    break;
                }
            }

            if (!$question) continue;

            $question_penalty = 0;

            if (strtolower($answer) === 'da') {
                $question_penalty += $question['weight'];

                // Apply intensity penalty
                if (isset($test_intensities[$question_id]) &&
                    isset($question['intensity_options']) &&
                    isset($question['intensity_weights'])) {

                    $intensity_index = array_search($test_intensities[$question_id], $question['intensity_options']);
                    if ($intensity_index !== false && isset($question['intensity_weights'][$intensity_index])) {
                        $question_penalty += $question['intensity_weights'][$intensity_index];
                    }
                }
            }

            $breakdown[] = array(
                'question' => $question['text'],
                'answer' => $answer,
                'penalty' => $question_penalty
            );

            $total_penalty += $question_penalty;
        }

        $score = max(0, 100 - $total_penalty);
        $category_info = $this->get_score_category($score);

        return array(
            'score' => $score,
            'category' => $category_info['key'],
            'category_data' => $category_info['data'],
            'total_penalty' => $total_penalty,
            'breakdown' => $breakdown
        );
    }

    /**
     * Get score statistics for all responses
     */
    public function get_score_statistics() {
        global $wpdb;
        $table = $wpdb->prefix . AIHS_RESPONSES_TABLE;

        $stats = array();

        // Average score
        $stats['average_score'] = (float) $wpdb->get_var(
            "SELECT AVG(calculated_score) FROM $table WHERE calculated_score > 0"
        );

        // Score distribution
        $categories = get_option('aihs_score_categories', array());
        $stats['distribution'] = array();

        foreach ($categories as $key => $category) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE calculated_score BETWEEN %d AND %d",
                $category['min_score'],
                $category['max_score']
            ));

            $stats['distribution'][$key] = array(
                'count' => $count,
                'label' => $category['label'],
                'color' => $category['color']
            );
        }

        // Trend data (last 30 days)
        $stats['trend'] = $wpdb->get_results(
            "SELECT DATE(created_at) as date, AVG(calculated_score) as avg_score, COUNT(*) as count
             FROM $table
             WHERE calculated_score > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        return $stats;
    }
}

error_log('AI Health Savetnik: Scoring class loaded');
?>