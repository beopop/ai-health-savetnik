<?php
/**
 * Health Score Display Template
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$score_category = aihs_get_score_category($response->calculated_score);
$size_class = 'aihs-score-' . esc_attr($atts['size']);
?>

<div class="aihs-health-score-widget <?php echo $size_class; ?>" data-score="<?php echo esc_attr($response->calculated_score); ?>">

    <?php if ($atts['show_gauge'] === 'yes'): ?>
        <!-- Score Gauge -->
        <div class="aihs-score-gauge-container">
            <div class="aihs-score-gauge">
                <svg class="aihs-gauge-svg" viewBox="0 0 200 200">
                    <!-- Background circle -->
                    <circle cx="100" cy="100" r="80"
                            fill="none"
                            stroke="#ecf0f1"
                            stroke-width="20"/>

                    <!-- Progress circle -->
                    <circle cx="100" cy="100" r="80"
                            fill="none"
                            stroke="<?php echo esc_attr($score_category['data']['color'] ?? '#3498db'); ?>"
                            stroke-width="20"
                            stroke-dasharray="<?php echo ($response->calculated_score / 100) * 502.65; ?> 502.65"
                            stroke-dashoffset="125.66"
                            transform="rotate(-90 100 100)"
                            stroke-linecap="round"
                            class="aihs-progress-circle"/>
                </svg>

                <!-- Score display in center -->
                <div class="aihs-score-center">
                    <div class="aihs-score-number"><?php echo intval($response->calculated_score); ?></div>
                    <div class="aihs-score-divider">/</div>
                    <div class="aihs-score-max">100</div>
                </div>

                <!-- Score indicator arrow -->
                <?php
                $angle = ($response->calculated_score / 100) * 180 - 90; // Convert to degrees
                ?>
                <div class="aihs-score-indicator" style="transform: rotate(<?php echo $angle; ?>deg);"></div>
            </div>

            <!-- Score labels around gauge -->
            <div class="aihs-gauge-labels">
                <span class="aihs-gauge-label aihs-label-low">0</span>
                <span class="aihs-gauge-label aihs-label-mid">50</span>
                <span class="aihs-gauge-label aihs-label-high">100</span>
            </div>
        </div>
    <?php else: ?>
        <!-- Simple Score Display -->
        <div class="aihs-score-simple">
            <div class="aihs-score-value" style="color: <?php echo esc_attr($score_category['data']['color'] ?? '#3498db'); ?>">
                <?php echo intval($response->calculated_score); ?>
                <span class="aihs-score-unit">/100</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($atts['show_category'] === 'yes' && $score_category): ?>
        <!-- Score Category -->
        <div class="aihs-score-category-section">
            <div class="aihs-category-badge" style="background-color: <?php echo esc_attr($score_category['data']['color']); ?>">
                <span class="aihs-category-label"><?php echo esc_html($score_category['data']['label']); ?></span>
            </div>

            <?php if (!empty($score_category['data']['description'])): ?>
                <p class="aihs-category-description">
                    <?php echo esc_html($score_category['data']['description']); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Score Breakdown -->
    <div class="aihs-score-breakdown">
        <div class="aihs-breakdown-header">
            <h4>Analiza rezultata</h4>
            <button type="button" class="aihs-breakdown-toggle" onclick="toggleBreakdown()">
                <span class="aihs-toggle-icon">▼</span>
                <span class="aihs-toggle-text">Detalji</span>
            </button>
        </div>

        <div class="aihs-breakdown-content" style="display: none;">
            <?php
            // Calculate some basic breakdown info
            $total_questions = 0;
            $answered_questions = 0;
            $positive_answers = 0;

            if (!empty($response->answers)) {
                $answers = json_decode($response->answers, true) ?: array();
                $questions = aihs_get_health_questions();

                $total_questions = count($questions);
                $answered_questions = count($answers);

                foreach ($answers as $answer) {
                    if (strtolower($answer) === 'da') {
                        $positive_answers++;
                    }
                }
            }
            ?>

            <div class="aihs-breakdown-stats">
                <div class="aihs-stat-item">
                    <div class="aihs-stat-number"><?php echo $answered_questions; ?></div>
                    <div class="aihs-stat-label">Odgovorenih pitanja</div>
                </div>

                <div class="aihs-stat-item">
                    <div class="aihs-stat-number"><?php echo $positive_answers; ?></div>
                    <div class="aihs-stat-label">Identifikovanih problema</div>
                </div>

                <div class="aihs-stat-item">
                    <div class="aihs-stat-number"><?php echo max(0, 100 - $response->calculated_score); ?></div>
                    <div class="aihs-stat-label">Poeni za poboljšanje</div>
                </div>
            </div>

            <!-- Progress bars for different health aspects -->
            <div class="aihs-health-aspects">
                <div class="aihs-aspect-item">
                    <div class="aihs-aspect-label">Opšte zdravlje</div>
                    <div class="aihs-aspect-bar">
                        <div class="aihs-aspect-fill" style="width: <?php echo $response->calculated_score; ?>%; background: <?php echo esc_attr($score_category['data']['color']); ?>;"></div>
                    </div>
                    <div class="aihs-aspect-score"><?php echo intval($response->calculated_score); ?>%</div>
                </div>

                <?php
                // Mock some aspect scores based on the main score
                $aspects = array(
                    'Fizička aktivnost' => max(0, $response->calculated_score - rand(0, 20)),
                    'Ishrana' => max(0, $response->calculated_score - rand(0, 15)),
                    'San i odmor' => max(0, $response->calculated_score - rand(0, 25)),
                    'Stres management' => max(0, $response->calculated_score - rand(0, 30))
                );

                foreach ($aspects as $aspect => $score):
                    $color = $score >= 70 ? '#27ae60' : ($score >= 50 ? '#f39c12' : '#e74c3c');
                ?>
                    <div class="aihs-aspect-item">
                        <div class="aihs-aspect-label"><?php echo esc_html($aspect); ?></div>
                        <div class="aihs-aspect-bar">
                            <div class="aihs-aspect-fill" style="width: <?php echo $score; ?>%; background: <?php echo $color; ?>;"></div>
                        </div>
                        <div class="aihs-aspect-score"><?php echo intval($score); ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recommendations based on score -->
            <div class="aihs-score-recommendations">
                <h5>Preporuke za poboljšanje</h5>
                <ul>
                    <?php if ($response->calculated_score < 50): ?>
                        <li>🩺 Konsultujte se sa lekarom za detaljnu analizu</li>
                        <li>🏃‍♂️ Započnite program redovne fizičke aktivnosti</li>
                        <li>🥗 Poboljšajte kvalitet ishrane</li>
                    <?php elseif ($response->calculated_score < 70): ?>
                        <li>💪 Povećajte nivo fizičke aktivnosti</li>
                        <li>😴 Poboljšajte kvalitet i količinu sna</li>
                        <li>🧘‍♀️ Integrirajte tehnike opuštanja</li>
                    <?php else: ?>
                        <li>✨ Odličan rezultat! Nastavite sa zdravim navikama</li>
                        <li>📈 Razmotriti optimizaciju postojećih rutina</li>
                        <li>👥 Podelite iskustvo sa drugima</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="aihs-score-actions">
        <?php if ($response->completion_status === 'analysis_completed'): ?>
            <a href="#" class="aihs-btn aihs-btn-primary" onclick="viewFullResults()">
                📊 Pogledaj kompletnu analizu
            </a>
        <?php endif; ?>

        <button type="button" class="aihs-btn aihs-btn-secondary" onclick="shareScore()">
            📤 Podeli rezultat
        </button>

        <button type="button" class="aihs-btn aihs-btn-tertiary" onclick="retakeQuiz()">
            🔄 Ponovi test
        </button>
    </div>

    <!-- Generated timestamp -->
    <div class="aihs-score-meta">
        <small class="aihs-meta-text">
            Rezultat generisan <?php echo date_i18n('j.n.Y. \u H:i', strtotime($response->created_at)); ?>
        </small>
    </div>
</div>

<style>
.aihs-health-score-widget {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 30px;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 100%;
    margin: 0 auto;
}

/* Size variations */
.aihs-score-small {
    padding: 20px;
    max-width: 300px;
}

