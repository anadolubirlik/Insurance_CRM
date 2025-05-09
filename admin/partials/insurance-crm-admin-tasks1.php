<?php
/**
 * Görevler Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @version    1.0.5
 */

if (!defined('WPINC')) {
    die;
}

// Yetki kontrolü
if (!current_user_can('manage_options')) {
    $is_admin = false;
} else {
    $is_admin = true;
}

// Aksiyon kontrolü
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = ($action === 'edit' && $task_id > 0);
$adding = ($action === 'new');

// Filtreleme parametreleri
$customer_filter = isset($_GET['customer']) ? intval($_GET['customer']) : 0;
$priority_filter = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Form gönderildiğinde işlem yap
if (isset($_POST['submit_task']) && isset($_POST['task_nonce']) && 
    wp_verify_nonce($_POST['task_nonce'], 'add_edit_task')) {
    
    $task_data = array(
        'customer_id' => intval($_POST['customer_id']),
        'policy_id' => !empty($_POST['policy_id']) ? intval($_POST['policy_id']) : null,
        'task_description' => sanitize_textarea_field($_POST['task_description']),
        'due_date' => sanitize_text_field($_POST['due_date']),
        'priority' => sanitize_text_field($_POST['priority']),
        'status' => sanitize_text_field($_POST['status']),
        'representative_id' => !empty($_POST['representative_id']) ? intval($_POST['representative_id']) : null
    );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_tasks';
    
    if ($editing && isset($_POST['task_id'])) {
        // Mevcut görevi güncelle
        $update_result = $wpdb->update(
            $table_name,
            array(
                'customer_id' => $task_data['customer_id'],
                'policy_id' => $task_data['policy_id'],
                'task_description' => $task_data['task_description'],
                'due_date' => $task_data['due_date'],
                'priority' => $task_data['priority'],
                'status' => $task_data['status'],
                'representative_id' => $task_data['representative_id'],
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($_POST['task_id']))
        );
        
        if ($update_result !== false) {
            echo '<div class="notice notice-success"><p>Görev başarıyla güncellendi.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-tasks&updated=1') . '";</script>';
        } else {
            echo '<div class="notice notice-error"><p>Görev güncellenirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
        }
    } else {
        // Yeni görev ekle
        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'customer_id' => $task_data['customer_id'],
                'policy_id' => $task_data['policy_id'],
                'task_description' => $task_data['task_description'],
                'due_date' => $task_data['due_date'],
                'priority' => $task_data['priority'],
                'status' => $task_data['status'],
                'representative_id' => $task_data['representative_id'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        if ($insert_result !== false) {
            echo '<div class="notice notice-success"><p>Görev başarıyla eklendi.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-tasks&added=1') . '";</script>';
        } else {
            echo '<div class="notice notice-error"><p>Görev eklenirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
        }
    }
}

// Silme işlemi
if ($action === 'delete' && $task_id > 0) {
    if (!$is_admin) {
        echo '<div class="notice notice-error"><p>Görev silme yetkisine sahip değilsiniz. Sadece yöneticiler görev silebilir.</p></div>';
    } else {
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_task_' . $task_id)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'insurance_crm_tasks';
            
            $delete_result = $wpdb->delete($table_name, array('id' => $task_id), array('%d'));
            
            if ($delete_result !== false) {
                echo '<div class="notice notice-success"><p>Görev başarıyla silindi.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Görev silinirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Geçersiz silme işlemi.</p></div>';
        }
    }
}

// İşlem mesajları
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    echo '<div class="notice notice-success"><p>Görev başarıyla güncellendi.</p></div>';
}

if (isset($_GET['added']) && $_GET['added'] === '1') {
    echo '<div class="notice notice-success"><p>Yeni görev başarıyla eklendi.</p></div>';
}

// Düzenlenecek görevin verilerini al
$edit_task = null;
if ($editing) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_tasks';
    
    $edit_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $task_id));
    
    if (!$edit_task) {
        echo '<div class="notice notice-error"><p>Düzenlenmek istenen görev bulunamadı.</p></div>';
        $editing = false;
    }
}

