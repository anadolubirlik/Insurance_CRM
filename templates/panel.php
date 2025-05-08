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
if (!in_array('insurance_representative', (array)$current_user->roles)) {
    wp_redirect(home_url());
    exit;
}

// Müşteri temsilcisi bilgilerini al
global $wpdb;
$representative = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}insurance_crm_representatives 
     WHERE user_id = %d AND status = 'active'",
    $current_user->ID
));

if (!$representative) {
    wp_die('Müşteri temsilcisi kaydınız bulunamadı.');
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

$total_premium = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(premium_amount) FROM {$wpdb->prefix}insurance_crm_policies 
     WHERE representative_id = %d",
    $representative->id
));

$pending_tasks = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, c.first_name, c.last_name 
     FROM {$wpdb->prefix}insurance_crm_tasks t
     LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON t.customer_id = c.id
     WHERE t.representative_id = %d AND t.status = 'pending'
     ORDER BY t.due_date ASC
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

<div class="insurance-crm-dashboard-wrapper">
    <!-- Üst Bar -->
    <div class="top-bar">
        <div class="user-info">
            <span class="welcome">Hoş geldin, <?php echo esc_html($current_user->display_name); ?></span>
            <span class="role"><?php echo esc_html($representative->title); ?></span>
        </div>
        <div class="actions">
            <a href="<?php echo wp_logout_url(home_url('/temsilci-girisi')); ?>" class="logout-button">Çıkış Yap</a>
        </div>
    </div>

    <!-- Ana İstatistikler -->
    <div class="stats-grid">
        <div class="stat-box">
            <h3>Toplam Müşteri</h3>
            <div class="stat-value"><?php echo number_format($total_customers); ?></div>
        </div>
        <div class="stat-box">
            <h3>Toplam Poliçe</h3>
            <div class="stat-value"><?php echo number_format($total_policies); ?></div>
        </div>
        <div class="stat-box">
            <h3>Toplam Üretim</h3>
            <div class="stat-value">₺<?php echo number_format($total_premium, 2); ?></div>
        </div>
        <div class="stat-box">
            <h3>Aylık Hedef</h3>
            <div class="stat-value">₺<?php echo number_format($representative->monthly_target, 2); ?></div>
            <?php
            $achievement_rate = $representative->monthly_target > 0 ? 
                              ($total_premium / $representative->monthly_target) * 100 : 0;
            ?>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo min(100, $achievement_rate); ?>%"></div>
            </div>
            <div class="progress-label"><?php echo number_format($achievement_rate, 1); ?>%</div>
        </div>
    </div>

    <!-- Grafik ve Görevler Grid -->
    <div class="dashboard-grid">
        <!-- Üretim Grafiği -->
        <div class="chart-box">
            <h3>Son 6 Aylık Üretim</h3>
            <canvas id="productionChart"></canvas>
        </div>

        <!-- Bekleyen Görevler -->
        <div class="tasks-box">
            <h3>Bekleyen Görevler</h3>
            <?php if (!empty($pending_tasks)): ?>
                <ul class="tasks-list">
                    <?php foreach ($pending_tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-info">
                                <span class="task-customer">
                                    <?php echo esc_html($task->first_name . ' ' . $task->last_name); ?>
                                </span>
                                <span class="task-desc">
                                    <?php echo esc_html($task->task_description); ?>
                                </span>
                            </div>
                            <div class="task-date">
                                <?php echo date('d.m.Y H:i', strtotime($task->due_date)); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-tasks">Bekleyen görev bulunmuyor.</p>
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
    const data = productionData.map(item => item.total);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Aylık Üretim (₺)',
                data: data,
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
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
.insurance-crm-dashboard-wrapper {
    padding: 20px;
    background: #f5f5f5;
    min-height: 100vh;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    flex-direction: column;
}

.welcome {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.role {
    font-size: 14px;
    color: #666;
}

.logout-button {
    padding: 8px 16px;
    background: #dc3545;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-box h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.progress-bar {
    height: 8px;
    background: #eee;
    border-radius: 4px;
    margin: 10px 0;
    overflow: hidden;
}

.progress {
    height: 100%;
    background: #28a745;
    transition: width 0.3s ease;
}

.progress-label {
    font-size: 14px;
    color: #666;
    text-align: right;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.chart-box, .tasks-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-box {
    height: 400px;
}

.tasks-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.task-item {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.task-item:last-child {
    border-bottom: none;
}

.task-info {
    display: flex;
    flex-direction: column;
}

.task-customer {
    font-weight: 600;
    color: #333;
}

.task-desc {
    font-size: 14px;
    color: #666;
    margin-top: 4px;
}

.task-date {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-box {
        height: 300px;
    }
}
</style>