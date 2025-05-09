<?php
/**
 * Müşteri detay sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.4
 */

if (!defined('WPINC')) {
    die;
}

// Müşteri ID'sini al
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    wp_die(__('Geçersiz müşteri ID\'si'));
}

global $wpdb;

// Müşteri verilerini al
$table_name = $wpdb->prefix . 'insurance_crm_customers';
$customer = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, r.title as representative_title, u.display_name as representative_name
     FROM $table_name c
     LEFT JOIN {$wpdb->prefix}insurance_crm_representatives r ON c.representative_id = r.id
     LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
     WHERE c.id = %d",
    $customer_id
));

if (!$customer) {
    wp_die(__('Müşteri bulunamadı'));
}

// Müşteri notlarını al
$notes_table = $wpdb->prefix . 'insurance_crm_customer_notes';
$notes = $wpdb->get_results($wpdb->prepare(
    "SELECT n.*, u.display_name as created_by_name 
     FROM $notes_table n 
     LEFT JOIN {$wpdb->users} u ON n.created_by = u.ID
     WHERE n.customer_id = %d 
     ORDER BY n.created_at DESC",
    $customer_id
));

// Müşteri poliçelerini al
$policies_table = $wpdb->prefix . 'insurance_crm_policies';
$policies = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $policies_table 
     WHERE customer_id = %d 
     ORDER BY end_date ASC",
    $customer_id
));

// Form gönderildiyse görüşme notu ekle
if (isset($_POST['submit_note']) && isset($_POST['note_nonce']) && 
    wp_verify_nonce($_POST['note_nonce'], 'add_customer_note')) {
    
    $note_data = array(
        'customer_id' => $customer_id,
        'note_content' => sanitize_textarea_field($_POST['note_content']),
        'note_type' => sanitize_text_field($_POST['note_type']),
        'rejection_reason' => ($_POST['note_type'] == 'olumsuz' && !empty($_POST['rejection_reason'])) ? 
                          sanitize_text_field($_POST['rejection_reason']) : NULL,
        'created_by' => get_current_user_id(),
        'created_at' => current_time('mysql')
    );
    
    $wpdb->insert($notes_table, $note_data);
    
    // Eğer görüşme notu olumsuzsa, müşteri durumunu pasif yap
    if ($_POST['note_type'] == 'olumsuz') {
        $wpdb->update(
            $table_name,
            array('status' => 'pasif'),
            array('id' => $customer_id)
        );
    } elseif ($_POST['note_type'] == 'olumlu') {
        $wpdb->update(
            $table_name,
            array('status' => 'aktif'),
            array('id' => $customer_id)
        );
    } elseif ($_POST['note_type'] == 'belirsiz') {
        $wpdb->update(
            $table_name,
            array('status' => 'belirsiz'),
            array('id' => $customer_id)
        );
    }
    
    // Sayfayı yenile
    echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-customers&action=view&id=' . $customer_id) . '";</script>';
    exit;
}

// Evcil hayvan türleri
$pet_types = array(
    'kedi' => 'Kedi',
    'kopek' => 'Köpek',
    'kus' => 'Kuş',
    'balik' => 'Balık',
    'kemirgen' => 'Kemirgen',
    'diger' => 'Diğer'
);

// Müşteri durumu CSS sınıfı
$status_class = '';
switch ($customer->status) {
    case 'aktif':
        $status_class = 'status-active';
        break;
    case 'pasif':
        $status_class = 'status-inactive';
        break;
    case 'belirsiz':
        $status_class = 'status-uncertain';
        break;
}

?>