// Müşterileri al
global $wpdb;
$customers_table = $wpdb->prefix . 'insurance_crm_customers';
$customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as customer_name FROM $customers_table WHERE status = 'aktif' ORDER BY first_name, last_name");

// Poliçeleri al
$policies_table = $wpdb->prefix . 'insurance_crm_policies';
$policies = $wpdb->get_results("SELECT id, policy_number, customer_id FROM $policies_table WHERE status = 'aktif' ORDER BY id DESC");

// Temsilcileri al
$reps_table = $wpdb->prefix . 'insurance_crm_representatives';
$representatives = $wpdb->get_results("
    SELECT r.id, u.display_name 
    FROM $reps_table r
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE r.status = 'active'
    ORDER BY u.display_name
");

// Görevleri listele
$tasks_table = $wpdb->prefix . 'insurance_crm_tasks';
$query = "
    SELECT t.*, 
           c.first_name, c.last_name,
           p.policy_number,
           u.display_name AS rep_name
    FROM $tasks_table t
    LEFT JOIN $customers_table c ON t.customer_id = c.id
    LEFT JOIN $policies_table p ON t.policy_id = p.id
    LEFT JOIN $reps_table r ON t.representative_id = r.id
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE 1=1
";

// Filtreleme
$where_conditions = array();
$where_values = array();

if ($customer_filter > 0) {
    $where_conditions[] = "t.customer_id = %d";
    $where_values[] = $customer_filter;
}

if (!empty($priority_filter)) {
    $where_conditions[] = "t.priority = %s";
    $where_values[] = $priority_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = %s";
    $where_values[] = $status_filter;
}

if (!empty($where_conditions)) {
    $query .= " AND " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY t.due_date ASC";

if (!empty($where_values)) {
    $prepared_query = $wpdb->prepare($query, $where_values);
    $tasks = $wpdb->get_results($prepared_query);
} else {
    $tasks = $wpdb->get_results($query);
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Görevler</h1>
    <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=new'); ?>" class="page-title-action">Yeni Ekle</a>
    
    <hr class="wp-header-end">

    <?php if (!$editing && !$adding): ?>
    <!-- GÖREVLER LİSTESİ -->
    <div class="insurance-crm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="insurance-crm-tasks">
            
            <div class="filter-row">
                <div class="filter-item">
                    <label for="customer">Müşteri:</label>
                    <select name="customer" id="customer">
                        <option value="0">Tüm Müşteriler</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer->id; ?>" <?php selected($customer_filter, $customer->id); ?>>
                                <?php echo esc_html($customer->customer_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="priority">Öncelik:</label>
                    <select name="priority" id="priority">
                        <option value="">Tüm Öncelikler</option>
                        <option value="low" <?php selected($priority_filter, 'low'); ?>>Düşük</option>
                        <option value="medium" <?php selected($priority_filter, 'medium'); ?>>Orta</option>
                        <option value="high" <?php selected($priority_filter, 'high'); ?>>Yüksek</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label for="status">Durum:</label>
                    <select name="status" id="status">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Beklemede</option>
                        <option value="in_progress" <?php selected($status_filter, 'in_progress'); ?>>İşlemde</option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>>Tamamlandı</option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>İptal Edildi</option>
                    </select>
                </div>
                
                <div class="filter-item">
                    <input type="submit" class="button" value="Filtrele">
                    <?php if ($customer_filter > 0 || !empty($priority_filter) || !empty($status_filter)): ?>
                        <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks'); ?>" class="button">Filtreleri Temizle</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <div class="insurance-crm-table-container">
        <table class="wp-list-table widefat fixed striped tasks">
            <thead>
                <tr>
                    <th width="3%">ID</th>
                    <th width="20%">Görev Açıklaması</th>
                    <th width="15%">Müşteri</th>
                    <th width="12%">Poliçe</th>
                    <th width="10%">Son Tarih</th>
                    <th width="10%">Öncelik</th>
                    <th width="10%">Durum</th>
                    <th width="10%">Temsilci</th>
                    <th width="10%">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="9">Hiç görev bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo esc_html($task->id); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task->id); ?>">
                                <?php echo esc_html($task->task_description); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($task->first_name . ' ' . $task->last_name); ?></td>
                        <td><?php echo esc_html($task->policy_number ? $task->policy_number : '—'); ?></td>
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
                        <td><?php echo esc_html($task->rep_name ? $task->rep_name : '—'); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task->id)); ?>" 
                               class="button button-small">
                                Düzenle
                            </a>
                            
                            <?php if ($is_admin): ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=insurance-crm-tasks&action=delete&id=' . $task->id), 'delete_task_' . $task->id)); ?>" 
                               class="button button-small delete-task" 
                               onclick="return confirm('Bu görevi silmek istediğinizden emin misiniz?');">
                                Sil
                            </a>
                            <?php else: ?>
                            <button class="button button-small" disabled title="Sadece yöneticiler silme yetkisine sahiptir">Sil</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if ($editing || $adding): ?>
    <!-- GÖREV DÜZENLEME / EKLEME FORMU -->
    <div class="insurance-crm-form-container">
        <h2><?php echo $editing ? 'Görev Düzenle' : 'Yeni Görev Ekle'; ?></h2>
        <form method="post" action="" class="insurance-crm-form">
            <?php wp_nonce_field('add_edit_task', 'task_nonce'); ?>
            
            <?php if ($editing): ?>
                <input type="hidden" name="task_id" value="<?php echo esc_attr($task_id); ?>">
            <?php endif; ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="customer_id">Müşteri <span class="required">*</span></label></th>
                    <td>
                        <select name="customer_id" id="customer_id" class="regular-text customer-select" required>
                            <option value="">Müşteri Seçin</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo esc_attr($customer->id); ?>" 
                                        <?php echo $editing && $edit_task->customer_id == $customer->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($customer->customer_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="policy_id">Poliçe</label></th>
                    <td>
                        <select name="policy_id" id="policy_id" class="regular-text policy-select">
                            <option value="">Poliçe Seçin (Opsiyonel)</option>
                            <?php foreach ($policies as $policy): ?>
                                <option value="<?php echo esc_attr($policy->id); ?>" 
                                    data-customer="<?php echo esc_attr($policy->customer_id); ?>"
                                    <?php echo $editing && $edit_task->policy_id == $policy->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($policy->policy_number); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="task_description">Görev Açıklaması <span class="required">*</span></label></th>
                    <td>
                        <textarea name="task_description" id="task_description" class="large-text" rows="4" required><?php echo $editing ? esc_textarea($edit_task->task_description) : ''; ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="due_date">Son Tarih <span class="required">*</span></label></th>
                    <td>
                        <input type="datetime-local" name="due_date" id="due_date" class="regular-text" 
                               value="<?php echo $editing ? date('Y-m-d\TH:i', strtotime($edit_task->due_date)) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="priority">Öncelik <span class="required">*</span></label></th>
                    <td>
                        <select name="priority" id="priority" class="regular-text" required>
                            <option value="low" <?php echo $editing && $edit_task->priority == 'low' ? 'selected' : ''; ?>>Düşük</option>
                            <option value="medium" <?php echo $editing && $edit_task->priority == 'medium' ? 'selected' : ''; ?>>Orta</option>
                            <option value="high" <?php echo $editing && $edit_task->priority == 'high' ? 'selected' : ''; ?>>Yüksek</option>
                        </select>
                        <div class="priority-preview">
                            <span class="task-priority"></span>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="status">Durum <span class="required">*</span></label></th>
                    <td>
                        <select name="status" id="status" class="regular-text" required>
                            <option value="pending" <?php echo $editing && $edit_task->status == 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                            <option value="in_progress" <?php echo $editing && $edit_task->status == 'in_progress' ? 'selected' : ''; ?>>İşlemde</option>
                            <option value="completed" <?php echo $editing && $edit_task->status == 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                            <option value="cancelled" <?php echo $editing && $edit_task->status == 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                        </select>
                        <div class="status-preview">
                            <span class="task-status"></span>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="representative_id">Sorumlu Temsilci</label></th>
                    <td>
                        <select name="representative_id" id="representative_id" class="regular-text">
                            <option value="">Sorumlu Temsilci Seçin (Opsiyonel)</option>
                            <?php foreach ($representatives as $rep): ?>
                                <option value="<?php echo esc_attr($rep->id); ?>" 
                                        <?php echo $editing && $edit_task->representative_id == $rep->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($rep->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit_task" class="button button-primary" 
                       value="<?php echo $editing ? 'Görevi Güncelle' : 'Görev Ekle'; ?>">
                <a href="<?php echo admin_url('admin.php?page=insurance-crm-tasks'); ?>" class="button">İptal</a>
            </p>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
/* Tablo konteyner stili */
.insurance-crm-table-container {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 20px;
    margin-bottom: 20px;
    overflow-x: auto;
}

/* Form konteyner stili */
.insurance-crm-form-container {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 20px;
}

/* Filtre stili */
.insurance-crm-filters {
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 15px;
}

.filter-item {
    min-width: 150px;
}

.filter-item label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

/* Öncelik etiketleri */
.task-priority {
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

/* Durum etiketleri */
.task-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 500;
    font-size: 12px;
    text-align: center;
    min-width: 80px;
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

/* Önizleme alanları */
.priority-preview, .status-preview {
    display: inline-block;
    margin-left: 10px;
}

/* Gerekli alan işareti */
.required {
    color: #dc3232;
}

/* Responsive düzenlemeler */
@media screen and (max-width: 782px) {
    .filter-row {
        flex-direction: column;
    }
    .filter-item {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Müşteri seçildiğinde, ilgili poliçeleri filtrele
    $('#customer_id').change(function() {
        var customer_id = $(this).val();
        
        // Tüm poliçe seçeneklerini gizle
        $('#policy_id option').hide();
        $('#policy_id').val('');
        
        // Sadece "Poliçe Seçin" seçeneğini ve seçilen müşteriye ait poliçeleri göster
        $('#policy_id option:first').show();
        
        if (customer_id) {
            $('#policy_id option[data-customer="' + customer_id + '"]').show();
        }
    });
    
    // Sayfa yüklendiğinde, eğer düzenleme modundaysa ve bir müşteri seçiliyse, poliçeleri filtrele
    if ($('#customer_id').val()) {
        $('#customer_id').trigger('change');
    }
    
    // Öncelik değiştiğinde önizleme güncelle
    function updatePriorityPreview() {
        var priority = $('#priority').val();
        var priorityText = $('#priority option:selected').text();
        $('.priority-preview .task-priority')
            .removeClass('priority-low priority-medium priority-high')
            .addClass('priority-' + priority)
            .text(priorityText);
    }
    
    // Durum değiştiğinde önizleme güncelle
    function updateStatusPreview() {
        var status = $('#status').val();
        var statusText = $('#status option:selected').text();
        $('.status-preview .task-status')
            .removeClass('status-pending status-in_progress status-completed status-cancelled')
            .addClass('status-' + status)
            .text(statusText);
    }
    
    // Sayfa yüklendiğinde önizlemeleri başlat
    updatePriorityPreview();
    updateStatusPreview();
    
    // Değişiklikler olduğunda önizlemeleri güncelle
    $('#priority').change(updatePriorityPreview);
    $('#status').change(updateStatusPreview);
    
    // Görev formu doğrulama
    $('.insurance-crm-form').on('submit', function(e) {
        var customer_id = $('#customer_id').val();
        var task_description = $('#task_description').val();
        var due_date = $('#due_date').val();
        
        if (!customer_id || !task_description || !due_date) {
            e.preventDefault();
            alert('Lütfen zorunlu alanları doldurun!');
            return false;
        }
        
        return true;
    });
    
    // Son tarihin geçmiş olup olmadığını kontrol et
    function checkDueDate() {
        var dueDateInput = $('#due_date').val();
        if (dueDateInput) {
            var dueDate = new Date(dueDateInput);
            var now = new Date();
            
            if (dueDate < now) {
                $('#due_date').css('background-color', '#ffeaed');
            } else {
                $('#due_date').css('background-color', '');
            }
        }
    }
    
    $('#due_date').on('change', checkDueDate);
    checkDueDate();
});
</script>