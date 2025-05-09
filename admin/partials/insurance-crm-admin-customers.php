<?php
/**
 * Müşteriler Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.3
 */

if (!defined('WPINC')) {
    die;
}

// Düzenleme işlemi için müşteri ID'sini kontrol et
$customer_id = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = ($customer_id > 0);

// Düzenleme işlemi için müşteri düzenleme sayfasına yönlendir
if ($editing) {
    // Müşteri düzenleme sayfasına bırak - buradan sonraki kodlar çalışmayacak
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php';
    return; // Bu satırı sonrasında kalan kodlar çalışmayacak
}

// Yeni müşteri ekleme işlemi
if (isset($_POST['submit_customer']) && isset($_POST['customer_nonce']) && 
    wp_verify_nonce($_POST['customer_nonce'], 'add_customer')) {
    
    $customer_data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'tc_identity' => sanitize_text_field($_POST['tc_identity']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'address' => sanitize_textarea_field($_POST['address']),
        'category' => sanitize_text_field($_POST['category']),
        'status' => sanitize_text_field($_POST['status']),
        'representative_id' => isset($_POST['representative_id']) ? intval($_POST['representative_id']) : NULL,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_customers';
    
    $wpdb->insert($table_name, $customer_data);
    $new_customer_id = $wpdb->insert_id;
    
    if ($new_customer_id) {
        echo '<div class="updated"><p>Müşteri başarıyla eklendi.</p></div>';
    } else {
        echo '<div class="error"><p>Müşteri eklenirken bir hata oluştu.</p></div>';
    }
}

// Silme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_customer_' . $_GET['id'])) {
        global $wpdb;
        $customer_id = intval($_GET['id']);
        $table_name = $wpdb->prefix . 'insurance_crm_customers';
        
        $wpdb->delete($table_name, array('id' => $customer_id));
        echo '<div class="updated"><p>Müşteri silindi.</p></div>';
    }
}

// Arama işlemi
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Müşterileri listele
global $wpdb;
$table_name = $wpdb->prefix . 'insurance_crm_customers';
$table_reps = $wpdb->prefix . 'insurance_crm_representatives';
$table_users = $wpdb->users;

$query = "
    SELECT c.*, u.display_name as representative_name
    FROM $table_name c
    LEFT JOIN $table_reps r ON c.representative_id = r.id
    LEFT JOIN $table_users u ON r.user_id = u.ID
";

