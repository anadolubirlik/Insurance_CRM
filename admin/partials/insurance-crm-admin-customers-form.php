<?php
/**
 * Müşteri ekleme/düzenleme formu
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @version    1.0.5
 */

if (!defined('WPINC')) {
    die;
}

$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && intval($_GET['id']) > 0;
$customer_id = $editing ? intval($_GET['id']) : 0;

// Form gönderildiğinde işlem yap
if (isset($_POST['submit_customer']) && isset($_POST['customer_nonce']) && wp_verify_nonce($_POST['customer_nonce'], 'add_edit_customer')) {
    
    // Temel müşteri bilgileri
    $customer_data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'address' => sanitize_textarea_field($_POST['address']),
        'tc_identity' => sanitize_text_field($_POST['tc_identity']),
        'category' => sanitize_text_field($_POST['category']),
        'status' => sanitize_text_field($_POST['status']),
        'representative_id' => !empty($_POST['representative_id']) ? intval($_POST['representative_id']) : null
    );
    
    // Yeni eklenen alanlar
    $customer_data['birth_date'] = !empty($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : null;
    $customer_data['gender'] = !empty($_POST['gender']) ? sanitize_text_field($_POST['gender']) : null;
    
    // Cinsiyet kadın ise ve gebe ise ilgili alanları ekle
    if ($customer_data['gender'] === 'female') {
        $customer_data['is_pregnant'] = isset($_POST['is_pregnant']) ? 1 : 0;
        if ($customer_data['is_pregnant'] == 1) {
            $customer_data['pregnancy_week'] = !empty($_POST['pregnancy_week']) ? intval($_POST['pregnancy_week']) : null;
        }
    }
    
    $customer_data['occupation'] = !empty($_POST['occupation']) ? sanitize_text_field($_POST['occupation']) : null;
    
    // Eş bilgileri
    $customer_data['spouse_name'] = !empty($_POST['spouse_name']) ? sanitize_text_field($_POST['spouse_name']) : null;
    $customer_data['spouse_birth_date'] = !empty($_POST['spouse_birth_date']) ? sanitize_text_field($_POST['spouse_birth_date']) : null;
    
    // Çocuk bilgileri
    $customer_data['children_count'] = !empty($_POST['children_count']) ? intval($_POST['children_count']) : 0;
    
    // Çocuk isimleri ve doğum tarihleri (virgülle ayrılmış)
    $children_names = [];
    $children_birth_dates = [];
    
    for ($i = 1; $i <= $customer_data['children_count']; $i++) {
        if (!empty($_POST['child_name_' . $i])) {
            $children_names[] = sanitize_text_field($_POST['child_name_' . $i]);
            $children_birth_dates[] = !empty($_POST['child_birth_date_' . $i]) ? sanitize_text_field($_POST['child_birth_date_' . $i]) : '';
        }
    }
    
    $customer_data['children_names'] = !empty($children_names) ? implode(',', $children_names) : null;
    $customer_data['children_birth_dates'] = !empty($children_birth_dates) ? implode(',', $children_birth_dates) : null;
    
    // Araç bilgileri
    $customer_data['has_vehicle'] = isset($_POST['has_vehicle']) ? 1 : 0;
    if ($customer_data['has_vehicle'] == 1) {
        $customer_data['vehicle_plate'] = !empty($_POST['vehicle_plate']) ? sanitize_text_field($_POST['vehicle_plate']) : null;
    }
    
    // Evcil hayvan bilgileri
    $customer_data['has_pet'] = isset($_POST['has_pet']) ? 1 : 0;
    if ($customer_data['has_pet'] == 1) {
        $customer_data['pet_name'] = !empty($_POST['pet_name']) ? sanitize_text_field($_POST['pet_name']) : null;
        $customer_data['pet_type'] = !empty($_POST['pet_type']) ? sanitize_text_field($_POST['pet_type']) : null;
        $customer_data['pet_age'] = !empty($_POST['pet_age']) ? sanitize_text_field($_POST['pet_age']) : null;
    }
    
    // Ev bilgileri
    $customer_data['owns_home'] = isset($_POST['owns_home']) ? 1 : 0;
    if ($customer_data['owns_home'] == 1) {
        $customer_data['has_dask_policy'] = isset($_POST['has_dask_policy']) ? 1 : 0;
        if ($customer_data['has_dask_policy'] == 1) {
            $customer_data['dask_policy_expiry'] = !empty($_POST['dask_policy_expiry']) ? sanitize_text_field($_POST['dask_policy_expiry']) : null;
        }
        
        $customer_data['has_home_policy'] = isset($_POST['has_home_policy']) ? 1 : 0;
        if ($customer_data['has_home_policy'] == 1) {
            $customer_data['home_policy_expiry'] = !empty($_POST['home_policy_expiry']) ? sanitize_text_field($_POST['home_policy_expiry']) : null;
        }
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_customers';
    
    if ($editing && isset($_POST['customer_id'])) {
        // Mevcut müşteriyi güncelle
        $customer_id = intval($_POST['customer_id']);
        
        $customer_data['updated_at'] = current_time('mysql');
        $update_result = $wpdb->update($table_name, $customer_data, array('id' => $customer_id));
        
        if ($update_result !== false) {
            echo '<div class="notice notice-success"><p>Müşteri başarıyla güncellendi.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-customers&updated=1') . '";</script>';
        } else {
            echo '<div class="notice notice-error"><p>Müşteri güncellenirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
        }
    } else {
        // Yeni müşteri ekle
        $customer_data['created_at'] = current_time('mysql');
        $customer_data['updated_at'] = current_time('mysql');
        
        $insert_result = $wpdb->insert($table_name, $customer_data);
        
        if ($insert_result !== false) {
            $new_customer_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>Müşteri başarıyla eklendi.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=insurance-crm-customers&added=1') . '";</script>';
        } else {
            echo '<div class="notice notice-error"><p>Müşteri eklenirken bir hata oluştu: ' . $wpdb->last_error . '</p></div>';
        }
    }
}

