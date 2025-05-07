<?php
/**
 * Admin ana görünüm dosyası
 */
?>
<div class="wrap insurance-crm-wrap">
    <div class="insurance-crm-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>

    <?php
    $stats = array(
        'customers' => Insurance_CRM_Customer::get_stats(),
        'policies' => Insurance_CRM_Policy::get_stats(),
        'tasks' => Insurance_CRM_Task::get_stats()
    );
    ?>

    <div class="insurance-crm-stats">
        <!-- Müşteri İstatistikleri -->
        <div class="insurance-crm-stat-card">
            <h3><?php _e('Müşteriler', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo esc_html($stats['customers']->total); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Toplam Müşteri', 'insurance-crm'); ?></div>
            <div class="insurance-crm-stat-number text-success"><?php echo esc_html($stats['customers']->active); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Aktif Müşteri', 'insurance-crm'); ?></div>
        </div>

        <!-- Poliçe İstatistikleri -->
        <div class="insurance-crm-stat-card">
            <h3><?php _e('Poliçeler', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo esc_html($stats['policies']->total); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Toplam Poliçe', 'insurance-crm'); ?></div>
            <div class="insurance-crm-stat-number text-success"><?php echo number_format($stats['policies']->total_premium, 2); ?> TL</div>
            <div class="insurance-crm-stat-label"><?php _e('Toplam Prim', 'insurance-crm'); ?></div>
        </div>

        <!-- Görev İstatistikleri -->
        <div class="insurance-crm-stat-card">
            <h3><?php _e('Görevler', 'insurance-crm'); ?></h3>
            <div class="insurance-crm-stat-number"><?php echo esc_html($stats['tasks']->pending); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Bekleyen Görev', 'insurance-crm'); ?></div>
            <div class="insurance-crm-stat-number text-success"><?php echo esc_html($stats['tasks']->completed); ?></div>
            <div class="insurance-crm-stat-label"><?php _e('Tamamlanan Görev', 'insurance-crm'); ?></div>
        </div>
    </div>

    <!-- Yaklaşan Yenilemeler -->
    <div class="insurance-crm-table-container">
        <h2><?php _e('Yaklaşan Poliçe Yenilemeleri', 'insurance-crm'); ?></h2>
        <?php
        $upcoming_renewals = Insurance_CRM_Policy::get_upcoming_renewals();
        if (!empty($upcoming_renewals)): ?>
            <table class="insurance-crm-table">
                <thead>
                    <tr>
                        <th><?php _e('Poliçe No', 'insurance-crm'); ?></th>
                        <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                        <th><?php _e('Tür', 'insurance-crm'); ?></th>
                        <th><?php _e('Bitiş Tarihi', 'insurance-crm'); ?></th>
                        <th><?php _e('Prim', 'insurance-crm'); ?></th>
                        <th><?php _e('İşlemler', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_renewals as $policy): ?>
                        <tr>
                            <td><?php echo esc_html($policy->policy_number); ?></td>
                            <td><?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?></td>
                            <td><?php echo esc_html($policy->policy_type); ?></td>
                            <td><?php echo esc_html($policy->end_date); ?></td>
                            <td><?php echo number_format($policy->premium_amount, 2); ?> TL</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=renew&id=' . $policy->id); ?>" class="button button-primary">
                                    <?php _e('Yenile', 'insurance-crm'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('Yaklaşan poliçe yenilemesi bulunmuyor.', 'insurance-crm'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Bugünkü Görevler -->
    <div class="insurance-crm-table-container">
        <h2><?php _e('Bugünkü Görevler', 'insurance-crm'); ?></h2>
        <?php
        $today_tasks = Insurance_CRM_Task::get_today_tasks();
        if (!empty($today_tasks)): ?>
            <table class="insurance-crm-table">
                <thead>
                    <tr>
                        <th><?php _e('Görev', 'insurance-crm'); ?></th>
                        <th><?php _e('Müşteri', 'insurance-crm'); ?></th>
                        <th><?php _e('Poliçe', 'insurance-crm'); ?></th>
                        <th><?php _e('Durum', 'insurance-crm'); ?></th>
                        <th><?php _e('İşlemler', 'insurance-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_tasks as $task): ?>
                        <tr>
                            <td><?php echo esc_html($task->task_description); ?></td>
                            <td><?php echo esc_html($task->first_name . ' ' . $task->last_name); ?></td>
                            <td><?php echo $task->policy_number ? esc_html($task->policy_number) : '-'; ?></td>
                            <td>
                                <span class="insurance-crm-badge insurance-crm-badge-<?php echo $task->status === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo esc_html($task->status === 'completed' ? __('Tamamlandı', 'insurance-crm') : __('Bekliyor', 'insurance-crm')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task->status !== 'completed'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=insurance-crm-tasks&action=complete&id=' . $task->id), 'complete_task_' . $task->id); ?>" class="button button-primary">
                                        <?php _e('Tamamla', 'insurance-crm'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('Bugün için planlanmış görev bulunmuyor.', 'insurance-crm'); ?></p>
        <?php endif; ?>
    </div>
</div>