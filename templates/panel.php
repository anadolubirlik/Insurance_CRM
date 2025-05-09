<?php
if (!defined('ABSPATH')) {
    exit;
}

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/temsilci-girisi/'));
    exit;
}

// Kullanıcı müşteri temsilcisi değilse ana sayfaya yönlendir
$user = wp_get_current_user();
if (!in_array('insurance_representative', (array)$user->roles)) {
    wp_safe_redirect(home_url());
    exit;
}

// Mevcut kullanıcıyı al
$current_user = wp_get_current_user();

// Müşteri temsilcisi bilgilerini al
global $wpdb;
$representative = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}insurance_crm_representatives 
     WHERE user_id = %d AND status = 'active'",
    $current_user->ID
));

if (!$representative) {
    wp_die('Müşteri temsilcisi kaydınız bulunamadı veya hesabınız pasif durumda.');
}

// İstatistikleri hesapla
$total_customers = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_customers 
     WHERE representative_id = %d",
    $representative->id
));

$total_policies = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_policies 
     WHERE representative_id = %d",
    $representative->id
));

// Toplam üretim
$total_premium = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(premium_amount) FROM {$wpdb->prefix}insurance_crm_policies 
     WHERE representative_id = %d",
    $representative->id
));

if ($total_premium === null) $total_premium = 0;

// Bekleyen görevler
$pending_tasks = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, c.first_name, c.last_name 
     FROM {$wpdb->prefix}insurance_crm_tasks t
     LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON t.customer_id = c.id
     WHERE t.representative_id = %d AND t.status = 'pending'
     ORDER BY t.due_date ASC
     LIMIT 5",
    $representative->id
));

// Cari ay için üretim
$current_month = date('Y-m');
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$current_month_premium = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(premium_amount) FROM {$wpdb->prefix}insurance_crm_policies
     WHERE representative_id = %d 
     AND created_at BETWEEN %s AND %s",
    $representative->id,
    $current_month_start . ' 00:00:00',
    $current_month_end . ' 23:59:59'
));

if ($current_month_premium === null) $current_month_premium = 0;

// Aylık hedef gerçekleşme oranı
$monthly_target = $representative->monthly_target > 0 ? $representative->monthly_target : 1;
$achievement_rate = ($current_month_premium / $monthly_target) * 100;
$achievement_rate = min(100, $achievement_rate); // 100% üzerini gösterme

// Son poliçeler
$recent_policies = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, c.first_name, c.last_name
     FROM {$wpdb->prefix}insurance_crm_policies p
     LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
     WHERE p.representative_id = %d
     ORDER BY p.created_at DESC
     LIMIT 5",
    $representative->id
));

// Son 6 ayın üretim verilerini al
$monthly_production = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(premium_amount) as total
     FROM {$wpdb->prefix}insurance_crm_policies
     WHERE representative_id = %d
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month ASC",
    $representative->id
));
?>