// Düzenlenecek müşterinin verilerini al
$customer = null;
if ($editing) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_customers';
    
    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $customer_id));
    
    if (!$customer) {
        echo '<div class="notice notice-error"><p>Düzenlenmek istenen müşteri bulunamadı.</p></div>';
        return;
    }
}

// Temsilcileri al
global $wpdb;
$reps_table = $wpdb->prefix . 'insurance_crm_representatives';
$representatives = $wpdb->get_results("
    SELECT r.id, u.display_name 
    FROM $reps_table r
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE r.status = 'active'
    ORDER BY u.display_name
");
?>

<div class="wrap">
    <h1><?php echo $editing ? 'Müşteri Düzenle' : 'Yeni Müşteri Ekle'; ?></h1>
    
    <form method="post" action="" class="insurance-crm-form">
        <?php wp_nonce_field('add_edit_customer', 'customer_nonce'); ?>
        
        <?php if ($editing): ?>
            <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer_id); ?>">
        <?php endif; ?>
        
        <div class="form-tabs">
            <ul class="nav-tab-wrapper">
                <li><a href="#tab-basic" class="nav-tab nav-tab-active">Temel Bilgiler</a></li>
                <li><a href="#tab-personal" class="nav-tab">Kişisel Bilgiler</a></li>
                <li><a href="#tab-family" class="nav-tab">Aile Bilgileri</a></li>
                <li><a href="#tab-assets" class="nav-tab">Varlık Bilgileri</a></li>
            </ul>
            
            <div id="tab-basic" class="tab-content" style="display:block;">
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">Ad <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="first_name" id="first_name" class="regular-text" 
                                   value="<?php echo $editing ? esc_attr($customer->first_name) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="last_name">Soyad <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="last_name" id="last_name" class="regular-text" 
                                   value="<?php echo $editing ? esc_attr($customer->last_name) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="tc_identity">TC Kimlik No <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="tc_identity" id="tc_identity" class="regular-text" 
                                   value="<?php echo $editing ? esc_attr($customer->tc_identity) : ''; ?>"
                                   pattern="\d{11}" title="TC Kimlik No 11 haneli olmalıdır" required>
                            <p class="description">11 haneli TC Kimlik Numarasını giriniz.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="email">E-posta</label></th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" 
                                   value="<?php echo $editing ? esc_attr($customer->email) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="phone">Telefon <span class="required">*</span></label></th>
                        <td>
                            <input type="tel" name="phone" id="phone" class="regular-text" 
                                   value="<?php echo $editing ? esc_attr($customer->phone) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="address">Adres</label></th>
                        <td>
                            <textarea name="address" id="address" class="large-text" rows="3"><?php echo $editing ? esc_textarea($customer->address) : ''; ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="category">Kategori <span class="required">*</span></label></th>
                        <td>
                            <select name="category" id="category" class="regular-text" required>
                                <option value="bireysel" <?php echo $editing && $customer->category === 'bireysel' ? 'selected' : ''; ?>>Bireysel</option>
                                <option value="kurumsal" <?php echo $editing && $customer->category === 'kurumsal' ? 'selected' : ''; ?>>Kurumsal</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="status">Durum <span class="required">*</span></label></th>
                        <td>
                            <select name="status" id="status" class="regular-text" required>
                                <option value="aktif" <?php echo $editing && $customer->status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="pasif" <?php echo $editing && $customer->status === 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="representative_id">Müşteri Temsilcisi</label></th>
                        <td>
                            <select name="representative_id" id="representative_id" class="regular-text">
                                <option value="">Temsilci Seçin</option>
                                <?php foreach ($representatives as $rep): ?>
                                <option value="<?php echo esc_attr($rep->id); ?>" 
                                        <?php echo $editing && $customer->representative_id == $rep->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($rep->display_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-personal" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th><label for="birth_date">Doğum Tarihi</label></th>
                        <td>
                            <input type="date" name="birth_date" id="birth_date" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->birth_date) ? esc_attr($customer->birth_date) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="gender">Cinsiyet</label></th>
                        <td>
                            <select name="gender" id="gender" class="regular-text">
                                <option value="">Seçiniz</option>
                                <option value="male" <?php echo $editing && $customer->gender === 'male' ? 'selected' : ''; ?>>Erkek</option>
                                <option value="female" <?php echo $editing && $customer->gender === 'female' ? 'selected' : ''; ?>>Kadın</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr class="pregnancy-row" style="<?php echo (!$editing || $customer->gender !== 'female') ? 'display:none;' : ''; ?>">
                        <th>Gebelik Durumu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_pregnant" id="is_pregnant" 
                                       <?php echo $editing && !empty($customer->is_pregnant) ? 'checked' : ''; ?>>
                                Gebe
                            </label>
                            
                            <div id="pregnancy-week-container" style="<?php echo (!$editing || empty($customer->is_pregnant)) ? 'display:none;' : ''; ?> margin-top: 10px;">
                                <label for="pregnancy_week">Kaç Haftalık?</label>
                                <input type="number" name="pregnancy_week" id="pregnancy_week" min="1" max="42" class="small-text" 
                                       value="<?php echo $editing && !empty($customer->pregnancy_week) ? esc_attr($customer->pregnancy_week) : ''; ?>">
                                <span class="description">Hafta</span>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="occupation">Meslek</label></th>
                        <td>
                            <input type="text" name="occupation" id="occupation" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->occupation) ? esc_attr($customer->occupation) : ''; ?>">
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-family" class="tab-content">
                <table class="form-table">
                    <tr>
                        <th><label for="spouse_name">Eş Adı</label></th>
                        <td>
                            <input type="text" name="spouse_name" id="spouse_name" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->spouse_name) ? esc_attr($customer->spouse_name) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="spouse_birth_date">Eşin Doğum Tarihi</label></th>
                        <td>
                            <input type="date" name="spouse_birth_date" id="spouse_birth_date" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->spouse_birth_date) ? esc_attr($customer->spouse_birth_date) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="children_count">Çocuk Sayısı</label></th>
                        <td>
                            <input type="number" name="children_count" id="children_count" class="small-text" min="0" max="10" 
                                   value="<?php echo $editing && !empty($customer->children_count) ? esc_attr($customer->children_count) : '0'; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Çocuk Bilgileri</th>
                        <td>
                            <div id="children-container">
                                <?php
                                if ($editing && !empty($customer->children_names)) {
                                    $children_names = explode(',', $customer->children_names);
                                    $children_birth_dates = !empty($customer->children_birth_dates) ? explode(',', $customer->children_birth_dates) : [];
                                    
                                    for ($i = 0; $i < count($children_names); $i++) {
                                        $child_name = trim($children_names[$i]);
                                        $child_birth_date = isset($children_birth_dates[$i]) ? trim($children_birth_dates[$i]) : '';
                                        ?>
                                        <div class="child-row">
                                            <div class="child-fields">
                                                <input type="text" name="child_name_<?php echo $i+1; ?>" placeholder="Çocuğun Adı" 
                                                       value="<?php echo esc_attr($child_name); ?>" class="regular-text">
                                                
                                                <input type="date" name="child_birth_date_<?php echo $i+1; ?>" placeholder="Doğum Tarihi" 
                                                       value="<?php echo esc_attr($child_birth_date); ?>" class="regular-text">
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="tab-assets" class="tab-content">
                <h3>Ev Bilgileri</h3>
                <table class="form-table">
                    <tr>
                        <th>Ev Durumu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="owns_home" id="owns_home" 
                                       <?php echo $editing && !empty($customer->owns_home) ? 'checked' : ''; ?>>
                                Ev kendisine ait
                            </label>
                        </td>
                    </tr>
                    
                    <tr class="home-policy-row" style="<?php echo (!$editing || empty($customer->owns_home)) ? 'display:none;' : ''; ?>">
                        <th>DASK Poliçesi</th>
                        <td>
                            <label>
                                <input type="checkbox" name="has_dask_policy" id="has_dask_policy" 
                                       <?php echo $editing && !empty($customer->has_dask_policy) ? 'checked' : ''; ?>>
                                DASK Poliçesi var
                            </label>
                            
                            <div id="dask-expiry-container" style="<?php echo (!$editing || empty($customer->has_dask_policy)) ? 'display:none;' : ''; ?> margin-top: 10px;">
                                <label for="dask_policy_expiry">DASK Poliçe Vadesi</label>
                                <input type="date" name="dask_policy_expiry" id="dask_policy_expiry" class="regular-text" 
                                       value="<?php echo $editing && !empty($customer->dask_policy_expiry) ? esc_attr($customer->dask_policy_expiry) : ''; ?>">
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="home-policy-row" style="<?php echo (!$editing || empty($customer->owns_home)) ? 'display:none;' : ''; ?>">
                        <th>Konut Poliçesi</th>
                        <td>
                            <label>
                                <input type="checkbox" name="has_home_policy" id="has_home_policy" 
                                       <?php echo $editing && !empty($customer->has_home_policy) ? 'checked' : ''; ?>>
                                Konut Poliçesi var
                            </label>
                            
                            <div id="home-expiry-container" style="<?php echo (!$editing || empty($customer->has_home_policy)) ? 'display:none;' : ''; ?> margin-top: 10px;">
                                <label for="home_policy_expiry">Konut Poliçe Vadesi</label>
                                <input type="date" name="home_policy_expiry" id="home_policy_expiry" class="regular-text" 
                                       value="<?php echo $editing && !empty($customer->home_policy_expiry) ? esc_attr($customer->home_policy_expiry) : ''; ?>">
                            </div>
                        </td>
                    </tr>
                </table>
                
                <h3>Araç Bilgileri</h3>
                <table class="form-table">
                    <tr>
                        <th>Araç Durumu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="has_vehicle" id="has_vehicle" 
                                       <?php echo $editing && !empty($customer->has_vehicle) ? 'checked' : ''; ?>>
                                Aracı var
                            </label>
                        </td>
                    </tr>
                    
                    <tr class="vehicle-row" style="<?php echo (!$editing || empty($customer->has_vehicle)) ? 'display:none;' : ''; ?>">
                        <th><label for="vehicle_plate">Araç Plakası</label></th>
                        <td>
                            <input type="text" name="vehicle_plate" id="vehicle_plate" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->vehicle_plate) ? esc_attr($customer->vehicle_plate) : ''; ?>">
                        </td>
                    </tr>
                </table>
                
                <h3>Evcil Hayvan Bilgileri</h3>
                <table class="form-table">
                    <tr>
                        <th>Evcil Hayvan</th>
                        <td>
                            <label>
                                <input type="checkbox" name="has_pet" id="has_pet" 
                                       <?php echo $editing && !empty($customer->has_pet) ? 'checked' : ''; ?>>
                                Evcil hayvanı var
                            </label>
                        </td>
                    </tr>
                    
                    <tr class="pet-row" style="<?php echo (!$editing || empty($customer->has_pet)) ? 'display:none;' : ''; ?>">
                        <th><label for="pet_name">Evcil Hayvan Adı</label></th>
                        <td>
                            <input type="text" name="pet_name" id="pet_name" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->pet_name) ? esc_attr($customer->pet_name) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr class="pet-row" style="<?php echo (!$editing || empty($customer->has_pet)) ? 'display:none;' : ''; ?>">
                        <th><label for="pet_type">Evcil Hayvan Cinsi</label></th>
                        <td>
                            <select name="pet_type" id="pet_type" class="regular-text">
                                <option value="">Seçiniz</option>
                                <option value="Kedi" <?php echo $editing && $customer->pet_type === 'Kedi' ? 'selected' : ''; ?>>Kedi</option>
                                <option value="Köpek" <?php echo $editing && $customer->pet_type === 'Köpek' ? 'selected' : ''; ?>>Köpek</option>
                                <option value="Kuş" <?php echo $editing && $customer->pet_type === 'Kuş' ? 'selected' : ''; ?>>Kuş</option>
                                <option value="Balık" <?php echo $editing && $customer->pet_type === 'Balık' ? 'selected' : ''; ?>>Balık</option>
                                <option value="Diğer" <?php echo $editing && $customer->pet_type === 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr class="pet-row" style="<?php echo (!$editing || empty($customer->has_pet)) ? 'display:none;' : ''; ?>">
                        <th><label for="pet_age">Evcil Hayvan Yaşı</label></th>
                        <td>
                            <input type="text" name="pet_age" id="pet_age" class="regular-text" 
                                   value="<?php echo $editing && !empty($customer->pet_age) ? esc_attr($customer->pet_age) : ''; ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit_customer" class="button button-primary" 
                   value="<?php echo $editing ? 'Müşteriyi Güncelle' : 'Müşteri Ekle'; ?>">
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers'); ?>" class="button">İptal</a>
        </p>
    </form>