.aihs-score-medium {
    padding: 30px;
    max-width: 450px;
}

.aihs-score-large {
    padding: 40px;
    max-width: 600px;
}

.aihs-score-gauge-container {
    position: relative;
    margin-bottom: 30px;
}

.aihs-score-gauge {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
}

.aihs-score-small .aihs-score-gauge {
    width: 150px;
    height: 150px;
}

.aihs-score-large .aihs-score-gauge {
    width: 250px;
    height: 250px;
}

.aihs-gauge-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.aihs-progress-circle {
    transition: stroke-dasharray 1.5s ease-in-out;
    filter: drop-shadow(0 0 6px rgba(52, 152, 219, 0.3));
}

.aihs-score-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
}

.aihs-score-number {
    font-size: 3em;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
}

.aihs-score-small .aihs-score-number {
    font-size: 2.2em;
}

.aihs-score-large .aihs-score-number {
    font-size: 3.5em;
}

.aihs-score-divider {
    font-size: 1.5em;
    color: #bdc3c7;
    margin: 0 2px;
}

.aihs-score-max {
    font-size: 1.8em;
    color: #7f8c8d;
    line-height: 1;
}

.aihs-score-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 4px;
    height: 70px;
    background: #2c3e50;
    border-radius: 2px;
    transform-origin: bottom center;
    margin-left: -2px;
    margin-top: -70px;
    transition: transform 1.5s ease-in-out;
}

