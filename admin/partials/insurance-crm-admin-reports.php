<?php
/**
 * Raporlar Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.0 (2025-05-02)
 */

if (!defined('WPINC')) {
    die;
}

// Raporlama sınıfını başlat
$reports = new Insurance_CRM_Reports();

// Tarih filtrelerini al
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$policy_type = isset($_GET['policy_type']) ? sanitize_text_field($_GET['policy_type']) : '';

// İstatistikleri al
$summary_stats = $reports->get_summary_stats($start_date, $end_date, $policy_type);
$task_stats = $reports->get_task_stats($start_date, $end_date);
$customer_stats = $reports->get_customer_stats($start_date, $end_date);
?>

<div class="wrap insurance-crm-wrap">
    <div class="insurance-crm-header">
        <h1><?php _e('Raporlar', 'insurance-crm'); ?></h1>
    </div>

    <!-- Filtre Formu -->
    <div class="insurance-crm-filters">
        <form method="get" class="insurance-crm-filter-form">
            <input type="hidden" name="page" value="insurance-crm-reports">
            
            <div class="filter-row">
                <label for="start_date"><?php _e('Başlangıç Tarihi:', 'insurance-crm'); ?></label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                
                <label for="end_date"><?php _e('Bitiş Tarihi:', 'insurance-crm'); ?></label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                
                <label for="policy_type"><?php _e('Poliçe Türü:', 'insurance-crm'); ?></label>
                <select name="policy_type" id="policy_type">
                    <option value=""><?php _e('Tümü', 'insurance-crm'); ?></option>
                    <?php
                    $settings = get_option('insurance_crm_settings');
                    foreach ($settings['default_policy_types'] as $type) {
                        echo sprintf(
                            '<option value="%s" %s>%s</option>',
                            $type,
                            selected($policy_type, $type, false),
                            ucfirst($type)
                        );
                    }
                    ?>
                </select>
                
                <?php submit_button(__('Filtrele', 'insurance-crm'), 'primary', 'submit', false); ?>
                
                <div class="export-buttons">
                    <button type="submit" name="export" value="pdf" class="button">
                        <?php _e('PDF İndir', 'insurance-crm'); ?>
                    </button>
                    <button type="submit" name="export" value="excel" class="button">
                        <?php _e('Excel İndir', 'insurance-crm'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Özet İstatistikler -->
    <div class="insurance-crm-stats">
        <div class="insurance-crm-stat-card">
            <h3><?php _e('Toplam Poliçe', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number">
                <?php echo number_format($summary_stats->total_policies); ?>
            </div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Toplam Prim', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number">
                <?php echo number_format($summary_stats->total_premium, 2) . ' ₺'; ?>
            </div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Yeni Müşteri', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number">
                <?php echo number_format($summary_stats->new_customers); ?>
            </div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Yenileme Oranı', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number">
                <?php echo number_format($summary_stats->renewal_rate, 1) . '%'; ?>
            </div>
        </div>
    </div>

    <div class="insurance-crm-report-sections">
        <!-- Poliçe Türü Dağılımı -->
        <div class="insurance-crm-report-section">
            <h3><?php _e('Poliçe Türü Dağılımı', 'insurance-crm'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Poliçe Türü', 'insurance-crm'); ?></th>
                        <th><?php _e('Adet', 'insurance-crm'); ?></th>
                        <th><?php _e('Toplam Prim', 'insurance-crm'); ?></th>
                        <th><?php _e('Oran', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary_stats->policy_type_distribution as $type): ?>
                    <tr>
                        <td><?php echo esc_html($type->label); ?></td>
                        <td><?php echo number_format($type->count); ?></td>
                        <td><?php echo number_format($type->total_premium, 2) . ' ₺'; ?></td>
                        <td><?php echo number_format(($type->count / $summary_stats->total_policies) * 100, 1) . '%'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Görev İstatistikleri -->
        <div class="insurance-crm-report-section">
            <h3><?php _e('Görev İstatistikleri', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stats">
                <div class="insurance-crm-stat-card">
                    <h4><?php _e('Bekleyen Görevler', 'insurance-crm'); ?></h4>
                    <div class="insurance-crm-stat-number">
                        <?php 
                        $pending_tasks = array_filter($task_stats->status_distribution, function($item) {
                            return $item->status === 'pending';
                        });
                        echo !empty($pending_tasks) ? current($pending_tasks)->count : 0;
                        ?>
                    </div>
                </div>

                <div class="insurance-crm-stat-card">
                    <h4><?php _e('Geciken Görevler', 'insurance-crm'); ?></h4>
                    <div class="insurance-crm-stat-number insurance-crm-text-danger">
                        <?php echo number_format($task_stats->overdue_tasks); ?>
                    </div>
                </div>

                <div class="insurance-crm-stat-card">
                    <h4><?php _e('Tamamlanan Görevler', 'insurance-crm'); ?></h4>
                    <div class="insurance-crm-stat-number insurance-crm-text-success">
                        <?php 
                        $completed_tasks = array_filter($task_stats->status_distribution, function($item) {
                            return $item->status === 'completed';
                        });
                        echo !empty($completed_tasks) ? current($completed_tasks)->count : 0;
                        ?>
                    </div>
                </div>

                <div class="insurance-crm-stat-card">
                    <h4><?php _e('Ortalama Tamamlanma Süresi', 'insurance-crm'); ?></h4>
                    <div class="insurance-crm-stat-number">
                        <?php echo number_format($task_stats->avg_completion_time, 1) . ' ' . __('saat', 'insurance-crm'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Müşteri İstatistikleri -->
        <div class="insurance-crm-report-section">
            <h3><?php _e('Müşteri İstatistikleri', 'insurance-crm'); ?></h3>
            
            <!-- Kategori Dağılımı -->
            <div class="insurance-crm-subsection">
                <h4><?php _e('Kategori Dağılımı', 'insurance-crm'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Kategori', 'insurance-crm'); ?></th>
                            <th><?php _e('Müşteri Sayısı', 'insurance-crm'); ?></th>
                            <th><?php _e('Oran', 'insurance-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_stats->category_distribution as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category->label); ?></td>
                            <td><?php echo number_format($category->count); ?></td>
                            <td><?php echo number_format(($category->count / $customer_stats->total_customers) * 100, 1) . '%'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- En Çok Poliçesi Olan Müşteriler -->
            <div class="insurance-crm-subsection">
                <h4><?php _e('En Çok Poliçesi Olan Müşteriler', 'insurance-crm'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                            <th><?php _e('Poliçe Sayısı', 'insurance-crm'); ?></th>
                            <th><?php _e('Toplam Prim', 'insurance-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_stats->top_customers as $customer): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $customer->id); ?>">
                                    <?php echo esc_html($customer->customer_name); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($customer->policy_count); ?></td>
                            <td><?php echo number_format($customer->total_premium, 2) . ' ₺'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detaylı Poliçe Listesi -->
        <div class="insurance-crm-report-section">
            <h3><?php _e('Detaylı Poliçe Listesi', 'insurance-crm'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Poliçe No', 'insurance-crm'); ?></th>
                        <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                        <th><?php _e('Tür', 'insurance-crm'); ?></th>
                        <th><?php _e('Başlangıç', 'insurance-crm'); ?></th>
                        <th><?php _e('Bitiş', 'insurance-crm'); ?></th>
                        <th><?php _e('Prim', 'insurance-crm'); ?></th>
                        <th><?php _e('Durum', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $policies = $reports->get_detailed_policies($start_date, $end_date, $policy_type);
                    foreach ($policies as $policy):
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=edit&id=' . $policy->id); ?>">
                                <?php echo esc_html($policy->policy_number); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($policy->customer_name); ?></td>
                        <td><?php echo esc_html($policy->policy_type); ?></td>
                        <td><?php echo date_i18n('d.m.Y', strtotime($policy->start_date)); ?></td>
                        <td><?php echo date_i18n('d.m.Y', strtotime($policy->end_date)); ?></td>
                        <td><?php echo number_format($policy->premium_amount, 2) . ' ₺'; ?></td>
                        <td>
                            <span class="insurance-crm-badge insurance-crm-badge-<?php echo $policy->status === 'aktif' ? 'success' : 'danger'; ?>">
                                <?php echo esc_html($policy->status); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>