<?php
/**
 * Gösterge Paneli
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 */

if (!defined('WPINC')) {
    die;
}

// Son 30 günlük istatistikleri al
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$stats = Insurance_CRM_Reports::get_summary_stats($start_date, $end_date);
?>

<div class="wrap insurance-crm-wrap">
    <div class="insurance-crm-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="insurance-crm-header-actions">
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=new'); ?>" class="button button-primary"><?php _e('Yeni Müşteri', 'insurance-crm'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=new'); ?>" class="button button-primary"><?php _e('Yeni Poliçe', 'insurance-crm'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=new'); ?>" class="button button-primary"><?php _e('Yeni Görev', 'insurance-crm'); ?></a>
        </div>
    </div>

    <div class="insurance-crm-stats">
        <div class="insurance-crm-stat-card">
            <h3><?php _e('Toplam Poliçe', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo number_format($stats->total_policies); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Son 30 gün', 'insurance-crm'); ?></div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Toplam Prim', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo number_format($stats->total_premium, 2) . ' ₺'; ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Son 30 gün', 'insurance-crm'); ?></div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Yeni Müşteri', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo number_format($stats->new_customers); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Son 30 gün', 'insurance-crm'); ?></div>
        </div>

        <div class="insurance-crm-stat-card">
            <h3><?php _e('Yenileme Oranı', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo number_format($stats->renewal_rate, 1) . '%'; ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Son 30 gün', 'insurance-crm'); ?></div>
        </div>
    </div>

    <div class="insurance-crm-dashboard-widgets">
        <!-- Poliçe Türü Dağılımı -->
        <div class="insurance-crm-widget">
            <h3><?php _e('Poliçe Türü Dağılımı', 'insurance-crm'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Poliçe Türü', 'insurance-crm'); ?></th>
                        <th><?php _e('Adet', 'insurance-crm'); ?></th>
                        <th><?php _e('Oran', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats->policy_type_distribution as $type): ?>
                    <tr>
                        <td><?php echo esc_html($type->label); ?></td>
                        <td><?php echo number_format($type->count); ?></td>
                        <td><?php echo number_format(($type->count / $stats->total_policies) * 100, 1) . '%'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Aylık Prim Dağılımı -->
        <div class="insurance-crm-widget">
            <h3><?php _e('Aylık Prim Dağılımı', 'insurance-crm'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Ay', 'insurance-crm'); ?></th>
                        <th><?php _e('Prim Tutarı', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats->monthly_premium_distribution as $month): ?>
                    <tr>
                        <td><?php echo esc_html($month->month); ?></td>
                        <td><?php echo number_format($month->amount, 2) . ' ₺'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Yaklaşan Görevler -->
        <div class="insurance-crm-widget">
            <h3><?php _e('Yaklaşan Görevler', 'insurance-crm'); ?></h3>
            <?php
            $task = new Insurance_CRM_Task();
            $upcoming_tasks = $task->get_all(array(
                'status' => 'pending',
                'due_date_start' => date('Y-m-d H:i:s'),
                'due_date_end' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'orderby' => 'due_date',
                'order' => 'ASC',
                'limit' => 5
            ));
            ?>
            <?php if (!empty($upcoming_tasks)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Görev', 'insurance-crm'); ?></th>
                            <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                            <th><?php _e('Son Tarih', 'insurance-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_tasks as $task): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task->id); ?>">
                                    <?php echo esc_html(wp_trim_words($task->task_description, 5)); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $task->customer_id); ?>">
                                    <?php echo esc_html($task->first_name . ' ' . $task->last_name); ?>
                                </a>
                            </td>
                            <td><?php echo date_i18n('d.m.Y H:i', strtotime($task->due_date)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('Yaklaşan görev bulunmuyor.', 'insurance-crm'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Süresi Yaklaşan Poliçeler -->
        <div class="insurance-crm-widget">
            <h3><?php _e('Süresi Yaklaşan Poliçeler', 'insurance-crm'); ?></h3>
            <?php
            $policy = new Insurance_CRM_Policy();
            $expiring_policies = $policy->get_all(array(
                'status' => 'aktif',
                'end_date_start' => date('Y-m-d'),
                'end_date_end' => date('Y-m-d', strtotime('+30 days')),
                'orderby' => 'end_date',
                'order' => 'ASC',
                'limit' => 5
            ));
            ?>
            <?php if (!empty($expiring_policies)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Poliçe No', 'insurance-crm'); ?></th>
                            <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                            <th><?php _e('Bitiş Tarihi', 'insurance-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring_policies as $policy): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=edit&id=' . $policy->id); ?>">
                                    <?php echo esc_html($policy->policy_number); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $policy->customer_id); ?>">
                                    <?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?>
                                </a>
                            </td>
                            <td><?php echo date_i18n('d.m.Y', strtotime($policy->end_date)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('Süresi yaklaşan poliçe bulunmuyor.', 'insurance-crm'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>