if (!empty($search)) {
    $query .= $wpdb->prepare(
        " WHERE c.first_name LIKE %s OR c.last_name LIKE %s OR c.tc_identity LIKE %s OR c.email LIKE %s OR c.phone LIKE %s",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

$query .= " ORDER BY c.id DESC";

$customers = $wpdb->get_results($query);

// Müşteri temsilcilerini al
$representatives = $wpdb->get_results("
    SELECT r.id, u.display_name 
    FROM $table_reps r
    JOIN $table_users u ON r.user_id = u.ID
    WHERE r.status = 'active'
    ORDER BY u.display_name ASC
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Müşteriler</h1>
    <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=new'); ?>" class="page-title-action">Yeni Ekle</a>
    
    <hr class="wp-header-end">
    
    <form method="get">
        <input type="hidden" name="page" value="insurance-crm-customers">
        <p class="search-box">
            <label class="screen-reader-text" for="customer-search-input">Müşteri ara:</label>
            <input type="search" id="customer-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Ad, soyad, TC, telefon veya e-posta">
            <input type="submit" id="search-submit" class="button" value="Ara">
        </p>
    </form>
    
    <table class="wp-list-table widefat fixed striped customers">
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>TC Kimlik</th>
                <th>E-posta</th>
                <th>Telefon</th>
                <th>Kategori</th>
                <th>Durum</th>
                <th>Temsilci</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="8">Hiç müşteri bulunamadı.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $customer->id); ?>"><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></a></strong>
                        </td>
                        <td><?php echo esc_html($customer->tc_identity); ?></td>
                        <td><?php echo esc_html($customer->email); ?></td>
                        <td><?php echo esc_html($customer->phone); ?></td>
                        <td>
                            <?php 
                            if ($customer->category == 'bireysel') {
                                echo '<span class="customer-category bireysel">Bireysel</span>';
                            } else {
                                echo '<span class="customer-category kurumsal">Kurumsal</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($customer->status == 'aktif') {
                                echo '<span class="customer-status aktif">Aktif</span>';
                            } else {
                                echo '<span class="customer-status pasif">Pasif</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($customer->representative_name ? $customer->representative_name : '—'); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $customer->id); ?>" class="button button-small">Düzenle</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=insurance-crm-customers&action=delete&id=' . $customer->id), 'delete_customer_' . $customer->id); ?>" class="button button-small delete-customer" data-customer-name="<?php echo esc_attr($customer->first_name . ' ' . $customer->last_name); ?>" onclick="return confirm('<?php echo esc_attr($customer->first_name . ' ' . $customer->last_name); ?> müşterisini silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
    <hr>
    <h2>Yeni Müşteri</h2>
    <form method="post" action="">
        <?php wp_nonce_field('add_customer', 'customer_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="first_name">Ad <span class="required">*</span></label></th>
                <td><input type="text" id="first_name" name="first_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="last_name">Soyad <span class="required">*</span></label></th>
                <td><input type="text" id="last_name" name="last_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="tc_identity">TC Kimlik No <span class="required">*</span></label></th>
                <td><input type="text" id="tc_identity" name="tc_identity" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="email">E-posta <span class="required">*</span></label></th>
                <td><input type="email" id="email" name="email" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="phone">Telefon <span class="required">*</span></label></th>
                <td><input type="tel" id="phone" name="phone" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="address">Adres</label></th>
                <td><textarea id="address" name="address" rows="4" cols="50"></textarea></td>
            </tr>
            <tr>
                <th><label for="category">Kategori</label></th>
                <td>
                    <select id="category" name="category">
                        <option value="bireysel">Bireysel</option>
                        <option value="kurumsal">Kurumsal</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status">Durum</label></th>
                <td>
                    <select id="status" name="status">
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="representative_id">Müşteri Temsilcisi</label></th>
                <td>
                    <select id="representative_id" name="representative_id">
                        <option value="">Seçiniz</option>
                        <?php foreach ($representatives as $rep): ?>
                            <option value="<?php echo esc_attr($rep->id); ?>">
                                <?php echo esc_html($rep->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_customer" class="button button-primary" value="Müşteri Ekle">
        </p>
    </form>
    <?php endif; ?>
</div>

<style>
    .customer-status, .customer-category {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .customer-status.aktif {
        background-color: #dcf5dc;
        color: #0a3622;
    }
    .customer-status.pasif {
        background-color: #f5dcdc;
        color: #360a0a;
    }
    .customer-category.bireysel {
        background-color: #e0f0ff;
        color: #0a366c;
    }
    .customer-category.kurumsal {
        background-color: #daf0e8;
        color: #0a3636;
    }
    .required {
        color: #dc3232;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // TC Kimlik doğrulama
    $('#tc_identity').on('change', function() {
        var $input = $(this);
        var tc = $input.val();

        if (tc.length !== 11 || !validateTC(tc)) {
            $input.addClass('error');
            alert('Geçersiz TC Kimlik numarası!');
            $input.val('');
        } else {
            $input.removeClass('error');
        }
    });

    // TC Kimlik algoritma kontrolü
    function validateTC(value) {
        if (value.substring(0, 1) === '0') return false;
        if (!(/^[0-9]+$/.test(value))) return false;

        var digits = value.split('');
        var sum = 0;
        for (var i = 0; i < 10; i++) {
            sum += parseInt(digits[i]);
        }
        return sum % 10 === parseInt(digits[10]);
    }
});
</script>