<div class="insurance-crm-dashboard">
    <!-- Üst Bar -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <div class="user-avatar">
                <?php echo get_avatar($current_user->ID, 64); ?>
            </div>
            <div class="user-info">
                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                <span class="user-role"><?php echo esc_html($representative->title); ?> | <?php echo esc_html($representative->department); ?></span>
            </div>
        </div>
        <div class="top-actions">
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-profile'); ?>" class="action-button profile-button">
                <i class="dashicons dashicons-admin-users"></i> Profilim
            </a>
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-notifications'); ?>" class="action-button notification-button">
                <i class="dashicons dashicons-bell"></i>
                <span class="notification-count">3</span>
            </a>
            <a href="<?php echo wp_logout_url(home_url('/temsilci-girisi')); ?>" class="action-button logout-button">
                <i class="dashicons dashicons-exit"></i> Çıkış
            </a>
        </div>
    </div>

    <!-- Ana İçerik -->
    <div class="dashboard-content">
        <!-- İstatistikler -->
        <div class="stats-grid">
            <div class="stat-box customers-box">
                <div class="stat-icon">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                    <div class="stat-label">Toplam Müşteri</div>
                </div>
            </div>
            
            <div class="stat-box policies-box">
                <div class="stat-icon">
                    <i class="dashicons dashicons-portfolio"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?php echo number_format($total_policies); ?></div>
                    <div class="stat-label">Toplam Poliçe</div>
                </div>
            </div>
            
            <div class="stat-box production-box">
                <div class="stat-icon">
                    <i class="dashicons dashicons-chart-bar"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value">₺<?php echo number_format($total_premium, 2); ?></div>
                    <div class="stat-label">Toplam Üretim</div>
                </div>
            </div>
            
            <div class="stat-box target-box">
                <div class="stat-icon">
                    <i class="dashicons dashicons-performance"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value">₺<?php echo number_format($monthly_target, 2); ?></div>
                    <div class="stat-label">Aylık Hedef</div>
                </div>
            </div>
        </div>
        
        <!-- Hedef Gerçekleşme -->
        <div class="target-achievement-section">
            <div class="target-header">
                <h3>Aylık Hedef Gerçekleşme - <?php echo date_i18n('F Y'); ?></h3>
                <div class="achievement-percentage"><?php echo number_format($achievement_rate, 1); ?>%</div>
            </div>
            
            <div class="target-progress-container">
                <div class="target-progress-bar">
                    <div class="target-progress" style="width: <?php echo $achievement_rate; ?>%"></div>
                </div>
                <div class="target-values">
                    <div class="target-current">₺<?php echo number_format($current_month_premium, 2); ?></div>
                    <div class="target-goal">₺<?php echo number_format($monthly_target, 2); ?></div>
                </div>
            </div>
            
            <div class="target-footer">
                <div class="days-remaining">
                    <?php 
                    $days_in_month = date('t');
                    $current_day = date('j');
                    $days_remaining = $days_in_month - $current_day;
                    echo sprintf('Bu ay için kalan gün: <strong>%d</strong>', $days_remaining); 
                    ?>
                </div>
                <div class="target-daily-need">
                    <?php
                    $remaining_target = $monthly_target - $current_month_premium;
                    $daily_need = $days_remaining > 0 ? $remaining_target / $days_remaining : 0;
                    if ($remaining_target > 0) {
                        echo sprintf('Günlük hedef gerçekleştirme ihtiyacı: <strong>₺%s</strong>', number_format($daily_need, 2));
                    } else {
                        echo '<span class="target-complete">Tebrikler! Aylık hedefinizi tamamladınız.</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Grafik ve Görevler Grid -->
        <div class="dashboard-main-grid">
            <!-- Üretim Grafiği -->
            <div class="chart-box">
                <div class="box-header">
                    <h3>Son 6 Aylık Üretim</h3>
                    <div class="box-actions">
                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-reports'); ?>" class="box-action">
                            <i class="dashicons dashicons-visibility"></i> Detaylı Rapor
                        </a>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="productionChart"></canvas>
                </div>
            </div>

            <!-- Bekleyen Görevler -->
            <div class="tasks-box">
                <div class="box-header">
                    <h3>Bekleyen Görevler</h3>
                    <div class="box-actions">
                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks'); ?>" class="box-action">
                            <i class="dashicons dashicons-list-view"></i> Tümünü Gör
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=new'); ?>" class="box-action">
                            <i class="dashicons dashicons-plus-alt"></i> Yeni Ekle
                        </a>
                    </div>
                </div>
                <?php if (!empty($pending_tasks)): ?>
                    <ul class="tasks-list">
                        <?php foreach ($pending_tasks as $task): 
                            // Öncelik renkleri
                            $priority_class = '';
                            switch ($task->priority) {
                                case 'high':
                                    $priority_class = 'priority-high';
                                    $priority_text = 'Yüksek';
                                    break;
                                case 'medium':
                                    $priority_class = 'priority-medium';
                                    $priority_text = 'Orta';
                                    break;
                                default:
                                    $priority_class = 'priority-low';
                                    $priority_text = 'Düşük';
                            }
                            
                            // Kalan gün hesaplama
                            $due_date = new DateTime($task->due_date);
                            $now = new DateTime();
                            $interval = $now->diff($due_date);
                            $days_left = $interval->days;
                            $is_past_due = $due_date < $now;
                        ?>
                            <li class="task-item <?php echo $priority_class; ?> <?php echo $is_past_due ? 'past-due' : ''; ?>">
                                <div class="task-priority" title="<?php echo $priority_text; ?> Öncelik"></div>
                                <div class="task-content">
                                    <span class="task-customer">
                                        <?php echo esc_html($task->first_name . ' ' . $task->last_name); ?>
                                    </span>
                                    <span class="task-desc">
                                        <?php echo esc_html($task->task_description); ?>
                                    </span>
                                </div>
                                <div class="task-meta">
                                    <div class="task-date <?php echo $is_past_due ? 'past-due' : ''; ?>">
                                        <i class="dashicons dashicons-calendar-alt"></i>
                                        <?php 
                                        if ($is_past_due) {
                                            echo '<span class="overdue-label">Gecikmiş</span> ';
                                            echo date_i18n('d.m.Y', strtotime($task->due_date));
                                        } else {
                                            echo date_i18n('d.m.Y', strtotime($task->due_date));
                                            echo '<span class="days-left">' . $days_left . ' gün</span>';
                                        }
                                        ?>
                                    </div>
                                    <div class="task-actions">
                                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task->id); ?>" title="Düzenle"><i class="dashicons dashicons-edit"></i></a>
                                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=complete&id=' . $task->id . '&_wpnonce=' . wp_create_nonce('complete_task_' . $task->id)); ?>" title="Tamamla"><i class="dashicons dashicons-yes-alt"></i></a>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-tasks-message">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <p>Bekleyen göreviniz bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Son Eklenen Poliçeler -->
        <div class="recent-policies-section">
            <div class="box-header">
                <h3>Son Eklenen Poliçeler</h3>
                <div class="box-actions">
                    <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies'); ?>" class="box-action">
                        <i class="dashicons dashicons-list-view"></i> Tümünü Gör
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=new'); ?>" class="box-action">
                        <i class="dashicons dashicons-plus-alt"></i> Yeni Ekle
                    </a>
                </div>
            </div>
            
            <?php if (!empty($recent_policies)): ?>
                <div class="recent-policies-table-container">
                    <table class="recent-policies-table">
                        <thead>
                            <tr>
                                <th>Poliçe No</th>
                                <th>Müşteri</th>
                                <th>Tür</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_policies as $policy): ?>
                                <tr>
                                    <td><a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=edit&id=' . $policy->id); ?>"><?php echo esc_html($policy->policy_number); ?></a></td>
                                    <td><?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?></td>
                                    <td><?php echo esc_html(ucfirst($policy->policy_type)); ?></td>
                                    <td><?php echo date_i18n('d.m.Y', strtotime($policy->start_date)); ?></td>
                                    <td><?php echo date_i18n('d.m.Y', strtotime($policy->end_date)); ?></td>
                                    <td class="policy-amount">₺<?php echo number_format($policy->premium_amount, 2); ?></td>
                                    <td>
                                        <span class="policy-status status-<?php echo esc_attr($policy->status); ?>">
                                            <?php echo esc_html(ucfirst($policy->status)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-policies-message">
                    <i class="dashicons dashicons-portfolio"></i>
                    <p>Henüz hiç poliçe eklenmemiş.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js kütüphanesini ekle -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Üretim grafiği
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productionChart').getContext('2d');
    
    const productionData = <?php echo json_encode($monthly_production); ?>;
    const labels = productionData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('tr-TR', { month: 'long', year: 'numeric' });
    });
    const data = productionData.map(item => parseFloat(item.total));
    
    // Hedef çizgisi için veri oluştur
    const targets = Array(labels.length).fill(<?php echo $monthly_target; ?>);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Aylık Üretim (₺)',
                    data: data,
                    backgroundColor: 'rgba(0, 115, 170, 0.8)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                },
                {
                    label: 'Aylık Hedef (₺)',
                    data: targets,
                    type: 'line',
                    backgroundColor: 'transparent',
                    borderColor: 'rgba(255, 99, 132, 0.8)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointStyle: 'line',
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₺' + value.toLocaleString('tr-TR');
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
/* Ana Dashboard Stilleri */
.insurance-crm-dashboard {
    background-color: #f0f2f5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #333;
    padding: 0;
    margin: 0;
    min-height: 100vh;
}

/* Header Bölümü */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.welcome-section {
    display: flex;
    align-items: center;
}

.user-avatar {
    margin-right: 15px;
}

.user-avatar img {
    border-radius: 50%;
    border: 2px solid #0073aa;
}

.user-info h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #23282d;
}

