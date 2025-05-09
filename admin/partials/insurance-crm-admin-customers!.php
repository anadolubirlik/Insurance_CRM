<?php
/**
 * Müşteriler Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.0 (2025-05-02)
 */

if (!defined('WPINC')) {
    die;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer = new Insurance_CRM_Customer();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insurance_crm_nonce'])) {
    if (!wp_verify_nonce($_POST['insurance_crm_nonce'], 'insurance_crm_save_customer')) {
        wp_die(__('Güvenlik doğrulaması başarısız', 'insurance-crm'));
    }

    $data = array(
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'tc_identity' => sanitize_text_field($_POST['tc_identity']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'address' => sanitize_textarea_field($_POST['address']),
        'category' => sanitize_text_field($_POST['category']),
        'status' => sanitize_text_field($_POST['status']),
        'representative_id' => isset($_POST['representative_id']) ? intval($_POST['representative_id']) : null
    );

    if ($id > 0) {
        $result = $customer->update($id, $data);
    } else {
        $result = $customer->add($data);
    }

    if (is_wp_error($result)) {
        $error_message = $result->get_error_message();
    } else {
        wp_redirect(admin_url('admin.php?page=insurance-crm-customers&message=' . ($id ? 'updated' : 'added')));
        exit;
    }
}

// Mesaj gösterimi
if (isset($_GET['message'])) {
    $message = '';
    switch ($_GET['message']) {
        case 'added':
            $message = __('Müşteri başarıyla eklendi.', 'insurance-crm');
            break;
        case 'updated':
            $message = __('Müşteri başarıyla güncellendi.', 'insurance-crm');
            break;
        case 'deleted':
            $message = __('Müşteri başarıyla silindi.', 'insurance-crm');
            break;
    }
    if ($message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
}

// Hata gösterimi
if (isset($error_message)) {
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
}
?>

<div class="wrap insurance-crm-wrap">
    <div class="insurance-crm-header">
        <h1>
            <?php 
            if ($action === 'new') {
                _e('Yeni Müşteri', 'insurance-crm');
            } elseif ($action === 'edit') {
                _e('Müşteri Düzenle', 'insurance-crm');
            } else {
                _e('Müşteriler', 'insurance-crm');
            }
            ?>
        </h1>
        <?php if ($action === 'list'): ?>
            <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers&action=new'); ?>" class="page-title-action">
                <?php _e('Yeni Ekle', 'insurance-crm'); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        
        <!-- Filtre Formu -->
        <div class="tablenav top">
            <form method="get" class="insurance-crm-filter-form">
                <input type="hidden" name="page" value="insurance-crm-customers">
                
                <div class="alignleft actions">
                    <input type="search" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="<?php _e('Müşteri Ara...', 'insurance-crm'); ?>">
                    
                    <select name="category">
                        <option value=""><?php _e('Tüm Kategoriler', 'insurance-crm'); ?></option>
                        <option value="bireysel" <?php selected(isset($_GET['category']) ? $_GET['category'] : '', 'bireysel'); ?>><?php _e('Bireysel', 'insurance-crm'); ?></option>
                        <option value="kurumsal" <?php selected(isset($_GET['category']) ? $_GET['category'] : '', 'kurumsal'); ?>><?php _e('Kurumsal', 'insurance-crm'); ?></option>
                    </select>
                    
                    <select name="status">
                        <option value=""><?php _e('Tüm Durumlar', 'insurance-crm'); ?></option>
                        <option value="aktif" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'aktif'); ?>><?php _e('Aktif', 'insurance-crm'); ?></option>
                        <option value="pasif" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pasif'); ?>><?php _e('Pasif', 'insurance-crm'); ?></option>
                    </select>
                    
                    <?php submit_button(__('Filtrele', 'insurance-crm'), 'action', '', false); ?>
                </div>
            </form>
        </div>

        <!-- Müşteri Listesi -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Ad Soyad', 'insurance-crm'); ?></th>
                    <th><?php _e('TC Kimlik', 'insurance-crm'); ?></th>
                    <th><?php _e('E-posta', 'insurance-crm'); ?></th>
                    <th><?php _e('Telefon', 'insurance-crm'); ?></th>
                    <th><?php _e('Kategori', 'insurance-crm'); ?></th>
                    <th><?php _e('Durum', 'insurance-crm'); ?></th>
                    <th><?php _e('Müşteri Temsilcisi', 'insurance-crm'); ?></th>
                    <th><?php _e('Poliçeler', 'insurance-crm'); ?></th>
                    <th><?php _e('İşlemler', 'insurance-crm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $args = array(
                    'search' => isset($_GET['s']) ? $_GET['s'] : '',
                    'category' => isset($_GET['category']) ? $_GET['category'] : '',
                    'status' => isset($_GET['status']) ? $_GET['status'] : ''
                );
                
                $customers = $customer->get_all($args);
                
                if (!empty($customers)):
                    foreach ($customers as $item):
                        $edit_url = admin_url('admin.php?page=insurance-crm-customers&action=edit&id=' . $item->id);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=insurance-crm-customers&action=delete&id=' . $item->id), 'delete_customer_' . $item->id);
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->first_name . ' ' . $item->last_name); ?></a></strong>
                        </td>
                        <td><?php echo esc_html($item->tc_identity); ?></td>
                        <td><?php echo esc_html($item->email); ?></td>
                        <td><?php echo esc_html($item->phone); ?></td>
                        <td>
                            <?php
                            $category_class = $item->category === 'kurumsal' ? 'insurance-crm-badge-warning' : 'insurance-crm-badge-success';
                            echo '<span class="insurance-crm-badge ' . $category_class . '">' . esc_html($item->category) . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_class = $item->status === 'aktif' ? 'insurance-crm-badge-success' : 'insurance-crm-badge-danger';
                            echo '<span class="insurance-crm-badge ' . $status_class . '">' . esc_html($item->status) . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($item->representative_id)) {
                                global $wpdb;
                                $representative = $wpdb->get_row($wpdb->prepare("
                                    SELECT r.*, u.display_name 
                                    FROM {$wpdb->prefix}insurance_crm_representatives r
                                    JOIN {$wpdb->users} u ON r.user_id = u.ID
                                    WHERE r.id = %d
                                ", $item->representative_id));
                                echo $representative ? esc_html($representative->display_name) : '-';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $policy = new Insurance_CRM_Policy();
                            $policy_count = count($policy->get_all(array('customer_id' => $item->id)));
                            echo '<a href="' . admin_url('admin.php?page=insurance-crm-policies&customer_id=' . $item->id) . '">' . 
                                 sprintf(__('%d poliçe', 'insurance-crm'), $policy_count) . '</a>';
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo $edit_url; ?>" class="button button-small"><?php _e('Düzenle', 'insurance-crm'); ?></a>
                            <a href="<?php echo $delete_url; ?>" class="button button-small button-link-delete insurance-crm-delete" onclick="return confirm('<?php _e('Bu müşteriyi silmek istediğinizden emin misiniz?', 'insurance-crm'); ?>')">
                                <?php _e('Sil', 'insurance-crm'); ?>
                            </a>
                        </td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="9"><?php _e('Müşteri bulunamadı.', 'insurance-crm'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        
        <!-- Müşteri Formu -->
        <?php
        $customer_data = new stdClass();
        if ($action === 'edit') {
            $customer_data = $customer->get($id);
            if (!$customer_data) {
                wp_die(__('Müşteri bulunamadı.', 'insurance-crm'));
            }
        }
        ?>
        
        <form method="post" class="insurance-crm-form">
            <?php wp_nonce_field('insurance_crm_save_customer', 'insurance_crm_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="first_name"><?php _e('Ad', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="first_name" id="first_name" class="regular-text" 
                               value="<?php echo isset($customer_data->first_name) ? esc_attr($customer_data->first_name) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="last_name"><?php _e('Soyad', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="last_name" id="last_name" class="regular-text" 
                               value="<?php echo isset($customer_data->last_name) ? esc_attr($customer_data->last_name) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="tc_identity"><?php _e('TC Kimlik No', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="tc_identity" id="tc_identity" class="regular-text" maxlength="11" 
                               value="<?php echo isset($customer_data->tc_identity) ? esc_attr($customer_data->tc_identity) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email"><?php _e('E-posta', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="email" name="email" id="email" class="regular-text" 
                               value="<?php echo isset($customer_data->email) ? esc_attr($customer_data->email) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="phone"><?php _e('Telefon', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="tel" name="phone" id="phone" class="regular-text" 
                               value="<?php echo isset($customer_data->phone) ? esc_attr($customer_data->phone) : ''; ?>" required>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="address"><?php _e('Adres', 'insurance-crm'); ?></label>
                    </th>
                    <td>
                        <textarea name="address" id="address" class="large-text" rows="5"><?php echo isset($customer_data->address) ? esc_textarea($customer_data->address) : ''; ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="category"><?php _e('Kategori', 'insurance-crm'); ?></label>
                    </th>
                    <td>
                        <select name="category" id="category">
                            <option value="bireysel" <?php selected(isset($customer_data->category) ? $customer_data->category : '', 'bireysel'); ?>><?php _e('Bireysel', 'insurance-crm'); ?></option>
                            <option value="kurumsal" <?php selected(isset($customer_data->category) ? $customer_data->category : '', 'kurumsal'); ?>><?php _e('Kurumsal', 'insurance-crm'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="status"><?php _e('Durum', 'insurance-crm'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="aktif" <?php selected(isset($customer_data->status) ? $customer_data->status : '', 'aktif'); ?>><?php _e('Aktif', 'insurance-crm'); ?></option>
                            <option value="pasif" <?php selected(isset($customer_data->status) ? $customer_data->status : '', 'pasif'); ?>><?php _e('Pasif', 'insurance-crm'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="representative_id"><?php _e('Müşteri Temsilcisi', 'insurance-crm'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <?php
                        global $wpdb;
                        $representatives = $wpdb->get_results("
                            SELECT r.*, u.display_name 
                            FROM {$wpdb->prefix}insurance_crm_representatives r
                            JOIN {$wpdb->users} u ON r.user_id = u.ID
                            WHERE r.status = 'active'
                            ORDER BY u.display_name ASC
                        ");
                        
                        $current_rep = isset($customer_data->representative_id) ? $customer_data->representative_id : '';
                        ?>
                        <select name="representative_id" id="representative_id" required>
                            <option value=""><?php _e('Seçiniz...', 'insurance-crm'); ?></option>
                            <?php foreach($representatives as $rep): ?>
                                <option value="<?php echo $rep->id; ?>" <?php selected($current_rep, $rep->id); ?>>
                                    <?php echo esc_html($rep->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $action === 'edit' ? __('Güncelle', 'insurance-crm') : __('Ekle', 'insurance-crm'); ?>">
                <a href="<?php echo admin_url('admin.php?page=insurance-crm-customers'); ?>" class="button"><?php _e('İptal', 'insurance-crm'); ?></a>
            </p>
        </form>

    <?php endif; ?>
</div>