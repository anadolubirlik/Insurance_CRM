<?php
/**
 * Müşteri Detay Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @version    1.0.5
 */

if (!defined('WPINC')) {
    die;
}

// Müşteri ID kontrolü
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customer_id) {
    echo '<div class="notice notice-error"><p>Geçersiz müşteri ID\'si.</p></div>';
    return;
}

// Müşteri bilgilerini al
global $wpdb;
$customers_table = $wpdb->prefix . 'insurance_crm_customers';
$customer = $wpdb->get_row($wpdb->prepare("
    SELECT c.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           r.id AS rep_id,
           u.display_name AS rep_name
    FROM $customers_table c
    LEFT JOIN {$wpdb->prefix}insurance_crm_representatives r ON c.representative_id = r.id
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE c.id = %d
", $customer_id));

if (!$customer) {
    echo '<div class="notice notice-error"><p>Müşteri bulunamadı.</p></div>';
    return;
}

// Müşterinin poliçelerini al
$policies_table = $wpdb->prefix . 'insurance_crm_policies';
$policies = $wpdb->get_results($wpdb->prepare("
    SELECT p.*,
           r.id AS rep_id,
           u.display_name AS rep_name
    FROM $policies_table p
    LEFT JOIN {$wpdb->prefix}insurance_crm_representatives r ON p.representative_id = r.id
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE p.customer_id = %d
    ORDER BY p.end_date ASC
", $customer_id));

// Müşterinin görevlerini al
$tasks_table = $wpdb->prefix . 'insurance_crm_tasks';
$tasks = $wpdb->get_results($wpdb->prepare("
    SELECT t.*,
           p.policy_number,
           r.id AS rep_id,
           u.display_name AS rep_name
    FROM $tasks_table t
    LEFT JOIN $policies_table p ON t.policy_id = p.id
    LEFT JOIN {$wpdb->prefix}insurance_crm_representatives r ON t.representative_id = r.id
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE t.customer_id = %d
    ORDER BY t.due_date ASC
", $customer_id));

// Bugünün tarihi
$today = date('Y-m-d H:i:s');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        Müşteri Detayları: <?php echo esc_html($customer->customer_name); ?>
        <span class="customer-status status-<?php echo esc_attr($customer->status); ?>">
            <?php echo $customer->status === 'aktif' ? 'Aktif' : 'Pasif'; ?>
        </span>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $customer_id); ?>" class="page-title-action">Düzenle</a>
    <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=new&customer=' . $customer_id); ?>" class="page-title-action">Yeni Görev Ekle</a>
    <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=new&customer=' . $customer_id); ?>" class="page-title-action">Yeni Poliçe Ekle</a>
    
    <hr class="wp-header-end">
    
    <div class="customer-details-container">
        <div class="customer-profile-card">
            <div class="card-header">
                <h2>Kişisel Bilgiler</h2>
                <span class="customer-category category-<?php echo esc_attr($customer->category); ?>">
                    <?php echo $customer->category === 'bireysel' ? 'Bireysel' : 'Kurumsal'; ?>
                </span>
            </div>
            
            <div class="card-content">
                <div class="customer-info-grid">
                    <div class="info-item">
                        <div class="label">Ad Soyad</div>
                        <div class="value"><?php echo esc_html($customer->customer_name); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">TC Kimlik No</div>
                        <div class="value"><?php echo esc_html($customer->tc_identity); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">E-posta</div>
                        <div class="value">
                            <a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Telefon</div>
                        <div class="value">
                            <a href="tel:<?php echo esc_attr($customer->phone); ?>"><?php echo esc_html($customer->phone); ?></a>
                        </div>
                    </div>
                    
                    <div class="info-item wide">
                        <div class="label">Adres</div>
                        <div class="value"><?php echo nl2br(esc_html($customer->address)); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Müşteri Temsilcisi</div>
                        <div class="value">
                            <?php if ($customer->rep_id): ?>
                                <a href="<?php echo admin_url('admin.php?page=insurance-crm-representatives&action=edit&id=' . $customer->rep_id); ?>">
                                    <?php echo esc_html($customer->rep_name); ?>
                                </a>
                            <?php else: ?>
                                <span class="no-value">Atanmamış</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Kayıt Tarihi</div>
                        <div class="value"><?php echo date('d.m.Y H:i', strtotime($customer->created_at)); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- POLİÇELER SEKSİYONU -->
        <div class="card policy-card">
            <div class="card-header">
                <h2>Poliçeler</h2>
                <span class="policy-count"><?php echo count($policies); ?> poliçe</span>
            </div>
            
            <div class="card-content">
                <?php if (empty($policies)): ?>
                    <div class="empty-message">Henüz poliçe bulunmuyor.</div>
                    <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=new&customer=' . $customer_id); ?>" class="button">
                        Yeni Poliçe Ekle
                    </a>
                <?php else: ?>
                <div class="customer-policies-container">
                    <table class="wp-list-table widefat fixed striped policies">
                        <thead>
                            <tr>
                                <th>Poliçe No</th>
                                <th>Tür</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Prim Tutarı</th>
                                <th>Durum</th>
                                <th>Temsilci</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($policies as $policy):
                                $is_expired = strtotime($policy->end_date) < time();
                                $is_expiring_soon = !$is_expired && (strtotime($policy->end_date) - time()) < (30 * 24 * 60 * 60); // 30 gün
                                $row_class = $is_expired ? 'expired' : ($is_expiring_soon ? 'expiring-soon' : '');
                            ?>
                                <tr class="<?php echo esc_attr($row_class); ?>">
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-policies&action=edit&id=' . $policy->id); ?>">
                                            <?php echo esc_html($policy->policy_number); ?>
                                        </a>
                                        <?php if ($is_expired): ?>
                                            <span class="expired-badge">Süresi Dolmuş</span>
                                        <?php elseif ($is_expiring_soon): ?>
                                            <span class="expiring-soon-badge">Yakında Bitiyor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($policy->policy_type); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($policy->start_date)); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($policy->end_date)); ?></td>
                                    <td>₺<?php echo number_format($policy->premium_amount, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="policy-status status-<?php echo esc_attr($policy->status); ?>">
                                            <?php echo esc_html($policy->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $policy->rep_name ? esc_html($policy->rep_name) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- GÖREVLER SEKSİYONU -->
        <div class="card tasks-card">
            <div class="card-header">
                <h2>Görevler</h2>
                <span class="task-count"><?php echo count($tasks); ?> görev</span>
            </div>
            
            <div class="card-content">
                <?php if (empty($tasks)): ?>
                    <div class="empty-message">Henüz görev bulunmuyor.</div>
                    <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=new&customer=' . $customer_id); ?>" class="button">
                        Yeni Görev Ekle
                    </a>
                <?php else: ?>
                <div class="customer-tasks-container">
                    <table class="wp-list-table widefat fixed striped tasks">
                        <thead>
                            <tr>
                                <th>Görev Açıklaması</th>
                                <th>Poliçe</th>
                                <th>Son Tarih</th>
                                <th>Öncelik</th>
                                <th>Durum</th>
                                <th>Temsilci</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task):
                                $is_overdue = strtotime($task->due_date) < strtotime($today) && $task->status != 'completed';
                            ?>
                                <tr <?php echo $is_overdue ? 'class="overdue-task"' : ''; ?>>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task->id); ?>">
                                            <?php echo esc_html($task->task_description); ?>
                                        </a>
                                        <?php if ($is_overdue): ?>
                                            <span class="overdue-badge">Gecikmiş!</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $task->policy_number ? esc_html($task->policy_number) : '—'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($task->due_date)); ?></td>
                                    <td>
                                        <span class="task-priority priority-<?php echo esc_attr($task->priority); ?>">
                                            <?php 
                                                switch ($task->priority) {
                                                    case 'low': echo 'Düşük'; break;
                                                    case 'medium': echo 'Orta'; break;
                                                    case 'high': echo 'Yüksek'; break;
                                                    default: echo $task->priority; break;
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="task-status status-<?php echo esc_attr($task->status); ?>">
                                            <?php 
                                                switch ($task->status) {
                                                    case 'pending': echo 'Beklemede'; break;
                                                    case 'in_progress': echo 'İşlemde'; break;
                                                    case 'completed': echo 'Tamamlandı'; break;
                                                    case 'cancelled': echo 'İptal Edildi'; break;
                                                    default: echo $task->status; break;
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo $task->rep_name ? esc_html($task->rep_name) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- NOTLAR VE ETKİNLİKLER SEKSİYONU -->
        <div class="card activity-card">
            <div class="card-header">
                <h2>İşlem Geçmişi</h2>
            </div>
            
            <div class="card-content">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="dashicons dashicons-admin-users"></i>
                        </div>
                        <div class="timeline-content">
                            <h3>Müşteri Kaydedildi</h3>
                            <p class="timeline-date"><?php echo date('d.m.Y H:i', strtotime($customer->created_at)); ?></p>
                        </div>
                    </div>
                    
                    <?php foreach ($policies as $policy): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="dashicons dashicons-media-document"></i>
                        </div>
                        <div class="timeline-content">
                            <h3>Poliçe Eklendi: <?php echo esc_html($policy->policy_number); ?></h3>
                            <p><?php echo esc_html($policy->policy_type); ?> poliçesi kaydedildi.</p>
                            <p class="timeline-date"><?php echo date('d.m.Y H:i', strtotime($policy->created_at)); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($tasks as $task): 
                        if ($task->status === 'completed'):
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="dashicons dashicons-yes-alt"></i>
                        </div>
                        <div class="timeline-content">
                            <h3>Görev Tamamlandı</h3>
                            <p><?php echo esc_html($task->task_description); ?></p>
                            <p class="timeline-date"><?php echo date('d.m.Y H:i', strtotime($task->updated_at)); ?></p>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Müşteri detay sayfası genel stilleri */
.customer-details-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}

/* Durum etiketleri */
.customer-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 12px;
    margin-left: 10px;
    vertical-align: middle;
}

.status-aktif {
    background-color: #e9f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.status-pasif {
    background-color: #f5f5f5;
    color: #757575;
    border: 1px solid #e0e0e0;
}

/* Kategori etiketleri */
.customer-category {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 12px;
}

.category-bireysel {
    background-color: #e0f0ff;
    color: #0a366c;
    border: 1px solid #b3d1ff;
}

.category-kurumsal {
    background-color: #daf0e8;
    color: #0a3636;
    border: 1px solid #a6e9d5;
}

/* Kart stilleri */
.card {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.card-content {
    padding: 20px;
}

/* Bilgi grid */
.customer-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-item.wide {
    grid-column: 1 / -1;
}

.info-item .label {
    font-weight: 600;
    color: #666;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.info-item .value {
    font-size: 1.1em;
}

.no-value {
    color: #999;
    font-style: italic;
}

/* Poliçe ve görev listeleri */
.customer-policies-container,
.customer-tasks-container {
    overflow-x: auto;
}

.empty-message {
    color: #666;
    font-style: italic;
    margin-bottom: 15px;
}

/* Poliçe durumları */
.policy-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 12px;
    text-align: center;
}

.expired, .expired td {
    background-color: #fff8f8 !important;
}

.expiring-soon td {
    background-color: #fffde7 !important;
}

.expired-badge {
    display: inline-block;
    background-color: #dc3232;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 5px;
    vertical-align: middle;
}

.expiring-soon-badge {
    display: inline-block;
    background-color: #ffa000;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 5px;
    vertical-align: middle;
}

/* Görev stilleri */
.task-priority, .task-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 12px;
    text-align: center;
    min-width: 80px;
}

.priority-low {
    background-color: #e9f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.priority-medium {
    background-color: #fff9e6;
    color: #f9a825;
    border: 1px solid #ffe082;
}

.priority-high {
    background-color: #ffeaed;
    color: #e53935;
    border: 1px solid #ef9a9a;
}

.status-pending {
    background-color: #fff9e6;
    color: #f9a825;
    border: 1px solid #ffe082;
}

.status-in_progress {
    background-color: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.status-completed {
    background-color: #e9f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.status-cancelled {
    background-color: #f5f5f5;
    color: #757575;
    border: 1px solid #e0e0e0;
}

.overdue-task td {
    background-color: #fff8f8 !important;
}

.overdue-badge {
    display: inline-block;
    background-color: #dc3232;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 5px;
    vertical-align: middle;
}

/* Zaman çizelgesi */
.timeline {
    position: relative;
    margin: 0 0 30px;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
    left: 25px;
    margin-left: -1px;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    display: flex;
}

.timeline-icon {
    width: 50px;
    height: 50px;
    background: #f0f0f0;
    border-radius: 50%;
    text-align: center;
    line-height: 50px;
    margin-right: 25px;
    z-index: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timeline-icon .dashicons {
    font-size: 20px;
    line-height: 50px;
    color: #555;
}

.timeline-content {
    flex: 1;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.timeline-content h3 {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 600;
}

.timeline-date {
    color: #777;
    font-size: 0.9em;
    margin-top: 10px;
    font-style: italic;
}

/* Responsive tasarım */
@media screen and (min-width: 992px) {
    .customer-details-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .customer-profile-card {
        grid-column: 1;
    }
    
    .policy-card, .tasks-card, .activity-card {
        grid-column: 1 / -1;
    }
}
</style>