.aihs-gauge-labels {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.aihs-gauge-label {
    position: absolute;
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 600;
}

.aihs-label-low {
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
}

.aihs-label-mid {
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
}

.aihs-label-high {
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}

.aihs-score-simple {
    margin-bottom: 30px;
}

.aihs-score-value {
    font-size: 4em;
    font-weight: 700;
    line-height: 1;
}

.aihs-score-small .aihs-score-value {
    font-size: 3em;
}

.aihs-score-large .aihs-score-value {
    font-size: 5em;
}

.aihs-score-unit {
    font-size: 0.6em;
    color: #7f8c8d;
    font-weight: 400;
}

.aihs-score-category-section {
    margin-bottom: 30px;
}

.aihs-category-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 20px;
    border-radius: 20px;
    margin-bottom: 15px;
}

.aihs-category-label {
    color: white;
    font-weight: 600;
    font-size: 1.1em;
}

.aihs-category-description {
    color: #7f8c8d;
    font-size: 1em;
    line-height: 1.5;
    margin: 0;
}

.aihs-score-breakdown {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.aihs-breakdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.aihs-breakdown-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2em;
}

.aihs-breakdown-toggle {
    background: none;
    border: none;
    color: #3498db;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
}

.aihs-toggle-icon {
    transition: transform 0.3s ease;
}

.aihs-breakdown-toggle.active .aihs-toggle-icon {
    transform: rotate(180deg);
}

.aihs-breakdown-content {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.aihs-breakdown-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.aihs-stat-item {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
}

.aihs-stat-number {
    font-size: 2em;
    font-weight: 700;
    color: #3498db;
    line-height: 1;
}

.aihs-stat-label {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
    line-height: 1.3;
}

.aihs-health-aspects {
    margin-bottom: 25px;
}

.aihs-aspect-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 12px;
}

.aihs-aspect-label {
    width: 120px;
    font-size: 13px;
    color: #2c3e50;
    font-weight: 500;
}

.aihs-aspect-bar {
    flex: 1;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
}

.aihs-aspect-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 1s ease;
}

.aihs-aspect-score {
    width: 40px;
    text-align: right;
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 600;
}

.aihs-score-recommendations h5 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1em;
}

.aihs-score-recommendations ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aihs-score-recommendations li {
    padding: 8px 0;
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.4;
}

.aihs-score-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
}

.aihs-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.aihs-btn-primary {
    background: #3498db;
    color: white;
}

.aihs-btn-primary:hover {
    background: #2980b9;
    transform: translateY(-1px);
}

.aihs-btn-secondary {
    background: #27ae60;
    color: white;
}

.aihs-btn-secondary:hover {
    background: #229954;
}

.aihs-btn-tertiary {
    background: #ecf0f1;
    color: #2c3e50;
}

.aihs-btn-tertiary:hover {
    background: #d5dbdb;
}

.aihs-score-meta {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
}

.aihs-meta-text {
    color: #95a5a6;
    font-size: 12px;
}

/* Responsive */
@media (max-width: 768px) {
    .aihs-health-score-widget {
        padding: 20px 15px;
    }

    .aihs-breakdown-stats {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .aihs-aspect-item {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .aihs-aspect-label {
        width: auto;
        text-align: center;
    }

    .aihs-aspect-score {
        width: auto;
        text-align: center;
    }

    .aihs-score-actions {
        flex-direction: column;
    }

    .aihs-btn {
        width: 100%;
        justify-content: center;
    }

    .aihs-gauge-labels {
        display: none;
    }
}

/* Animation on load */
@keyframes scoreAnimation {
    from {
        stroke-dasharray: 0 502.65;
    }
}

.aihs-progress-circle {
    animation: scoreAnimation 1.5s ease-in-out;
}

/* Pulse effect for high scores */
.aihs-health-score-widget[data-score*="9"] .aihs-score-number,
.aihs-health-score-widget[data-score="100"] .aihs-score-number {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
</style>

<script>
function toggleBreakdown() {
    const content = document.querySelector('.aihs-breakdown-content');
    const toggle = document.querySelector('.aihs-breakdown-toggle');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.classList.add('active');
    } else {
        content.style.display = 'none';
        toggle.classList.remove('active');
    }
}

function viewFullResults() {
    // Redirect to full results page
    const currentUrl = window.location.href;
    const resultsUrl = currentUrl.includes('?') ? currentUrl + '&view=results' : currentUrl + '?view=results';
    window.location.href = resultsUrl;
}

function shareScore() {
    const score = document.querySelector('.aihs-health-score-widget').dataset.score;
    const text = `Moj AI Health Score je ${score}/100! 🎯`;

    if (navigator.share) {
        navigator.share({
            title: 'Moj AI Health Score',
            text: text,
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(text + ' ' + window.location.href).then(() => {
            alert('Rezultat je kopiran u clipboard!');
        });
    }
}

function retakeQuiz() {
    if (confirm('Da li želite da ponovite test? Trenutni rezultati će biti zamenjen novim.')) {
        const baseUrl = window.location.href.split('?')[0];
        window.location.href = baseUrl + '?retake=1';
    }
}

// Animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    const aspectFills = document.querySelectorAll('.aihs-aspect-fill');

    setTimeout(() => {
        aspectFills.forEach(fill => {
            const width = fill.style.width;
            fill.style.width = '0%';
            setTimeout(() => {
                fill.style.width = width;
            }, 100);
        });
    }, 500);
});
</script>