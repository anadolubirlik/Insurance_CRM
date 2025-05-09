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

// Müşteri görüşme notlarını al
$notes_table = $wpdb->prefix . 'insurance_crm_customer_notes';
$customer_notes = $wpdb->get_results($wpdb->prepare("
    SELECT n.*, 
           u.display_name AS user_name
    FROM $notes_table n
    LEFT JOIN {$wpdb->users} u ON n.created_by = u.ID
    WHERE n.customer_id = %d
    ORDER BY n.created_at DESC
", $customer_id));

// Bugünün tarihi
$today = date('Y-m-d H:i:s');

// Not ekleme işlemi
if (isset($_POST['add_customer_note']) && isset($_POST['note_nonce']) && 
    wp_verify_nonce($_POST['note_nonce'], 'add_customer_note')) {
    
    $note_data = array(
        'customer_id' => $customer_id,
        'note_content' => sanitize_textarea_field($_POST['note_content']),
        'note_type' => sanitize_text_field($_POST['note_type']),
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    // Eğer olumsuz not ise sebep alanını da ekle
    if ($note_data['note_type'] === 'negative') {
        $note_data['rejection_reason'] = sanitize_text_field($_POST['rejection_reason']);
        
        // Müşteri durumunu Pasif olarak güncelle
        $wpdb->update(
            $customers_table,
            array('status' => 'pasif'),
            array('id' => $customer_id)
        );
    }
    // Olumlu not ise müşteriyi aktif yap
    else if ($note_data['note_type'] === 'positive') {
        $wpdb->update(
            $customers_table,
            array('status' => 'aktif'),
            array('id' => $customer_id)
        );
    }
    
    $insert_result = $wpdb->insert(
        $notes_table,
        $note_data
    );
    
    if ($insert_result !== false) {
        echo '<div class="notice notice-success"><p>Görüşme notu başarıyla eklendi.</p></div>';
        echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-customer-details&id=' . $customer_id . '&note_added=1') . '";</script>';
    } else {
        echo '<div class="notice notice-error"><p>Görüşme notu eklenirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
    }
}

// İşlem mesajları
if (isset($_GET['note_added']) && $_GET['note_added'] === '1') {
    echo '<div class="notice notice-success"><p>Görüşme notu başarıyla eklendi.</p></div>';
}
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
        <!-- KİŞİSEL BİLGİLER -->
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
                        <div class="label">Doğum Tarihi</div>
                        <div class="value">
                            <?php echo !empty($customer->birth_date) ? date('d.m.Y', strtotime($customer->birth_date)) : '<span class="no-value">Belirtilmemiş</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Cinsiyet</div>
                        <div class="value">
                            <?php 
                            if (!empty($customer->gender)) {
                                echo $customer->gender == 'male' ? 'Erkek' : 'Kadın';
                            } else {
                                echo '<span class="no-value">Belirtilmemiş</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if ($customer->gender == 'female'): ?>
                    <div class="info-item">
                        <div class="label">Gebelik Durumu</div>
                        <div class="value">
                            <?php 
                            if (isset($customer->is_pregnant)) {
                                if ($customer->is_pregnant == 1) {
                                    echo 'Evet';
                                    if (!empty($customer->pregnancy_week)) {
                                        echo ' (' . $customer->pregnancy_week . ' haftalık)';
                                    }
                                } else {
                                    echo 'Hayır';
                                }
                            } else {
                                echo '<span class="no-value">Belirtilmemiş</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="label">Meslek</div>
                        <div class="value">
                            <?php echo !empty($customer->occupation) ? esc_html($customer->occupation) : '<span class="no-value">Belirtilmemiş</span>'; ?>
                        </div>
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
        
        <!-- AİLE BİLGİLERİ -->
        <div class="card family-card">
            <div class="card-header">
                <h2>Aile Bilgileri</h2>
            </div>
            
            <div class="card-content">
                <div class="family-info-grid">
                    <div class="info-item">
                        <div class="label">Eş</div>
                        <div class="value">
                            <?php echo !empty($customer->spouse_name) ? esc_html($customer->spouse_name) : '<span class="no-value">Belirtilmemiş</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Eşin Doğum Tarihi</div>
                        <div class="value">
                            <?php echo !empty($customer->spouse_birth_date) ? date('d.m.Y', strtotime($customer->spouse_birth_date)) : '<span class="no-value">Belirtilmemiş</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="label">Çocuk Sayısı</div>
                        <div class="value">
                            <?php echo isset($customer->children_count) && $customer->children_count > 0 ? $customer->children_count : '<span class="no-value">0</span>'; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($customer->children_names) || !empty($customer->children_birth_dates)): ?>
                    <div class="info-item wide">
                        <div class="label">Çocuklar</div>
                        <div class="value">
                            <?php
                            $children_names = !empty($customer->children_names) ? explode(',', $customer->children_names) : [];
                            $children_birth_dates = !empty($customer->children_birth_dates) ? explode(',', $customer->children_birth_dates) : [];
                            
                            if (!empty($children_names)) {
                                echo '<ul class="children-list">';
                                for ($i = 0; $i < count($children_names); $i++) {
                                    echo '<li>' . esc_html(trim($children_names[$i]));
                                    if (isset($children_birth_dates[$i]) && !empty(trim($children_birth_dates[$i]))) {
                                        echo ' - Doğum Tarihi: ' . date('d.m.Y', strtotime(trim($children_birth_dates[$i])));
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<span class="no-value">Belirtilmemiş</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- VARLIK BİLGİLERİ -->
        <div class="card assets-card">
            <div class="card-header">
                <h2>Varlık Bilgileri</h2>
            </div>
            
            <div class="card-content">
                <div class="assets-info-grid">
                    <!-- EV BİLGİLERİ -->
                    <div class="info-group">
                        <h3>Ev Bilgileri</h3>
                        <div class="info-item">
                            <div class="label">Evi Kendisine mi Ait?</div>
                            <div class="value">
                                <?php 
                                if (isset($customer->owns_home)) {
                                    echo $customer->owns_home == 1 ? 'Evet' : 'Hayır';
                                } else {
                                    echo '<span class="no-value">Belirtilmemiş</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (isset($customer->owns_home) && $customer->owns_home == 1): ?>
                        <div class="info-item">
                            <div class="label">DASK Poliçesi</div>
                            <div class="value">
                                <?php 
                                if (isset($customer->has_dask_policy)) {
                                    if ($customer->has_dask_policy == 1) {
                                        echo 'Var';
                                        if (!empty($customer->dask_policy_expiry)) {
                                            echo ' (Vade: ' . date('d.m.Y', strtotime($customer->dask_policy_expiry)) . ')';
                                        }
                                    } else {
                                        echo 'Yok';
                                    }
                                } else {
                                    echo '<span class="no-value">Belirtilmemiş</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="label">Konut Poliçesi</div>
                            <div class="value">
                                <?php 
                                if (isset($customer->has_home_policy)) {
                                    if ($customer->has_home_policy == 1) {
                                        echo 'Var';
                                        if (!empty($customer->home_policy_expiry)) {
                                            echo ' (Vade: ' . date('d.m.Y', strtotime($customer->home_policy_expiry)) . ')';
                                        }
                                    } else {
                                        echo 'Yok';
                                    }
                                } else {
                                    echo '<span class="no-value">Belirtilmemiş</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ARAÇ BİLGİLERİ -->
                    <div class="info-group">
                        <h3>Araç Bilgileri</h3>
                        <div class="info-item">
                            <div class="label">Aracı Var mı?</div>
                            <div class="value">
                                <?php 
                                if (isset($customer->has_vehicle)) {
                                    echo $customer->has_vehicle == 1 ? 'Evet' : 'Hayır';
                                } else {
                                    echo '<span class="no-value">Belirtilmemiş</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (isset($customer->has_vehicle) && $customer->has_vehicle == 1): ?>
                        <div class="info-item">
                            <div class="label">Araç Plakası</div>
                            <div class="value">
                                <?php echo !empty($customer->vehicle_plate) ? esc_html($customer->vehicle_plate) : '<span class="no-value">Belirtilmemiş</span>'; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- EVCİL HAYVAN BİLGİLERİ -->
                    <div class="info-group">
                        <h3>Evcil Hayvan Bilgileri</h3>
                        <div class="info-item">
                            <div class="label">Evcil Hayvanı Var mı?</div>
                            <div class="value">
                                <?php 
                                if (isset($customer->has_pet)) {
                                    echo $customer->has_pet == 1 ? 'Evet' : 'Hayır';
                                } else {
                                    echo '<span class="no-value">Belirtilmemiş</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (isset($customer->has_pet) && $customer->has_pet == 1): ?>
                        <div class="info-item">
                            <div class="label">Evcil Hayvan Bilgisi</div>
                            <div class="value">
                                <?php
                                $pet_info = [];
                                if (!empty($customer->pet_name)) {
                                    $pet_info[] = 'Adı: ' . esc_html($customer->pet_name);
                                }
                                if (!empty($customer->pet_type)) {
                                    $pet_info[] = 'Cinsi: ' . esc_html($customer->pet_type);
                                }
                                if (!empty($customer->pet_age)) {
                                    $pet_info[] = 'Yaşı: ' . esc_html($customer->pet_age);
                                }
                                
                                echo !empty($pet_info) ? implode(', ', $pet_info) : '<span class="no-value">Detay belirtilmemiş</span>';
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
        
        <!-- GÖRÜŞME NOTLARI -->
        <div class="card notes-card">
            <div class="card-header">
                <h2>Görüşme Notları</h2>
                <span class="notes-count"><?php echo count($customer_notes); ?> not</span>
            </div>
            
            <div class="card-content">
                <!-- Not Ekleme Formu -->
                <div class="add-note-container">
                    <h3>Yeni Görüşme Notu Ekle</h3>
                    <form method="post" action="" class="add-note-form">
                        <?php wp_nonce_field('add_customer_note', 'note_nonce'); ?>
                        
                        <div class="form-row">
                            <label for="note_content">Görüşme Notu</label>
                            <textarea name="note_content" id="note_content" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <label for="note_type">Görüşme Sonucu</label>
                            <select name="note_type" id="note_type" required>
                                <option value="">Seçiniz</option>
                                <option value="positive">Olumlu</option>
                                <option value="neutral">Durumu Belirsiz</option>
                                <option value="negative">Olumsuz</option>
                            </select>
                        </div>
                        
                        <div id="rejection_reason_container" style="display:none;">
                            <div class="form-row">
                                <label for="rejection_reason">Olumsuz Olma Nedeni</label>
                                <select name="rejection_reason" id="rejection_reason">
                                    <option value="">Seçiniz</option>
                                    <option value="price">Fiyat</option>
                                    <option value="wrong_application">Yanlış Başvuru</option>
                                    <option value="existing_policy">Mevcut Poliçesi Var</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" name="add_customer_note" class="button button-primary">Not Ekle</button>
                        </div>
                    </form>
                </div>
                
                <hr>
                
                <!-- Notlar Listesi -->
                <div class="notes-list">
                    <?php if (empty($customer_notes)): ?>
                        <div class="empty-message">Henüz görüşme notu bulunmuyor.</div>
                    <?php else: ?>
                        <?php foreach ($customer_notes as $note): ?>
                            <div class="note-item note-type-<?php echo esc_attr($note->note_type); ?>">
                                <div class="note-header">
                                    <div class="note-meta">
                                        <span class="note-author"><?php echo esc_html($note->user_name); ?></span>
                                        <span class="note-date"><?php echo date('d.m.Y H:i', strtotime($note->created_at)); ?></span>
                                    </div>
                                    <span class="note-type-badge <?php echo esc_attr($note->note_type); ?>">
                                        <?php 
                                            switch ($note->note_type) {
                                                case 'positive': echo 'Olumlu'; break;
                                                case 'neutral': echo 'Belirsiz'; break;
                                                case 'negative': echo 'Olumsuz'; break;
                                                default: echo ucfirst($note->note_type); break;
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="note-content">
                                    <?php echo nl2br(esc_html($note->note_content)); ?>
                                </div>
                                <?php if ($note->note_type === 'negative' && !empty($note->rejection_reason)): ?>
                                    <div class="note-reason">
                                        <strong>Sebep:</strong> 
                                        <?php 
                                            switch ($note->rejection_reason) {
                                                case 'price': echo 'Fiyat'; break;
                                                case 'wrong_application': echo 'Yanlış Başvuru'; break;
                                                case 'existing_policy': echo 'Mevcut Poliçesi Var'; break;
                                                case 'other': echo 'Diğer'; break;
                                                default: echo ucfirst($note->rejection_reason); break;
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    margin-bottom: 20px;
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
.customer-info-grid,
.family-info-grid,
.assets-info-grid {
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

/* Bilgi grupları */
.info-group {
    margin-bottom: 20px;
}

.info-group h3 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 600;
    padding-bottom: 5px;
    border-bottom: 1px solid #f0f0f0;
    color: #555;
}

/* Çocuk listeleri */
.children-list {
    margin: 0;
    padding-left: 20px;
}

.children-list li {
    margin-bottom: 5px;
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

/* Not ekleme formu */
.add-note-container {
    margin-bottom: 20px;
}

.add-note-form .form-row {
    margin-bottom: 15px;
}

.add-note-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.add-note-form textarea,
.add-note-form select {
    width: 100%;
}

.form-buttons {
    margin-top: 10px;
}

/* Not listesi */
.notes-list {
    margin-top: 20px;
}

.note-item {
    background: #f9f9f9;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #ddd;
}

.note-item.note-type-positive {
    border-left-color: #4CAF50;
}

.note-item.note-type-neutral {
    border-left-color: #2196F3;
}

.note-item.note-type-negative {
    border-left-color: #F44336;
}

.note-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.note-meta {
    font-size: 12px;
    color: #666;
}

.note-author {
    font-weight: bold;
    margin-right: 10px;
}

.note-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}

.note-type-badge.positive {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.note-type-badge.neutral {
    background-color: #e3f2fd;
    color: #1565c0;
}

.note-type-badge.negative {
    background-color: #ffebee;
    color: #c62828;
}

.note-content {
    margin-bottom: 10px;
    line-height: 1.5;
}

.note-reason {
    font-size: 12px;
    color: #555;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #eee;
}

/* Responsive tasarım */
@media screen and (min-width: 992px) {
    .customer-details-container {
        grid-template-columns: 1fr 1fr;
    }
    
    .customer-profile-card {
        grid-column: 1;
    }
    
    .family-card {
        grid-column: 2;
    }
    
    .assets-card {
        grid-column: 1;
    }
    
    .policy-card, .tasks-card, .notes-card {
        grid-column: 1 / -1;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Not türü değiştiğinde, olumsuz olma sebebi göster/gizle
    $('#note_type').on('change', function() {
        if ($(this).val() === 'negative') {
            $('#rejection_reason_container').show();
            $('#rejection_reason').prop('required', true);
        } else {
            $('#rejection_reason_container').hide();
            $('#rejection_reason').prop('required', false);
        }
    });
});
</script>