.user-role {
    color: #646970;
    font-size: 14px;
}

.top-actions {
    display: flex;
    gap: 10px;
}

.action-button {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: #fff;
    color: #5a5a5a;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s ease;
}

.action-button:hover {
    background: #f8f8f8;
    color: #333;
}

.action-button .dashicons {
    margin-right: 5px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.notification-button {
    position: relative;
}

.notification-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #d63638;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.logout-button {
    background: #f8f8f8;
}

.logout-button:hover {
    background: #f0f0f0;
    color: #d63638;
}

/* Ana İçerik */
.dashboard-content {
    padding: 30px;
}

/* İstatistik Kutuları */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.stat-icon {
    margin-right: 20px;
    padding: 12px;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: white;
}

.customers-box .stat-icon {
    background: linear-gradient(135deg, #2979ff, #1565c0);
}

.policies-box .stat-icon {
    background: linear-gradient(135deg, #00b0ff, #0091ea);
}

.production-box .stat-icon {
    background: linear-gradient(135deg, #00e676, #00c853);
}

.target-box .stat-icon {
    background: linear-gradient(135deg, #ff9100, #ff6d00);
}

.stat-details {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #23282d;
}

.stat-label {
    font-size: 14px;
    color: #646970;
}

/* Hedef Gerçekleşme Bölümü */
.target-achievement-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.target-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.target-header h3 {
    margin: 0;
    color: #23282d;
    font-size: 16px;
    font-weight: 600;
}

.achievement-percentage {
    font-size: 24px;
    font-weight: 600;
    color: #0073aa;
}

.target-progress-container {
    margin-bottom: 15px;
}

.target-progress-bar {
    height: 12px;
    background: #f0f0f0;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}

.target-progress {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #00a0d2);
    border-radius: 6px;
    transition: width 1s ease-in-out;
}

.target-values {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.target-current {
    color: #0073aa;
    font-weight: 600;
}

.target-goal {
    color: #646970;
}

.target-footer {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #646970;
    margin-top: 15px;
}

.target-complete {
    color: #00a32a;
    font-weight: 600;
}

/* Ana Grid */
.dashboard-main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-box, .tasks-box {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    overflow: hidden;
}

.box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.box-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
}

.box-actions {
    display: flex;
    gap: 10px;
}

.box-action {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #646970;
    text-decoration: none;
    transition: color 0.2s ease;
}

.box-action:hover {
    color: #0073aa;
}

.box-action .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 3px;
}

/* Grafik Kutusu */
.chart-container {
    padding: 20px;
    height: 350px;
}

/* Görev Listesi */
.tasks-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.task-item {
    display: flex;
    border-bottom: 1px solid #f0f0f0;
    padding: 0;
    position: relative;
}

.task-item:last-child {
    border-bottom: none;
}

.task-priority {
    width: 5px;
    height: auto;
}

.priority-high .task-priority {
    background-color: #d63638;
}

.priority-medium .task-priority {
    background-color: #ff9800;
}

.priority-low .task-priority {
    background-color: #00a32a;
}

.task-content {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.task-customer {
    font-weight: 600;
    margin-bottom: 5px;
    color: #23282d;
}

.task-desc {
    font-size: 13px;
    color: #646970;
}

.task-meta {
    padding: 15px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
}

.task-date {
    font-size: 12px;
    color: #646970;
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.task-date .dashicons {
    margin-right: 5px;
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.days-left {
    margin-left: 5px;
    background: #f0f0f0;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
}

.task-date.past-due {
    color: #d63638;
}

.overdue-label {
    background: #d63638;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    margin-right: 5px;
}

.task-actions {
    display: flex;
    gap: 5px;
}

.task-actions a {
    color: #646970;
    text-decoration: none;
}

.task-actions a:hover {
    color: #0073aa;
}

.task-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.no-tasks-message, .no-policies-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    color: #646970;
}

.no-tasks-message .dashicons, .no-policies-message .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    margin-bottom: 10px;
    color: #00a32a;
}

/* Son Poliçeler */
.recent-policies-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    overflow: hidden;
}

.recent-policies-table-container {
    overflow-x: auto;
}

.recent-policies-table {
    width: 100%;
    border-collapse: collapse;
}

.recent-policies-table thead th {
    font-size: 13px;
    color: #646970;
    font-weight: normal;
    text-align: left;
    padding: 15px 20px;
    background: #f9f9f9;
}

.recent-policies-table tbody td {
    padding: 15px 20px;
    font-size: 14px;
    border-bottom: 1px solid #f0f0f0;
}

.recent-policies-table tbody tr:last-child td {
    border-bottom: none;
}

.recent-policies-table a {
    color: #0073aa;
    text-decoration: none;
}

.recent-policies-table a:hover {
    text-decoration: underline;
}

.policy-amount {
    font-weight: 600;
    color: #00a32a;
}

.policy-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    text-transform: capitalize;
}

.status-aktif {
    background-color: #edfaef;
    color: #00a32a;
}

.status-iptal {
    background-color: #fcf0f1;
    color: #d63638;
}

.status-beklemede {
    background-color: #fcf8e3;
    color: #d97706;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .dashboard-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .top-actions {
        margin-top: 15px;
        width: 100%;
        justify-content: flex-end;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-content {
        padding: 15px;
    }
    
    .target-achievement-section, .target-footer {
        flex-direction: column;
    }
    
    .target-footer > div:first-child {
        margin-bottom: 5px;
    }
    
    .recent-policies-table th:nth-child(4), 
    .recent-policies-table td:nth-child(4), 
    .recent-policies-table th:nth-child(5), 
    .recent-policies-table td:nth-child(5) {
        display: none;
    }
}
</style>