</div>

<style>
/* Form stilleri */
.insurance-crm-form {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.required {
    color: #dc3232;
}

/* Tab stilleri */
.form-tabs {
    margin-bottom: 20px;
}

.nav-tab-wrapper {
    list-style: none;
    padding: 0;
    margin: 0;
    border-bottom: 1px solid #ccc;
    display: flex;
    flex-wrap: wrap;
}

.nav-tab-wrapper li {
    margin-bottom: -1px;
}

.nav-tab {
    float: none;
    margin-right: 0;
    cursor: pointer;
}

.tab-content {
    display: none;
    padding: 20px 0;
}

/* Çocuk alanları */
.child-row {
    margin-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

.child-row:last-child {
    border-bottom: none;
}

.child-fields {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .child-fields {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .child-fields input {
        margin-bottom: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Sekme değiştirme
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Aktif sekmeyi değiştir
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // İçeriği göster/gizle
        $('.tab-content').hide();
        $(target).show();
    });
    
    // Cinsiyet değiştiğinde gebelik alanını göster/gizle
    $('#gender').change(function() {
        if ($(this).val() === 'female') {
            $('.pregnancy-row').show();
        } else {
            $('.pregnancy-row').hide();
            $('#is_pregnant').prop('checked', false);
            $('#pregnancy_week').val('');
            $('#pregnancy-week-container').hide();
        }
    });
    
    // Gebelik seçildiğinde hafta alanını göster/gizle
    $('#is_pregnant').change(function() {
        if ($(this).is(':checked')) {
            $('#pregnancy-week-container').show();
        } else {
            $('#pregnancy-week-container').hide();
            $('#pregnancy_week').val('');
        }
    });
    
    // Ev sahibi değiştiğinde poliçe alanlarını göster/gizle
    $('#owns_home').change(function() {
        if ($(this).is(':checked')) {
            $('.home-policy-row').show();
        } else {
            $('.home-policy-row').hide();
            $('#has_dask_policy, #has_home_policy').prop('checked', false);
            $('#dask_policy_expiry, #home_policy_expiry').val('');
            $('#dask-expiry-container, #home-expiry-container').hide();
        }
    });
    
    // DASK poliçesi var/yok değiştiğinde vade alanını göster/gizle
    $('#has_dask_policy').change(function() {
        if ($(this).is(':checked')) {
            $('#dask-expiry-container').show();
        } else {
            $('#dask-expiry-container').hide();
            $('#dask_policy_expiry').val('');
        }
    });
    
    // Konut poliçesi var/yok değiştiğinde vade alanını göster/gizle
    $('#has_home_policy').change(function() {
        if ($(this).is(':checked')) {
            $('#home-expiry-container').show();
        } else {
            $('#home-expiry-container').hide();
            $('#home_policy_expiry').val('');
        }
    });
    
    // Araç var/yok değiştiğinde plaka alanını göster/gizle
    $('#has_vehicle').change(function() {
        if ($(this).is(':checked')) {
            $('.vehicle-row').show();
        } else {
            $('.vehicle-row').hide();
            $('#vehicle_plate').val('');
        }
    });
    
    // Evcil hayvan var/yok değiştiğinde ilgili alanları göster/gizle
    $('#has_pet').change(function() {
        if ($(this).is(':checked')) {
            $('.pet-row').show();
        } else {
            $('.pet-row').hide();
            $('#pet_name, #pet_age').val('');
            $('#pet_type').val('');
        }
    });
    
    // Çocuk sayısı değiştiğinde çocuk alanlarını güncelle
    $('#children_count').change(function() {
        updateChildrenFields();
    });
    
    function updateChildrenFields() {
        var count = parseInt($('#children_count').val()) || 0;
        var container = $('#children-container');
        
        // Mevcut alanları temizle
        container.empty();
        
        // Seçilen sayıda çocuk alanı ekle
        for (var i = 1; i <= count; i++) {
            var row = $('<div class="child-row"></div>');
            var fields = $('<div class="child-fields"></div>');
            
            fields.append('<input type="text" name="child_name_' + i + '" placeholder="Çocuğun Adı" class="regular-text">');
            fields.append('<input type="date" name="child_birth_date_' + i + '" placeholder="Doğum Tarihi" class="regular-text">');
            
            row.append(fields);
            container.append(row);
        }
    }
    
    // Sayfa yüklendiğinde çocuk alanlarını oluştur
    if (!$('#children-container').children().length) {
        updateChildrenFields();
    }
});
</script>