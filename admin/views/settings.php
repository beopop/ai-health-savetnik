<?php
/**
 * Settings Page View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('AI Health Savetnik Settings', 'ai-health-savetnik'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=aihs-settings&tab=general"
           class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'ai-health-savetnik'); ?>
        </a>
        <a href="?page=aihs-settings&tab=openai"
           class="nav-tab <?php echo $active_tab === 'openai' ? 'nav-tab-active' : ''; ?>">
            <?php _e('OpenAI Integration', 'ai-health-savetnik'); ?>
        </a>
        <a href="?page=aihs-settings&tab=ui"
           class="nav-tab <?php echo $active_tab === 'ui' ? 'nav-tab-active' : ''; ?>">
            <?php _e('User Interface', 'ai-health-savetnik'); ?>
        </a>
        <a href="?page=aihs-settings&tab=scoring"
           class="nav-tab <?php echo $active_tab === 'scoring' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Scoring System', 'ai-health-savetnik'); ?>
        </a>
        <a href="?page=aihs-settings&tab=packages"
           class="nav-tab <?php echo $active_tab === 'packages' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Package Discounts', 'ai-health-savetnik'); ?>
        </a>
    </nav>

    <form method="post" action="options.php">
        <?php
        switch ($active_tab) {
            case 'openai':
                settings_fields('aihs_openai_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'ai-health-savetnik'); ?></th>
                        <td>
                            <input type="password"
                                   name="aihs_openai_settings[api_key]"
                                   value="<?php echo esc_attr($openai_settings['api_key'] ?? ''); ?>"
                                   class="regular-text"
                                   placeholder="sk-...">
                            <p class="description">
                                <?php _e('Enter your OpenAI API key. Get one from', 'ai-health-savetnik'); ?>
                                <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Model', 'ai-health-savetnik'); ?></th>
                        <td>
                            <select name="aihs_openai_settings[model]">
                                <option value="gpt-3.5-turbo" <?php selected($openai_settings['model'] ?? '', 'gpt-3.5-turbo'); ?>>
                                    GPT-3.5 Turbo (Recommended)
                                </option>
                                <option value="gpt-4" <?php selected($openai_settings['model'] ?? '', 'gpt-4'); ?>>
                                    GPT-4 (More expensive)
                                </option>
                                <option value="gpt-4-turbo-preview" <?php selected($openai_settings['model'] ?? '', 'gpt-4-turbo-preview'); ?>>
                                    GPT-4 Turbo Preview
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Select the OpenAI model to use for health analysis.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Temperature', 'ai-health-savetnik'); ?></th>
                        <td>
                            <input type="number"
                                   name="aihs_openai_settings[temperature]"
                                   value="<?php echo esc_attr($openai_settings['temperature'] ?? '0.7'); ?>"
                                   step="0.1" min="0" max="2" class="small-text">
                            <p class="description">
                                <?php _e('Controls randomness: 0 = focused, 2 = creative. Recommended: 0.7', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Max Tokens', 'ai-health-savetnik'); ?></th>
                        <td>
                            <input type="number"
                                   name="aihs_openai_settings[max_tokens]"
                                   value="<?php echo esc_attr($openai_settings['max_tokens'] ?? '1000'); ?>"
                                   step="100" min="100" max="4000" class="small-text">
                            <p class="description">
                                <?php _e('Maximum length of AI response. Higher = longer responses = more cost.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php if (!empty($openai_settings['api_key'])): ?>
                <div class="aihs-test-section">
                    <h3><?php _e('Test Connection', 'ai-health-savetnik'); ?></h3>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aihs-settings&tab=openai&action=test_openai'), 'aihs_test_openai', 'nonce'); ?>"
                           class="button button-secondary">
                            <?php _e('Test OpenAI Connection', 'ai-health-savetnik'); ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>
                <?php
                break;

            case 'ui':
                settings_fields('aihs_ui_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Form Title', 'ai-health-savetnik'); ?></th>
                        <td>
                            <input type="text"
                                   name="aihs_ui_settings[form_title]"
                                   value="<?php echo esc_attr($ui_settings['form_title'] ?? 'Analiza Zdravlja'); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Form Description', 'ai-health-savetnik'); ?></th>
                        <td>
                            <textarea name="aihs_ui_settings[form_description]"
                                      rows="3" class="large-text"><?php echo esc_textarea($ui_settings['form_description'] ?? ''); ?></textarea>
                            <p class="description">
                                <?php _e('Description shown at the top of the health quiz form.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Progress Bar Style', 'ai-health-savetnik'); ?></th>
                        <td>
                            <select name="aihs_ui_settings[progress_bar_style]">
                                <option value="gradient" <?php selected($ui_settings['progress_bar_style'] ?? '', 'gradient'); ?>>
                                    <?php _e('Gradient', 'ai-health-savetnik'); ?>
                                </option>
                                <option value="solid" <?php selected($ui_settings['progress_bar_style'] ?? '', 'solid'); ?>>
                                    <?php _e('Solid Color', 'ai-health-savetnik'); ?>
                                </option>
                                <option value="striped" <?php selected($ui_settings['progress_bar_style'] ?? '', 'striped'); ?>>
                                    <?php _e('Striped', 'ai-health-savetnik'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Show Progress Percentage', 'ai-health-savetnik'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="aihs_ui_settings[show_progress_percentage]"
                                       value="1"
                                       <?php checked(!empty($ui_settings['show_progress_percentage'])); ?>>
                                <?php _e('Display completion percentage in progress bar', 'ai-health-savetnik'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php
                break;

            case 'scoring':
                settings_fields('aihs_score_categories');
                ?>
                <h3><?php _e('Score Categories', 'ai-health-savetnik'); ?></h3>
                <p class="description">
                    <?php _e('Define score ranges and their corresponding health categories.', 'ai-health-savetnik'); ?>
                </p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Label', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Score Range', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Color', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Description', 'ai-health-savetnik'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $default_categories = array(
                            'excellent' => array('label' => 'Odličo', 'min_score' => 80, 'max_score' => 100, 'color' => '#28a745'),
                            'good' => array('label' => 'Dobro', 'min_score' => 60, 'max_score' => 79, 'color' => '#17a2b8'),
                            'moderate' => array('label' => 'Umereno', 'min_score' => 40, 'max_score' => 59, 'color' => '#fd7e14'),
                            'risky' => array('label' => 'Rizično', 'min_score' => 0, 'max_score' => 39, 'color' => '#dc3545')
                        );

                        $categories = array_merge($default_categories, $score_categories);

                        foreach ($categories as $key => $category):
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst($key); ?></strong></td>
                            <td>
                                <input type="text"
                                       name="aihs_score_categories[<?php echo $key; ?>][label]"
                                       value="<?php echo esc_attr($category['label']); ?>"
                                       class="regular-text">
                            </td>
                            <td>
                                <input type="number"
                                       name="aihs_score_categories[<?php echo $key; ?>][min_score]"
                                       value="<?php echo esc_attr($category['min_score']); ?>"
                                       class="small-text" min="0" max="100">
                                -
                                <input type="number"
                                       name="aihs_score_categories[<?php echo $key; ?>][max_score]"
                                       value="<?php echo esc_attr($category['max_score']); ?>"
                                       class="small-text" min="0" max="100">
                            </td>
                            <td>
                                <input type="color"
                                       name="aihs_score_categories[<?php echo $key; ?>][color]"
                                       value="<?php echo esc_attr($category['color']); ?>">
                            </td>
                            <td>
                                <textarea name="aihs_score_categories[<?php echo $key; ?>][description]"
                                          rows="2" class="regular-text"><?php echo esc_textarea($category['description'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                break;

            case 'packages':
                settings_fields('aihs_package_discounts');
                ?>
                <h3><?php _e('Package Discount Settings', 'ai-health-savetnik'); ?></h3>
                <p class="description">
                    <?php _e('Set discount percentages for different package types.', 'ai-health-savetnik'); ?>
                </p>

                <table class="form-table">
                    <?php
                    $package_types = aihs_get_package_types();
                    foreach ($package_types as $type => $info):
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($info['label']); ?></th>
                        <td>
                            <input type="number"
                                   name="aihs_package_discounts[<?php echo $type; ?>]"
                                   value="<?php echo esc_attr($package_discounts[$type] ?? '0'); ?>"
                                   step="0.1" min="0" max="50" class="small-text"> %
                            <p class="description">
                                <?php echo esc_html($info['description']); ?>
                            </p>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <tr>
                        <th scope="row"><?php _e('VIP Additional Discount', 'ai-health-savetnik'); ?></th>
                        <td>
                            <input type="number"
                                   name="aihs_package_discounts[vip_additional]"
                                   value="<?php echo esc_attr($package_discounts['vip_additional'] ?? '0'); ?>"
                                   step="0.1" min="0" max="20" class="small-text"> %
                            <p class="description">
                                <?php _e('Additional discount for VIP users on top of package discounts.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php
                break;

            default: // general
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Plugin Status', 'ai-health-savetnik'); ?></th>
                        <td>
                            <p>
                                <strong><?php _e('Version:', 'ai-health-savetnik'); ?></strong> <?php echo AIHS_VERSION; ?><br>
                                <strong><?php _e('Health Questions:', 'ai-health-savetnik'); ?></strong> <?php echo count(aihs_get_health_questions()); ?><br>
                                <strong><?php _e('OpenAI Status:', 'ai-health-savetnik'); ?></strong>
                                <?php if (aihs_is_openai_configured()): ?>
                                    <span style="color: #46b450;"><?php _e('Configured', 'ai-health-savetnik'); ?></span>
                                <?php else: ?>
                                    <span style="color: #dc3232;"><?php _e('Not Configured', 'ai-health-savetnik'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Shortcodes', 'ai-health-savetnik'); ?></th>
                        <td>
                            <p><?php _e('Use these shortcodes to display the health quiz:', 'ai-health-savetnik'); ?></p>
                            <ul>
                                <li><code>[aihs_health_quiz]</code> - <?php _e('Complete health quiz form', 'ai-health-savetnik'); ?></li>
                                <li><code>[aihs_quiz_results]</code> - <?php _e('Display quiz results', 'ai-health-savetnik'); ?></li>
                                <li><code>[aihs_health_packages]</code> - <?php _e('Show recommended packages', 'ai-health-savetnik'); ?></li>
                                <li><code>[aihs_quiz_button]</code> - <?php _e('Simple button to start quiz', 'ai-health-savetnik'); ?></li>
                                <li><code>[aihs_user_dashboard]</code> - <?php _e('User health dashboard', 'ai-health-savetnik'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Documentation', 'ai-health-savetnik'); ?></th>
                        <td>
                            <p>
                                <?php _e('For detailed setup instructions and features, visit:', 'ai-health-savetnik'); ?>
                                <a href="https://github.com/beopop/ai-health-savetnik" target="_blank">GitHub Repository</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php
                break;
        }
        ?>

        <?php if ($active_tab !== 'general'): ?>
            <?php submit_button(); ?>
        <?php endif; ?>
    </form>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.aihs-test-section {
    background: #f9f9f9;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
}

.aihs-test-section h3 {
    margin-top: 0;
}
</style>