<div class="wrap">
    <h1>Müşteri Detayları</h1>
    
    <div class="nav-tab-wrapper">
        <a href="?page=insurance-crm-customers&action=edit&id=<?php echo $customer_id; ?>" class="nav-tab">Müşteri Bilgileri</a>
        <a href="?page=insurance-crm-customers&action=view&id=<?php echo $customer_id; ?>" class="nav-tab nav-tab-active">Müşteri Detayları</a>
    </div>

    <div class="notice customer-status <?php echo $status_class; ?>">
        <p>
            <strong>Müşteri Durumu: <?php echo ucfirst($customer->status); ?></strong>
            <?php if (!empty($customer->representative_name)): ?>
                | Müşteri Temsilcisi: <?php echo $customer->representative_name; ?> (<?php echo $customer->representative_title; ?>)
            <?php endif; ?>
        </p>
    </div>
    
    <div class="metabox-holder">
        <div class="postbox">
            <h2 class="hndle"><span>Kişisel Bilgiler</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Ad Soyad</th>
                        <td><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></td>
                    </tr>
                    <tr>
                        <th>TC Kimlik No</th>
                        <td><?php echo esc_html($customer->tc_identity); ?></td>
                    </tr>
                    <tr>
                        <th>Doğum Tarihi</th>
                        <td><?php echo $customer->birth_date ? date('d.m.Y', strtotime($customer->birth_date)) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Cinsiyet</th>
                        <td><?php echo $customer->gender ? ucfirst($customer->gender) : '-'; ?></td>
                    </tr>
                    <?php if ($customer->gender == 'kadın' && $customer->is_pregnant): ?>
                    <tr>
                        <th>Gebelik Durumu</th>
                        <td>Evet (<?php echo intval($customer->pregnancy_week); ?> haftalık)</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Meslek</th>
                        <td><?php echo $customer->occupation ? esc_html($customer->occupation) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>E-posta</th>
                        <td><?php echo esc_html($customer->email); ?></td>
                    </tr>
                    <tr>
                        <th>Telefon</th>
                        <td><?php echo esc_html($customer->phone); ?></td>
                    </tr>
                    <tr>
                        <th>Adres</th>
                        <td><?php echo nl2br(esc_html($customer->address)); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Aile Bilgileri</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Eşi</th>
                        <td><?php echo $customer->spouse_name ? esc_html($customer->spouse_name) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Eşinin Doğum Tarihi</th>
                        <td><?php echo $customer->spouse_birth_date ? date('d.m.Y', strtotime($customer->spouse_birth_date)) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Çocuk Sayısı</th>
                        <td><?php echo $customer->children_count ? intval($customer->children_count) : '0'; ?></td>
                    </tr>
                    <?php if ($customer->children_count > 0 && $customer->children_names): ?>
                    <tr>
                        <th>Çocukların Bilgileri</th>
                        <td>
                            <?php 
                            $children_names = explode("\n", $customer->children_names);
                            $children_birth_dates = $customer->children_birth_dates ? explode("\n", $customer->children_birth_dates) : array();
                            
                            echo '<ul>';
                            foreach ($children_names as $index => $name) {
                                $name = trim($name);
                                if (!empty($name)) {
                                    echo '<li><strong>' . esc_html($name) . '</strong>';
                                    if (isset($children_birth_dates[$index]) && !empty(trim($children_birth_dates[$index]))) {
                                        echo ' - Doğum Tarihi: ' . esc_html(trim($children_birth_dates[$index]));
                                    }
                                    echo '</li>';
                                }
                            }
                            echo '</ul>';
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Evcil Hayvan Bilgileri</span></h2>
            <div class="inside">
                <?php if ($customer->has_pet): ?>
                <table class="form-table">
                    <tr>
                        <th>Evcil Hayvan Adı</th>
                        <td><?php echo $customer->pet_name ? esc_html($customer->pet_name) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Türü</th>
                        <td><?php echo isset($pet_types[$customer->pet_type]) ? $pet_types[$customer->pet_type] : $customer->pet_type; ?></td>
                    </tr>
                    <tr>
                        <th>Yaşı</th>
                        <td><?php echo $customer->pet_age ? esc_html($customer->pet_age) : '-'; ?></td>
                    </tr>
                </table>
                <?php else: ?>
                <p>Evcil hayvanı bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Araç ve Konut Bilgileri</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Araç Durumu</th>
                        <td>
                            <?php if ($customer->has_vehicle): ?>
                                <span class="dashicons dashicons-yes"></span> Var
                                <?php if ($customer->vehicle_plate): ?>
                                    (Plaka: <strong><?php echo esc_html($customer->vehicle_plate); ?></strong>)
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no"></span> Yok
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Ev Sahipliği</th>
                        <td>
                            <?php if ($customer->owns_home): ?>
                                <span class="dashicons dashicons-yes"></span> Evi kendisine ait
                            <?php else: ?>
                                <span class="dashicons dashicons-no"></span> Kiracı
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($customer->owns_home): ?>
                    <tr>
                        <th>DASK Poliçesi</th>
                        <td>
                            <?php if ($customer->has_dask_policy): ?>
                                <span class="dashicons dashicons-yes"></span> Var 
                                <?php if ($customer->dask_policy_expiry): ?>
                                    (Vade: <strong><?php echo date('d.m.Y', strtotime($customer->dask_policy_expiry)); ?></strong>)
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no"></span> Yok
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Konut Poliçesi</th>
                        <td>
                            <?php if ($customer->has_home_policy): ?>
                                <span class="dashicons dashicons-yes"></span> Var 
                                <?php if ($customer->home_policy_expiry): ?>
                                    (Vade: <strong><?php echo date('d.m.Y', strtotime($customer->home_policy_expiry)); ?></strong>)
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no"></span> Yok
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Aktif Poliçeler</span></h2>
            <div class="inside">
                <?php if (!empty($policies)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Poliçe No</th>
                            <th>Poliçe Türü</th>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Prim</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policies as $policy): ?>
                        <tr>
                            <td>
                                <a href="?page=insurance-crm-policies&action=edit&id=<?php echo $policy->id; ?>">
                                    <?php echo esc_html($policy->policy_number); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                $policy_types = array(
                                    'trafik' => 'Trafik Sigortası',
                                    'kasko' => 'Kasko',
                                    'konut' => 'Konut Sigortası',
                                    'dask' => 'DASK',
                                    'saglik' => 'Sağlık Sigortası',
                                    'hayat' => 'Hayat Sigortası',
                                    'isyeri' => 'İşyeri Sigortası',
                                    'nakliyat' => 'Nakliyat Sigortası',
                                    'sorumluluk' => 'Sorumluluk Sigortası',
                                    'diger' => 'Diğer'
                                );
                                echo isset($policy_types[$policy->policy_type]) ? $policy_types[$policy->policy_type] : ucfirst($policy->policy_type);
                                ?>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($policy->start_date)); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($policy->end_date)); ?></td>
                            <td><?php echo number_format($policy->premium_amount, 2, ',', '.') . ' ₺'; ?></td>
                            <td>
                                <?php
                                $now = time();
                                $end_date = strtotime($policy->end_date);
                                $days_left = ceil(($end_date - $now) / (60 * 60 * 24));
                                
                                if ($days_left < 0) {
                                    echo '<span class="policy-expired">Süresi Dolmuş</span>';
                                } elseif ($days_left <= 30) {
                                    echo '<span class="policy-expiring">Yakında Bitecek (' . $days_left . ' gün)</span>';
                                } else {
                                    echo '<span class="policy-active">Aktif (' . $days_left . ' gün)</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Bu müşteriye ait poliçe bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Yeni Görüşme Notu Ekle</span></h2>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('add_customer_note', 'note_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="note_content">Görüşme Notu</label></th>
                            <td>
                                <textarea id="note_content" name="note_content" rows="4" cols="50" placeholder="Görüşme detaylarını buraya giriniz..." required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="note_type">Görüşme Sonucu</label></th>
                            <td>
                                <select id="note_type" name="note_type" onchange="toggleRejectionReason()">
                                    <option value="olumlu">Olumlu</option>
                                    <option value="olumsuz">Olumsuz</option>
                                    <option value="belirsiz">Durumu Belirsiz</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="rejection_reason_row" style="display: none;">
                            <th><label for="rejection_reason">Olumsuz Sonuç Sebebi</label></th>
                            <td>
                                <select id="rejection_reason" name="rejection_reason">
                                    <option value="fiyat">Fiyat</option>
                                    <option value="yanlis_basvuru">Yanlış Başvuru</option>
                                    <option value="mevcut_police">Mevcut Poliçesi Var</option>
                                    <option value="diger">Diğer</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_note" class="button button-primary" value="Not Ekle">
                    </p>
                </form>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle"><span>Müşteri Görüşme Geçmişi</span></h2>
            <div class="inside">
                <?php if (!empty($notes)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="15%">Tarih</th>
                            <th width="55%">Not</th>
                            <th width="15%">Sonuç</th>
                            <th width="15%">Ekleyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($note->created_at)); ?></td>
                            <td><?php echo nl2br(esc_html($note->note_content)); ?></td>
                            <td>
                                <?php 
                                $note_type_class = '';
                                $note_type_text = '';
                                
                                switch ($note->note_type) {
                                    case 'olumlu':
                                        $note_type_class = 'note-olumlu';
                                        $note_type_text = 'Olumlu';
                                        break;
                                    case 'olumsuz':
                                        $note_type_class = 'note-olumsuz';
                                        $note_type_text = 'Olumsuz';
                                        if (!empty($note->rejection_reason)) {
                                            $rejection_reasons = array(
                                                'fiyat' => 'Fiyat',
                                                'yanlis_basvuru' => 'Yanlış Başvuru',
                                                'mevcut_police' => 'Mevcut Poliçesi Var',
                                                'diger' => 'Diğer'
                                            );
                                            $reason = isset($rejection_reasons[$note->rejection_reason]) ? 
                                                    $rejection_reasons[$note->rejection_reason] : $note->rejection_reason;
                                            $note_type_text .= ' (' . $reason . ')';
                                        }
                                        break;
                                    case 'belirsiz':
                                        $note_type_class = 'note-belirsiz';
                                        $note_type_text = 'Durumu Belirsiz';
                                        break;
                                    default:
                                        $note_type_text = ucfirst($note->note_type);
                                }
                                ?>
                                <span class="<?php echo $note_type_class; ?>"><?php echo $note_type_text; ?></span>
                            </td>
                            <td><?php echo esc_html($note->created_by_name); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Henüz görüşme notu bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <p class="submit">
        <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $customer_id); ?>" class="button button-primary">Müşteriyi Düzenle</a>
        <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers'); ?>" class="button">Müşteri Listesine Dön</a>
    </p>
</div>

<style>
.customer-status {
    margin: 15px 0;
    padding: 10px 15px;
    border-left-width: 5px;
    font-weight: bold;
}
.status-active {
    border-left-color: #46b450;
    background-color: #ecf7ed;
}
.status-inactive {
    border-left-color: #dc3232;
    background-color: #f7ecec;
}
.status-uncertain {
    border-left-color: #ffb900;
    background-color: #fff8e5;
}
.note-olumlu {
    color: #46b450;
    font-weight: bold;
}
.note-olumsuz {
    color: #dc3232;
    font-weight: bold;
}
.note-belirsiz {
    color: #ffb900;
    font-weight: bold;
}
.policy-active {
    color: #46b450;
    font-weight: bold;
}
.policy-expiring {
    color: #ffb900;
    font-weight: bold;
}
.policy-expired {
    color: #dc3232;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Görüşme sonucuna göre red nedenini göster/gizle
    toggleRejectionReason();
});

// Görüşme sonucuna göre red nedenini göster/gizle
function toggleRejectionReason() {
    var noteType = document.getElementById('note_type').value;
    document.getElementById('rejection_reason_row').style.display = (noteType === 'olumsuz') ? '' : 'none';
}
</script>