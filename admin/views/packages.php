<?php
/**
 * Packages Management View
 *
 * @package AI_Health_Savetnik
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get package statistics
global $wpdb;
$packages_table = $wpdb->prefix . AIHS_PACKAGES_TABLE;

$package_stats = array();
$package_types = aihs_get_package_types();

foreach ($package_types as $type => $info) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $packages_table WHERE package_type = %s",
        $type
    ));
    $package_stats[$type] = array(
        'count' => intval($count),
        'label' => $info['label']
    );
}

// Get recent packages
$recent_packages = $wpdb->get_results(
    "SELECT p.*, r.first_name, r.last_name, r.email
     FROM $packages_table p
     LEFT JOIN {$wpdb->prefix}" . AIHS_RESPONSES_TABLE . " r ON p.response_id = r.id
     ORDER BY p.created_at DESC
     LIMIT 20"
);
?>

<div class="wrap">
    <h1><?php _e('Health Packages Management', 'ai-health-savetnik'); ?></h1>

    <!-- Package Statistics -->
    <div class="aihs-packages-stats">
        <h2><?php _e('Package Statistics', 'ai-health-savetnik'); ?></h2>
        <div class="aihs-stats-grid">
            <?php foreach ($package_stats as $type => $stats): ?>
                <div class="aihs-stat-card">
                    <div class="aihs-stat-number"><?php echo $stats['count']; ?></div>
                    <div class="aihs-stat-label"><?php echo esc_html($stats['label']); ?></div>
                </div>
            <?php endforeach; ?>

            <div class="aihs-stat-card">
                <div class="aihs-stat-number"><?php echo array_sum(array_column($package_stats, 'count')); ?></div>
                <div class="aihs-stat-label"><?php _e('Total Packages', 'ai-health-savetnik'); ?></div>
            </div>
        </div>
    </div>

    <!-- Package Configuration -->
    <div class="aihs-package-config">
        <h2><?php _e('Package Configuration', 'ai-health-savetnik'); ?></h2>

        <div class="aihs-config-sections">
            <!-- Discount Settings -->
            <div class="aihs-config-section">
                <h3><?php _e('Discount Settings', 'ai-health-savetnik'); ?></h3>
                <p class="description">
                    <?php _e('Configure discount percentages for different package types.', 'ai-health-savetnik'); ?>
                </p>

                <form method="post" action="options.php">
                    <?php settings_fields('aihs_package_discounts'); ?>
                    <table class="form-table">
                        <?php
                        $package_discounts = get_option('aihs_package_discounts', array());
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
                    <?php submit_button(__('Save Discount Settings', 'ai-health-savetnik')); ?>
                </form>
            </div>

            <!-- Package Generation Settings -->
            <div class="aihs-config-section">
                <h3><?php _e('Automatic Package Generation', 'ai-health-savetnik'); ?></h3>
                <p class="description">
                    <?php _e('Configure how packages are automatically generated for quiz responses.', 'ai-health-savetnik'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-generate Packages', 'ai-health-savetnik'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" checked disabled>
                                <?php _e('Automatically generate packages when AI analysis is completed', 'ai-health-savetnik'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Packages are automatically created based on recommended products from quiz answers.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Create WooCommerce Products', 'ai-health-savetnik'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" checked disabled>
                                <?php _e('Automatically create WooCommerce products for generated packages', 'ai-health-savetnik'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Each package will be created as a WooCommerce product with proper pricing and description.', 'ai-health-savetnik'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Package Types Configuration -->
            <div class="aihs-config-section">
                <h3><?php _e('Package Types', 'ai-health-savetnik'); ?></h3>
                <p class="description">
                    <?php _e('Available package types and their configurations.', 'ai-health-savetnik'); ?>
                </p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Type', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Label', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Product Count', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Current Discount', 'ai-health-savetnik'); ?></th>
                            <th><?php _e('Generated', 'ai-health-savetnik'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($package_types as $type => $info): ?>
                        <tr>
                            <td><code><?php echo esc_html($type); ?></code></td>
                            <td><strong><?php echo esc_html($info['label']); ?></strong></td>
                            <td><?php echo intval($info['product_count']); ?> <?php _e('products', 'ai-health-savetnik'); ?></td>
                            <td><?php echo floatval($package_discounts[$type] ?? 0); ?>%</td>
                            <td><?php echo $package_stats[$type]['count']; ?> <?php _e('packages', 'ai-health-savetnik'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Packages -->
    <div class="aihs-recent-packages">
        <h2><?php _e('Recent Packages', 'ai-health-savetnik'); ?></h2>

        <?php if (empty($recent_packages)): ?>
            <div class="notice notice-info">
                <p><?php _e('No packages generated yet. Packages are automatically created when users complete the health quiz.', 'ai-health-savetnik'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Package Name', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Customer', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Type', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Original Price', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Final Price', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Discount', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Created', 'ai-health-savetnik'); ?></th>
                        <th><?php _e('Actions', 'ai-health-savetnik'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_packages as $package): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($package->package_name); ?></strong>
                            <?php if ($package->wc_product_id): ?>
                                <br><small>
                                    <a href="<?php echo get_permalink($package->wc_product_id); ?>" target="_blank">
                                        <?php _e('View Product', 'ai-health-savetnik'); ?>
                                    </a>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($package->first_name): ?>
                                <strong><?php echo esc_html($package->first_name . ' ' . $package->last_name); ?></strong>
                                <br><small><?php echo esc_html($package->email); ?></small>
                            <?php else: ?>
                                <em><?php _e('Customer data not available', 'ai-health-savetnik'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $type_info = $package_types[$package->package_type] ?? null;
                            echo $type_info ? esc_html($type_info['label']) : esc_html($package->package_type);
                            ?>
                        </td>
                        <td><?php echo aihs_format_price($package->original_price); ?></td>
                        <td>
                            <strong><?php echo aihs_format_price($package->final_price); ?></strong>
                        </td>
                        <td>
                            <?php if ($package->discount_percentage > 0): ?>
                                <span class="aihs-discount-badge">
                                    -<?php echo floatval($package->discount_percentage); ?>%
                                </span>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <abbr title="<?php echo esc_attr(date_i18n('Y/m/d g:i:s A', strtotime($package->created_at))); ?>">
                                <?php echo date_i18n(get_option('date_format'), strtotime($package->created_at)); ?>
                            </abbr>
                        </td>
                        <td>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=aihs-results&action=view&response_id=' . $package->response_id); ?>">
                                        <?php _e('View Response', 'ai-health-savetnik'); ?>
                                    </a>
                                </span>

                                <?php if ($package->wc_product_id): ?>
                                    <span class="edit"> |
                                        <a href="<?php echo admin_url('post.php?post=' . $package->wc_product_id . '&action=edit'); ?>">
                                            <?php _e('Edit Product', 'ai-health-savetnik'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="description">
                <a href="<?php echo admin_url('admin.php?page=aihs-results'); ?>">
                    <?php _e('View all responses and their packages', 'ai-health-savetnik'); ?> &rarr;
                </a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Package Management Tools -->
    <div class="aihs-package-tools">
        <h2><?php _e('Package Management Tools', 'ai-health-savetnik'); ?></h2>

        <div class="aihs-tools-grid">
            <div class="aihs-tool-card">
                <h3><?php _e('Clean Up Packages', 'ai-health-savetnik'); ?></h3>
                <p><?php _e('Remove packages that are older than 30 days and have not been purchased.', 'ai-health-savetnik'); ?></p>
                <button type="button" class="button" onclick="cleanupPackages()">
                    <?php _e('Clean Up Old Packages', 'ai-health-savetnik'); ?>
                </button>
            </div>

            <div class="aihs-tool-card">
                <h3><?php _e('Regenerate Package Prices', 'ai-health-savetnik'); ?></h3>
                <p><?php _e('Update package prices based on current product prices and discount settings.', 'ai-health-savetnik'); ?></p>
                <button type="button" class="button" onclick="regeneratePrices()">
                    <?php _e('Update All Prices', 'ai-health-savetnik'); ?>
                </button>
            </div>

            <div class="aihs-tool-card">
                <h3><?php _e('Package Analytics', 'ai-health-savetnik'); ?></h3>
                <p><?php _e('View detailed analytics about package generation and conversion rates.', 'ai-health-savetnik'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=aihs-reports'); ?>" class="button">
                    <?php _e('View Analytics', 'ai-health-savetnik'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.aihs-packages-stats {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.aihs-stat-card {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
}

.aihs-stat-number {
    font-size: 2em;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
}

.aihs-stat-label {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.aihs-package-config {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-config-sections {
    display: grid;
    gap: 30px;
    margin-top: 20px;
}

.aihs-config-section {
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 20px;
}

.aihs-config-section h3 {
    margin-top: 0;
    border-bottom: 1px solid #e2e4e7;
    padding-bottom: 10px;
}

.aihs-recent-packages {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-discount-badge {
    background: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.aihs-package-tools {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.aihs-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.aihs-tool-card {
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.aihs-tool-card h3 {
    margin-top: 0;
    color: #2271b1;
}

.aihs-tool-card p {
    color: #646970;
    margin-bottom: 15px;
}

@media (max-width: 782px) {
    .aihs-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .aihs-tools-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function cleanupPackages() {
    if (!confirm('<?php echo esc_js(__('Are you sure you want to clean up old packages? This action cannot be undone.', 'ai-health-savetnik')); ?>')) {
        return;
    }

    jQuery.post(ajaxurl, {
        action: 'aihs_cleanup_packages',
        nonce: '<?php echo wp_create_nonce('aihs_cleanup_packages'); ?>'
    }, function(response) {
        if (response.success) {
            alert('<?php echo esc_js(__('Package cleanup completed successfully.', 'ai-health-savetnik')); ?>');
            location.reload();
        } else {
            alert('<?php echo esc_js(__('Error during package cleanup.', 'ai-health-savetnik')); ?>');
        }
    });
}

function regeneratePrices() {
    if (!confirm('<?php echo esc_js(__('Are you sure you want to update all package prices? This will recalculate prices based on current settings.', 'ai-health-savetnik')); ?>')) {
        return;
    }

    jQuery.post(ajaxurl, {
        action: 'aihs_regenerate_prices',
        nonce: '<?php echo wp_create_nonce('aihs_regenerate_prices'); ?>'
    }, function(response) {
        if (response.success) {
            alert('<?php echo esc_js(__('Package prices updated successfully.', 'ai-health-savetnik')); ?>');
            location.reload();
        } else {
            alert('<?php echo esc_js(__('Error updating package prices.', 'ai-health-savetnik')); ?>');
        }
    });
}
</script>