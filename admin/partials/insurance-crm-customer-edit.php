<?php
/**
 * Müşteri düzenleme sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.3
 */

if (!defined('WPINC')) {
    die;
}

// Müşteri ID'sini al
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Form gönderildiyse
if (isset($_POST['submit_customer']) && isset($_POST['customer_nonce']) && 
    wp_verify_nonce($_POST['customer_nonce'], 'edit_customer')) {
    
    // Müşteri verileri
    $customer_data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'tc_identity' => sanitize_text_field($_POST['tc_identity']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'address' => sanitize_textarea_field($_POST['address']),
        'category' => sanitize_text_field($_POST['category']),
        'status' => sanitize_text_field($_POST['status']),
        'representative_id' => isset($_POST['representative_id']) ? intval($_POST['representative_id']) : NULL
    );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_customers';
    
    if ($customer_id > 0) {
        // Mevcut müşteriyi güncelle
        $wpdb->update(
            $table_name,
            $customer_data,
            array('id' => $customer_id)
        );
        
        echo '<div class="updated"><p>Müşteri başarıyla güncellendi.</p></div>';
    } else {
        // Yeni müşteri ekle
        $wpdb->insert(
            $table_name,
            $customer_data
        );
        $customer_id = $wpdb->insert_id;
        
        echo '<div class="updated"><p>Müşteri başarıyla eklendi.</p></div>';
    }
}

// Müşteri verilerini al
$customer = null;
if ($customer_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'insurance_crm_customers';
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $customer_id
    ));
}

// Müşteri temsilcilerini al
$representatives = array();
global $wpdb;
$table_reps = $wpdb->prefix . 'insurance_crm_representatives';
$table_users = $wpdb->prefix . 'users';
$query = "
    SELECT r.id, u.display_name 
    FROM $table_reps r
    JOIN $table_users u ON r.user_id = u.ID
    WHERE r.status = 'active'
    ORDER BY u.display_name ASC
";
$representatives = $wpdb->get_results($query);

?>

<div class="wrap">
    <h1><?php echo $customer_id > 0 ? 'Müşteri Düzenle' : 'Yeni Müşteri'; ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('edit_customer', 'customer_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="first_name">Ad</label></th>
                <td>
                    <input type="text" id="first_name" name="first_name" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->first_name) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="last_name">Soyad</label></th>
                <td>
                    <input type="text" id="last_name" name="last_name" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->last_name) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="tc_identity">TC Kimlik No</label></th>
                <td>
                    <input type="text" id="tc_identity" name="tc_identity" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->tc_identity) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="email">E-posta</label></th>
                <td>
                    <input type="email" id="email" name="email" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->email) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="phone">Telefon</label></th>
                <td>
                    <input type="tel" id="phone" name="phone" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->phone) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="address">Adres</label></th>
                <td>
                    <textarea id="address" name="address" rows="4" cols="50"><?php echo $customer ? esc_textarea($customer->address) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="category">Kategori</label></th>
                <td>
                    <select id="category" name="category">
                        <option value="bireysel" <?php echo ($customer && $customer->category == 'bireysel') ? 'selected' : ''; ?>>Bireysel</option>
                        <option value="kurumsal" <?php echo ($customer && $customer->category == 'kurumsal') ? 'selected' : ''; ?>>Kurumsal</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status">Durum</label></th>
                <td>
                    <select id="status" name="status">
                        <option value="aktif" <?php echo ($customer && $customer->status == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="pasif" <?php echo ($customer && $customer->status == 'pasif') ? 'selected' : ''; ?>>Pasif</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="representative_id">Müşteri Temsilcisi</label></th>
                <td>
                    <select id="representative_id" name="representative_id">
                        <option value="">Seçiniz</option>
                        <?php foreach ($representatives as $rep): ?>
                            <option value="<?php echo esc_attr($rep->id); ?>" 
                                <?php echo ($customer && $customer->representative_id == $rep->id) ? 'selected' : ''; ?>>
                                <?php echo esc_html($rep->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_customer" class="button button-primary" value="<?php echo $customer_id > 0 ? 'Güncelle' : 'Ekle'; ?>">
        </p>
    </form>
</div>