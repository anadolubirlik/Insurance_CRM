<?php
/**
 * Insurance CRM
 *
 * @package     Insurance_CRM
 * @author      Anadolu Birlik
 * @copyright   2025 Anadolu Birlik
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Insurance CRM
 * Plugin URI:  https://github.com/anadolubirlik/insurance-crm
 * Description: Sigorta acenteleri için müşteri ve poliçe yönetim sistemi.
 * Version:     1.0.3
 * Author:      Anadolu Birlik
 * Author URI:  https://github.com/anadolubirlik
 * Text Domain: insurance-crm
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('INSURANCE_CRM_VERSION', '1.0.3');

/**
 * Plugin base path
 */
define('INSURANCE_CRM_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin URL
 */
define('INSURANCE_CRM_URL', plugin_dir_url(__FILE__));

/**
 * Plugin activation.
 */
function activate_insurance_crm() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-insurance-crm-activator.php';
    Insurance_CRM_Activator::activate();
    
    // Menü sorununu çözmek için bilgi kaydedelim
    add_option('insurance_crm_activation_time', time());
    update_option('insurance_crm_menu_initialized', 'no');
    update_option('insurance_crm_menu_cache_cleared', 'no');
}

/**
 * Plugin deactivation.
 */
function deactivate_insurance_crm() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-insurance-crm-deactivator.php';
    Insurance_CRM_Deactivator::deactivate();
    
    // Menü sayısı sorununu çözmek için temizleme işlemi
    delete_option('insurance_crm_menu_initialized');
    delete_option('insurance_crm_menu_cache_cleared');
}

register_activation_hook(__FILE__, 'activate_insurance_crm');
register_deactivation_hook(__FILE__, 'deactivate_insurance_crm');

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-insurance-crm.php';

/**
 * Begins execution of the plugin.
 */
function run_insurance_crm() {
    $plugin = new Insurance_CRM();
    $plugin->run();
}

run_insurance_crm();

/**
 * Custom capabilities for the plugin
 */
function insurance_crm_add_capabilities() {
    $roles = array('administrator', 'editor');
    $capabilities = array(
        'read_insurance_crm',
        'edit_insurance_crm',
        'edit_others_insurance_crm',
        'publish_insurance_crm',
        'read_private_insurance_crm',
        'manage_insurance_crm'
    );

    foreach ($roles as $role) {
        $role_obj = get_role($role);
        if (!empty($role_obj)) {
            foreach ($capabilities as $cap) {
                $role_obj->add_cap($cap);
            }
        }
    }
}
register_activation_hook(__FILE__, 'insurance_crm_add_capabilities');

/**
 * Database tables creation
 */
function insurance_crm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Müşteriler tablosu
    $table_customers = $wpdb->prefix . 'insurance_crm_customers';
    $sql_customers = "CREATE TABLE IF NOT EXISTS $table_customers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        tc_identity varchar(11) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        address text,
        category varchar(20) DEFAULT 'bireysel',
        status varchar(20) DEFAULT 'aktif',
        representative_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY tc_identity (tc_identity),
        KEY email (email),
        KEY status (status),
        KEY representative_id (representative_id)
    ) $charset_collate;";

    // Poliçeler tablosu
    $table_policies = $wpdb->prefix . 'insurance_crm_policies';
    $sql_policies = "CREATE TABLE IF NOT EXISTS $table_policies (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        policy_number varchar(50) NOT NULL,
        policy_type varchar(50) NOT NULL,
        start_date date NOT NULL,
        end_date date NOT NULL,
        premium_amount decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'aktif',
        document_path varchar(255),
        representative_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY policy_number (policy_number),
        KEY customer_id (customer_id),
        KEY status (status),
        KEY end_date (end_date),
        KEY representative_id (representative_id)
    ) $charset_collate;";

    // Görevler tablosu
    $table_tasks = $wpdb->prefix . 'insurance_crm_tasks';
    $sql_tasks = "CREATE TABLE IF NOT EXISTS $table_tasks (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        policy_id bigint(20),
        task_description text NOT NULL,
        due_date datetime NOT NULL,
        priority varchar(20) DEFAULT 'medium',
        status varchar(20) DEFAULT 'pending',
        representative_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY customer_id (customer_id),
        KEY policy_id (policy_id),
        KEY status (status),
        KEY due_date (due_date),
        KEY representative_id (representative_id)
    ) $charset_collate;";

    // Müşteri temsilcileri tablosu
    $table_representatives = $wpdb->prefix . 'insurance_crm_representatives';
    $sql_representatives = "CREATE TABLE IF NOT EXISTS $table_representatives (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        department varchar(100) NOT NULL,
        monthly_target decimal(10,2) DEFAULT 0.00,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_customers);
    dbDelta($sql_policies);
    dbDelta($sql_tasks);
    dbDelta($sql_representatives);

    // Diğer tablolara representative_id kolonunu ekle
    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_customers ADD COLUMN IF NOT EXISTS representative_id bigint(20) DEFAULT NULL");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_customers ADD KEY IF NOT EXISTS representative_id (representative_id)");

    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_policies ADD COLUMN IF NOT EXISTS representative_id bigint(20) DEFAULT NULL");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_policies ADD KEY IF NOT EXISTS representative_id (representative_id)");

    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_tasks ADD COLUMN IF NOT EXISTS representative_id bigint(20) DEFAULT NULL");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}insurance_crm_tasks ADD KEY IF NOT EXISTS representative_id (representative_id)");
}
register_activation_hook(__FILE__, 'insurance_crm_create_tables');

/**
 * Otomatik yenileme hatırlatmaları için cron job
 */
function insurance_crm_schedule_cron_job() {
    if (!wp_next_scheduled('insurance_crm_daily_cron')) {
        wp_schedule_event(time(), 'daily', 'insurance_crm_daily_cron');
    }
}
register_activation_hook(__FILE__, 'insurance_crm_schedule_cron_job');

/**
 * Cron job kaldırma
 */
function insurance_crm_unschedule_cron_job() {
    wp_clear_scheduled_hook('insurance_crm_daily_cron');
}
register_deactivation_hook(__FILE__, 'insurance_crm_unschedule_cron_job');

/**
 * Veritabanı kurulumu için tablo kontrolü
 */
function insurance_crm_check_db_tables() {
    global $wpdb;
    
    $tables = array(
        'insurance_crm_customers',
        'insurance_crm_policies',
        'insurance_crm_tasks',
        'insurance_crm_representatives'
    );
    
    $missing_tables = array();
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        insurance_crm_create_tables();
    }
}
add_action('plugins_loaded', 'insurance_crm_check_db_tables');

/**
 * Günlük kontroller ve bildirimler
 */
function insurance_crm_daily_tasks() {
    global $wpdb;
    $settings = get_option('insurance_crm_settings');
    $renewal_days = isset($settings['renewal_reminder_days']) ? intval($settings['renewal_reminder_days']) : 30;
    $task_days = isset($settings['task_reminder_days']) ? intval($settings['task_reminder_days']) : 1;

    // Yaklaşan poliçe yenilemeleri
    $upcoming_renewals = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, c.first_name, c.last_name, c.email 
         FROM {$wpdb->prefix}insurance_crm_policies p
         JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
         WHERE p.status = 'aktif' 
         AND p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)",
        $renewal_days
    ));

    // Yaklaşan görevler
    $upcoming_tasks = $wpdb->get_results($wpdb->prepare(
        "SELECT t.*, c.first_name, c.last_name, c.email 
         FROM {$wpdb->prefix}insurance_crm_tasks t
         JOIN {$wpdb->prefix}insurance_crm_customers c ON t.customer_id = c.id
         WHERE t.status = 'pending'
         AND t.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL %d DAY)",
        $task_days
    ));

    // E-posta bildirimleri gönder
    if (!empty($upcoming_renewals) || !empty($upcoming_tasks)) {
        $notification_email = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');

        $message = "Merhaba,\n\n";
        $message .= "Bu e-posta $company_name sisteminden otomatik olarak gönderilmiştir.\n\n";

        if (!empty($upcoming_renewals)) {
            $message .= "YAKLAŞAN POLİÇE YENİLEMELERİ:\n";
            $message .= "--------------------------------\n";
            foreach ($upcoming_renewals as $renewal) {
                $days_left = (strtotime($renewal->end_date) - time()) / (60 * 60 * 24);
                $message .= sprintf(
                    "%s %s - Poliçe No: %s - Bitiş Tarihi: %s (%d gün kaldı)\n",
                    $renewal->first_name,
                    $renewal->last_name,
                    $renewal->policy_number,
                    date('d.m.Y', strtotime($renewal->end_date)),
                    ceil($days_left)
                );
            }
            $message .= "\n";
        }

        if (!empty($upcoming_tasks)) {
            $message .= "YAKLAŞAN GÖREVLER:\n";
            $message .= "--------------------------------\n";
            foreach ($upcoming_tasks as $task) {
                $days_left = (strtotime($task->due_date) - time()) / (60 * 60 * 24);
                $message .= sprintf(
                    "%s %s - %s - Tarih: %s (%d gün kaldı)\n",
                    $task->first_name,
                    $task->last_name,
                    $task->task_description,
                    date('d.m.Y H:i', strtotime($task->due_date)),
                    ceil($days_left)
                );
            }
        }

        wp_mail(
            $notification_email,
            'Insurance CRM - Günlük Hatırlatmalar',
            $message,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }
}
add_action('insurance_crm_daily_cron', 'insurance_crm_daily_tasks');

/**
 * Müşteri Temsilcisi rolü ve yetkileri
 */
function insurance_crm_add_representative_role() {
    // Müşteri Temsilcisi rolünü ekle
    add_role('insurance_representative', 'Müşteri Temsilcisi', array(
        'read' => true,
        'upload_files' => true,
        'read_insurance_crm' => true,
        'edit_insurance_crm' => true,
        'publish_insurance_crm' => true
    ));
}
register_activation_hook(__FILE__, 'insurance_crm_add_representative_role');

/**
 * Yeni müşteri temsilcisi oluşturulduğunda otomatik kullanıcı oluştur
 */
function insurance_crm_create_representative_user($rep_id, $data) {
    $username = sanitize_user(strtolower($data['first_name'] . '.' . $data['last_name']));
    
    $original_username = $username;
    $count = 1;
    while (username_exists($username)) {
        $username = $original_username . $count;
        $count++;
    }

    $password = wp_generate_password();
    
    $user_data = array(
        'user_login' => $username,
        'user_pass' => $password,
        'user_email' => $data['email'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'role' => 'insurance_representative'
    );

    $user_id = wp_insert_user($user_data);

    if (!is_wp_error($user_id)) {
        update_user_meta($user_id, '_insurance_representative_id', $rep_id);
        wp_send_new_user_notifications($user_id, 'both');
        return $user_id;
    }

    return false;
}

/**
 * Admin initialize
 */
function insurance_crm_admin_init() {
    if (!class_exists('Insurance_CRM_Admin')) {
        require_once plugin_dir_path(__FILE__) . 'admin/class-insurance-crm-admin.php';
    }
    
    // Users page'i oluştur
    $admin_dir = plugin_dir_path(__FILE__) . 'admin/';
    if (!file_exists($admin_dir)) {
        mkdir($admin_dir, 0755, true);
    }
    
    $users_page_path = $admin_dir . 'users-page.php';
    if (!file_exists($users_page_path)) {
        $users_page_content = '<?php
if (!defined("ABSPATH")) {
    exit;
}

function insurance_crm_users_page() {
    echo "<div class=\"wrap\">";
    echo "<h1>Kullanıcı Yönetimi</h1>";
    echo "</div>";
}';
        file_put_contents($users_page_path, $users_page_content);
    }
}
add_action('admin_init', 'insurance_crm_admin_init');

/**
 * Get total premium amount
 */
function insurance_crm_get_total_premium($customer_id = null) {
    global $wpdb;
    
    $where = '';
    $args = array();
    
    if ($customer_id) {
        $where = 'WHERE customer_id = %d';
        $args[] = $customer_id;
    }
    
    $query = $wpdb->prepare(
        "SELECT SUM(premium_amount) as total_premium 
         FROM {$wpdb->prefix}insurance_crm_policies 
         $where",
        $args
    );
    
    return $wpdb->get_var($query);
}

/**
 * Plugin'i etkinleştirirken örnek veri ekle
 */
function insurance_crm_add_sample_data() {
    // Etkinleştirme sonrası örnek veri ekleme kontrolü
    if (get_option('insurance_crm_sample_data_added')) {
        return;
    }
    
    // Müşteri Temsilcisi rolü ekle ve örnek temsilci oluştur
    if (!get_role('insurance_representative')) {
        add_role('insurance_representative', 'Müşteri Temsilcisi', array(
            'read' => true,
            'upload_files' => true,
            'read_insurance_crm' => true,
            'edit_insurance_crm' => true,
            'publish_insurance_crm' => true
        ));
    }
    
    // Örnek Müşteri Temsilcisi Kullanıcısı Oluştur
    $username = 'temsilci';
    if (!username_exists($username) && !email_exists('temsilci@example.com')) {
        $user_id = wp_create_user(
            $username,
            'temsilci123',
            'temsilci@example.com'
        );
        
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role('insurance_representative');
            
            // Temsilci profili oluştur
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'insurance_crm_representatives',
                array(
                    'user_id' => $user_id,
                    'title' => 'Kıdemli Müşteri Temsilcisi',
                    'phone' => '5551234567',
                    'department' => 'Bireysel Satış',
                    'monthly_target' => 50000.00,
                    'status' => 'active'
                )
            );
        }
    }
    
    // Örnek veri eklendi olarak işaretle
    update_option('insurance_crm_sample_data_added', true);
}
register_activation_hook(__FILE__, 'insurance_crm_add_sample_data');

/**
 * Plugin güncelleme kontrolü ve işlemleri
 */
function insurance_crm_update_check() {
    $current_version = get_option('insurance_crm_version');
    
    if ($current_version !== INSURANCE_CRM_VERSION) {
        // Temizleme işlemi yapılıyor - kritik
        delete_option('insurance_crm_menu_initialized');
        delete_option('insurance_crm_menu_cache_cleared');
        update_option('insurance_crm_version', INSURANCE_CRM_VERSION);
        
        // 1.0.3 için menü tekrarlanma sorununu çözmek için
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%transient%menu%'");
        
        // Yeni dosyaların oluşturulması
        insurance_crm_create_required_files();
    }
}
add_action('plugins_loaded', 'insurance_crm_update_check');

/**
 * 1.0.3 versiyonu için gerekli dosyaları oluştur
 */
function insurance_crm_create_required_files() {
    // Admin klasörünü kontrol et
    $admin_dir = INSURANCE_CRM_PATH . 'admin/';
    $partials_dir = $admin_dir . 'partials/';
    
    // Gerekli dizinleri oluştur
    if (!file_exists($admin_dir)) {
        mkdir($admin_dir, 0755, true);
    }
    
    if (!file_exists($partials_dir)) {
        mkdir($partials_dir, 0755, true);
    }
    
    // Müşteri düzenleme sayfası
    $customer_edit_content = '<?php
/**
 * Müşteri düzenleme sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.3
 */

if (!defined("WPINC")) {
    die;
}

// Müşteri ID\'sini al
$customer_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// Form gönderildiyse
if (isset($_POST["submit_customer"]) && isset($_POST["customer_nonce"]) && 
    wp_verify_nonce($_POST["customer_nonce"], "edit_customer")) {
    
    // Müşteri verileri
    $customer_data = array(
        "first_name" => sanitize_text_field($_POST["first_name"]),
        "last_name" => sanitize_text_field($_POST["last_name"]),
        "tc_identity" => sanitize_text_field($_POST["tc_identity"]),
        "email" => sanitize_email($_POST["email"]),
        "phone" => sanitize_text_field($_POST["phone"]),
        "address" => sanitize_textarea_field($_POST["address"]),
        "category" => sanitize_text_field($_POST["category"]),
        "status" => sanitize_text_field($_POST["status"]),
        "representative_id" => isset($_POST["representative_id"]) ? intval($_POST["representative_id"]) : NULL
    );
    
    global $wpdb;
    $table_name = $wpdb->prefix . "insurance_crm_customers";
    
    if ($customer_id > 0) {
        // Mevcut müşteriyi güncelle
        $wpdb->update(
            $table_name,
            $customer_data,
            array("id" => $customer_id)
        );
        
        echo \'<div class="updated"><p>Müşteri başarıyla güncellendi.</p></div>\';
    } else {
        // Yeni müşteri ekle
        $wpdb->insert(
            $table_name,
            $customer_data
        );
        $customer_id = $wpdb->insert_id;
        
        echo \'<div class="updated"><p>Müşteri başarıyla eklendi.</p></div>\';
    }
}

// Müşteri verilerini al
$customer = null;
if ($customer_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . "insurance_crm_customers";
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $customer_id
    ));
}

// Müşteri temsilcilerini al
$representatives = array();
global $wpdb;
$table_reps = $wpdb->prefix . "insurance_crm_representatives";
$table_users = $wpdb->users;
$query = "
    SELECT r.id, u.display_name 
    FROM $table_reps r
    JOIN $table_users u ON r.user_id = u.ID
    WHERE r.status = \'active\'
    ORDER BY u.display_name ASC
";
$representatives = $wpdb->get_results($query);

?>

<div class="wrap">
    <h1><?php echo $customer_id > 0 ? "Müşteri Düzenle" : "Yeni Müşteri"; ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field("edit_customer", "customer_nonce"); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="first_name">Ad</label></th>
                <td>
                    <input type="text" id="first_name" name="first_name" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->first_name) : ""; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="last_name">Soyad</label></th>
                <td>
                    <input type="text" id="last_name" name="last_name" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->last_name) : ""; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="tc_identity">TC Kimlik No</label></th>
                <td>
                    <input type="text" id="tc_identity" name="tc_identity" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->tc_identity) : ""; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="email">E-posta</label></th>
                <td>
                    <input type="email" id="email" name="email" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->email) : ""; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="phone">Telefon</label></th>
                <td>
                    <input type="tel" id="phone" name="phone" class="regular-text" 
                           value="<?php echo $customer ? esc_attr($customer->phone) : ""; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="address">Adres</label></th>
                <td>
                    <textarea id="address" name="address" rows="4" cols="50"><?php echo $customer ? esc_textarea($customer->address) : ""; ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="category">Kategori</label></th>
                <td>
                    <select id="category" name="category">
                        <option value="bireysel" <?php echo ($customer && $customer->category == "bireysel") ? "selected" : ""; ?>>Bireysel</option>
                        <option value="kurumsal" <?php echo ($customer && $customer->category == "kurumsal") ? "selected" : ""; ?>>Kurumsal</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="status">Durum</label></th>
                <td>
                    <select id="status" name="status">
                        <option value="aktif" <?php echo ($customer && $customer->status == "aktif") ? "selected" : ""; ?>>Aktif</option>
                        <option value="pasif" <?php echo ($customer && $customer->status == "pasif") ? "selected" : ""; ?>>Pasif</option>
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
                                <?php echo ($customer && $customer->representative_id == $rep->id) ? "selected" : ""; ?>>
                                <?php echo esc_html($rep->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_customer" class="button button-primary" value="<?php echo $customer_id > 0 ? "Güncelle" : "Ekle"; ?>">
        </p>
    </form>
</div>';
    
    $representatives_content = '<?php
/**
 * Müşteri Temsilcileri Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.3
 */

if (!defined("WPINC")) {
    die;
}

// Düzenleme işlemi için temsilci ID\'sini kontrol et
$rep_id = isset($_GET["edit"]) ? intval($_GET["edit"]) : 0;
$editing = ($rep_id > 0);
$edit_rep = null;

if ($editing) {
    global $wpdb;
    $table_reps = $wpdb->prefix . "insurance_crm_representatives";
    $table_users = $wpdb->users;
    
    $edit_rep = $wpdb->get_row($wpdb->prepare(
        "SELECT r.*, u.user_email as email, u.display_name, u.user_login as username,
                u.first_name, u.last_name
         FROM $table_reps r 
         LEFT JOIN $table_users u ON r.user_id = u.ID 
         WHERE r.id = %d",
        $rep_id
    ));
    
    if (!$edit_rep) {
        $editing = false;
    }
}

// Form gönderildiğinde işlem yap
if (isset($_POST["submit_representative"]) && isset($_POST["representative_nonce"]) && 
    wp_verify_nonce($_POST["representative_nonce"], "add_edit_representative")) {
    
    if ($editing) {
        // Mevcut temsilciyi güncelle
        $rep_data = array(
            "title" => sanitize_text_field($_POST["title"]),
            "phone" => sanitize_text_field($_POST["phone"]),
            "department" => sanitize_text_field($_POST["department"]),
            "monthly_target" => floatval($_POST["monthly_target"]),
            "updated_at" => current_time("mysql")
        );
        
        global $wpdb;
        $table_reps = $wpdb->prefix . "insurance_crm_representatives";
        
        $wpdb->update(
            $table_reps,
            $rep_data,
            array("id" => $rep_id)
        );
        
        // Kullanıcı bilgilerini güncelle
        if (isset($_POST["first_name"]) && isset($_POST["last_name"]) && isset($_POST["email"])) {
            $user_id = $edit_rep->user_id;
            wp_update_user(array(
                "ID" => $user_id,
                "first_name" => sanitize_text_field($_POST["first_name"]),
                "last_name" => sanitize_text_field($_POST["last_name"]),
                "display_name" => sanitize_text_field($_POST["first_name"]) . " " . sanitize_text_field($_POST["last_name"]),
                "user_email" => sanitize_email($_POST["email"])
            ));
        }
        
        // Şifre değiştirme kontrolü
        if (!empty($_POST["password"]) && !empty($_POST["confirm_password"]) && $_POST["password"] == $_POST["confirm_password"]) {
            wp_set_password($_POST["password"], $edit_rep->user_id);
        }
        
        echo \'<div class="notice notice-success"><p>Müşteri temsilcisi güncellendi.</p></div>\';
        
        // Yeniden yönlendir (şifre değişmesi durumunda)
        echo \'<script>window.location.href = "\' . admin_url("admin.php?page=insurance-crm-representatives") . \'";</script>\';
    } else {
        // Yeni kullanıcı oluştur
        if (isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["confirm_password"])) {
            $username = sanitize_user($_POST["username"]);
            $password = $_POST["password"];
            $confirm_password = $_POST["confirm_password"];
            
            if (empty($username) || empty($password) || empty($confirm_password)) {
                echo \'<div class="notice notice-error"><p>Kullanıcı adı ve şifre alanlarını doldurunuz.</p></div>\';
            } else if ($password !== $confirm_password) {
                echo \'<div class="notice notice-error"><p>Şifreler eşleşmiyor.</p></div>\';
            } else if (username_exists($username)) {
                echo \'<div class="notice notice-error"><p>Bu kullanıcı adı zaten kullanımda.</p></div>\';
            } else if (email_exists($_POST["email"])) {
                echo \'<div class="notice notice-error"><p>Bu e-posta adresi zaten kullanımda.</p></div>\';
            } else {
                // Kullanıcı oluştur
                $user_id = wp_create_user($username, $password, sanitize_email($_POST["email"]));
                
                if (!is_wp_error($user_id)) {
                    // Kullanıcı detaylarını güncelle
                    wp_update_user(
                        array(
                            "ID" => $user_id,
                            "first_name" => sanitize_text_field($_POST["first_name"]),
                            "last_name" => sanitize_text_field($_POST["last_name"]),
                            "display_name" => sanitize_text_field($_POST["first_name"]) . " " . sanitize_text_field($_POST["last_name"])
                        )
                    );
                    
                    // Kullanıcıya rol ata
                    $user = new WP_User($user_id);
                    $user->set_role("insurance_representative");
                    
                    // Müşteri temsilcisi kaydı oluştur
                    global $wpdb;
                    $table_name = $wpdb->prefix . "insurance_crm_representatives";
                    
                    $wpdb->insert(
                        $table_name,
                        array(
                            "user_id" => $user_id,
                            "title" => sanitize_text_field($_POST["title"]),
                            "phone" => sanitize_text_field($_POST["phone"]),
                            "department" => sanitize_text_field($_POST["department"]),
                            "monthly_target" => floatval($_POST["monthly_target"]),
                            "status" => "active",
                            "created_at" => current_time("mysql"),
                            "updated_at" => current_time("mysql")
                        )
                    );
                    
                    echo \'<div class="notice notice-success"><p>Müşteri temsilcisi başarıyla eklendi.</p></div>\';
                } else {
                    echo \'<div class="notice notice-error"><p>Kullanıcı oluşturulurken bir hata oluştu: \' . $user_id->get_error_message() . \'</p></div>\';
                }
            }
        } else {
            echo \'<div class="notice notice-error"><p>Gerekli alanlar doldurulmadı.</p></div>\';
        }
    }
}

// Mevcut temsilcileri listele
global $wpdb;
$table_name = $wpdb->prefix . "insurance_crm_representatives";
$representatives = $wpdb->get_results(
    "SELECT r.*, u.user_email as email, u.display_name 
     FROM $table_name r 
     LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
     WHERE r.status = \'active\' 
     ORDER BY r.created_at DESC"
);
?>

<div class="wrap">
    <h1>Müşteri Temsilcileri</h1>
    
    <!-- BURADA DEĞİŞİKLİK YAPILDI: LİSTELEME ÜSTE ALINDI -->
    <h2>Mevcut Müşteri Temsilcileri</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Ünvan</th>
                <th>Telefon</th>
                <th>Departman</th>
                <th>Aylık Hedef</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($representatives as $rep): ?>
            <tr>
                <td><?php echo esc_html($rep->display_name); ?></td>
                <td><?php echo esc_html($rep->email); ?></td>
                <td><?php echo esc_html($rep->title); ?></td>
                <td><?php echo esc_html($rep->phone); ?></td>
                <td><?php echo esc_html($rep->department); ?></td>
                <td>₺<?php echo number_format($rep->monthly_target, 2); ?></td>
                <td>
                    <!-- DÜZENLEME BUTONU EKLENDİ -->
                    <a href="<?php echo admin_url(\'admin.php?page=insurance-crm-representatives&edit=\' . $rep->id); ?>" 
                       class="button button-small">
                        Düzenle
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url(\'admin.php?page=insurance-crm-representatives&action=delete&id=\' . $rep->id), \'delete_representative_\' . $rep->id); ?>" 
                       class="button button-small" 
                       onclick="return confirm(\'Bu müşteri temsilcisini silmek istediğinizden emin misiniz?\');">
                        Sil
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <hr>
    
    <?php if ($editing): ?>
        <h2>Müşteri Temsilcisini Düzenle</h2>
    <?php else: ?>
        <h2>Yeni Müşteri Temsilcisi Ekle</h2>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field("add_edit_representative", "representative_nonce"); ?>
        <?php if ($editing): ?>
            <input type="hidden" name="rep_id" value="<?php echo $rep_id; ?>">
        <?php endif; ?>
        
        <table class="form-table">
            <?php if (!$editing): // Yeni kullanıcı için kullanıcı adı ve şifre alanları ?>
                <tr>
                    <th><label for="username">Kullanıcı Adı</label></th>
                    <td><input type="text" name="username" id="username" class="regular-text" required></td>
                </tr>
            <?php endif; ?>
                
            <tr>
                <th><label for="password">Şifre</label></th>
                <td>
                    <input type="password" name="password" id="password" class="regular-text" <?php echo !$editing ? "required" : ""; ?>>
                    <p class="description">
                        <?php echo $editing ? "Değiştirmek istemiyorsanız boş bırakın." : "En az 8 karakter uzunluğunda olmalıdır."; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="confirm_password">Şifre (Tekrar)</label></th>
                <td><input type="password" name="confirm_password" id="confirm_password" class="regular-text" <?php echo !$editing ? "required" : ""; ?>></td>
            </tr>
            <tr>
                <th><label for="first_name">Ad</label></th>
                <td>
                    <input type="text" name="first_name" id="first_name" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->first_name) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="last_name">Soyad</label></th>
                <td>
                    <input type="text" name="last_name" id="last_name" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->last_name) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="email">E-posta</label></th>
                <td>
                    <input type="email" name="email" id="email" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->email) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="title">Ünvan</label></th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->title) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="phone">Telefon</label></th>
                <td>
                    <input type="tel" name="phone" id="phone" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->phone) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="department">Departman</label></th>
                <td>
                    <input type="text" name="department" id="department" class="regular-text"
                           value="<?php echo $editing ? esc_attr($edit_rep->department) : ""; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="monthly_target">Aylık Hedef (₺)</label></th>
                <td>
                    <input type="number" step="0.01" name="monthly_target" id="monthly_target" class="regular-text" required
                           value="<?php echo $editing ? esc_attr($edit_rep->monthly_target) : ""; ?>">
                    <p class="description">Temsilcinin aylık satış hedefi (₺)</p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit_representative" class="button button-primary" 
                   value="<?php echo $editing ? "Temsilciyi Güncelle" : "Müşteri Temsilcisi Ekle"; ?>">
            <?php if ($editing): ?>
                <a href="<?php echo admin_url("admin.php?page=insurance-crm-representatives"); ?>" class="button">İptal</a>
            <?php endif; ?>
        </p>
    </form>
</div>';
    
    // Müşteri düzenleme sayfası için dosya oluştur
    file_put_contents($partials_dir . 'insurance-crm-customer-edit.php', $customer_edit_content);
    
    // Müşteri temsilcileri sayfası için dosya oluştur
    file_put_contents($partials_dir . 'insurance-crm-representatives.php', $representatives_content);
    
    // Login sayfasını güncelle
    $templates_dir = INSURANCE_CRM_PATH . 'templates/';
    if (!file_exists($templates_dir)) {
        mkdir($templates_dir, 0755, true);
    }
    
    $login_content = '<?php
if (!defined("ABSPATH")) {
    exit;
}

// Eğer kullanıcı zaten giriş yapmışsa ve müşteri temsilcisi ise panele yönlendir
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array("insurance_representative", (array)$user->roles)) {
        wp_safe_redirect(home_url("/temsilci-paneli/"));
        exit;
    }
}

// Login formundan gelen hataları kontrol et
$login_error = "";
if (isset($_GET["login"]) && $_GET["login"] === "failed") {
    $login_error = \'<div class="login-error">Kullanıcı adı veya şifre hatalı.</div>\';
}
if (isset($_GET["login"]) && $_GET["login"] === "inactive") {
    $login_error = \'<div class="login-error">Hesabınız pasif durumda. Lütfen yöneticiniz ile iletişime geçin.</div>\';
}
?>

<div class="insurance-crm-login-wrapper">
    <div class="insurance-crm-login-box">
        <div class="login-header">
            <?php 
            $company_settings = get_option("insurance_crm_settings");
            $company_name = !empty($company_settings["company_name"]) ? $company_settings["company_name"] : get_bloginfo("name");
            ?>
            <h2><?php echo esc_html($company_name); ?></h2>
            <div class="login-logo">
                <?php 
                $logo_url = !empty($company_settings["company_logo"]) ? $company_settings["company_logo"] : plugins_url("/assets/images/insurance-logo.png", dirname(__FILE__));
                ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?> Logo">
            </div>
            <h3>Müşteri Temsilcisi Girişi</h3>
        </div>
        
        <?php echo $login_error; ?>
        
        <form method="post" class="insurance-crm-login-form">
            <div class="form-group">
                <label for="username"><i class="dashicons dashicons-admin-users"></i></label>
                <input type="text" name="username" id="username" placeholder="Kullanıcı Adı" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="dashicons dashicons-lock"></i></label>
                <input type="password" name="password" id="password" placeholder="Şifre" required autocomplete="current-password">
            </div>

            <div class="form-remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Beni hatırla</label>
            </div>
            
            <div class="form-group">
                <input type="submit" name="insurance_crm_login" value="Giriş Yap" class="login-button">
            </div>
            
            <?php wp_nonce_field("insurance_crm_login", "insurance_crm_login_nonce"); ?>
        </form>
        
        <div class="login-footer">
            <p><?php echo date("Y"); ?> &copy; <?php echo esc_html($company_name); ?> - Sigorta CRM</p>
        </div>
    </div>
</div>

<style>
.insurance-crm-login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f7f9fc;
    padding: 20px;
}

.insurance-crm-login-box {
    width: 400px;
    max-width: 100%;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 30px;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 10px;
}

.login-header h3 {
    font-size: 18px;
    font-weight: 500;
    color: #7f8c8d;
    margin: 10px 0;
}

.login-logo {
    margin: 15px 0;
}

.login-logo img {
    max-height: 80px;
    max-width: 200px;
}

.insurance-crm-login-form .form-group {
    margin-bottom: 20px;
    position: relative;
}

.insurance-crm-login-form label {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
}

.insurance-crm-login-form input[type="text"],
.insurance-crm-login-form input[type="password"] {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.insurance-crm-login-form input[type="text"]:focus,
.insurance-crm-login-form input[type="password"]:focus {
    border-color: #2980b9;
    outline: none;
    box-shadow: 0 0 0 2px rgba(41, 128, 185, 0.2);
}

.form-remember {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.form-remember input {
    margin-right: 8px;
}

.login-button {
    width: 100%;
    background-color: #2980b9;
    color: white;
    border: none;
    padding: 12px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.login-button:hover {
    background-color: #3498db;
}

.login-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #f5c6cb;
}

.login-footer {
    text-align: center;
    margin-top: 30px;
    color: #7f8c8d;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 480px) {
    .insurance-crm-login-box {
        padding: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $(".insurance-crm-login-form").on("submit", function() {
        $(".login-button").prop("disabled", true).val("Giriş Yapılıyor...");
    });
});
</script>';
    
    file_put_contents($templates_dir . 'login.php', $login_content);
}

/**
 * Admin menüleri ekle - DOĞRUDAN ÇAĞRILACAK - GÜNCELLEME 1.0.3
 */
function insurance_crm_admin_menu() {
    // MENÜ SORUNUNU ÇÖZMEK İÇİN GÜNCELLENDİ
    // Eğer zaten menü oluşturulduysa tekrar çalıştırmayalım
    static $menu_initialized = false;
    
    if ($menu_initialized) {
        return;
    }
    
    $menu_initialized = true;
    
    // Ana menüyü ekle
    add_menu_page(
        'Insurance CRM',
        'Insurance CRM',
        'manage_options',
        'insurance-crm',
        'insurance_crm_dashboard',
        'dashicons-businessman',
        30
    );

    // Alt menüleri ekle
    add_submenu_page(
        'insurance-crm',
        'Gösterge Paneli',
        'Gösterge Paneli',
        'manage_options',
        'insurance-crm',
        'insurance_crm_dashboard'
    );

    add_submenu_page(
        'insurance-crm',
        'Müşteriler',
        'Müşteriler',
        'manage_options',
        'insurance-crm-customers',
        'insurance_crm_customers'
    );

    add_submenu_page(
        'insurance-crm',
        'Poliçeler',
        'Poliçeler',
        'manage_options',
        'insurance-crm-policies',
        'insurance_crm_policies'
    );

    add_submenu_page(
        'insurance-crm',
        'Görevler',
        'Görevler',
        'manage_options',
        'insurance-crm-tasks',
        'insurance_crm_tasks'
    );

    // MÜŞTERİ TEMSİLCİLERİ menüsü
    add_submenu_page(
        'insurance-crm',
        'Müşteri Temsilcileri',
        'Müşteri Temsilcileri',
        'manage_options',
        'insurance-crm-representatives',
        'insurance_crm_display_representatives_page'
    );

    add_submenu_page(
        'insurance-crm',
        'Raporlar',
        'Raporlar',
        'manage_options',
        'insurance-crm-reports',
        'insurance_crm_reports'
    );

    add_submenu_page(
        'insurance-crm',
        'Ayarlar',
        'Ayarlar',
        'manage_options',
        'insurance-crm-settings',
        'insurance_crm_settings'
    );
    
    // Menü oluşturuldu olarak işaretle
    update_option('insurance_crm_menu_initialized', 'yes');
}

// 1.0.3 sürümünde tek seferlik çalıştırma için özel hook
add_action('admin_menu', 'insurance_crm_admin_menu');

/**
 * 1.0.3 versiyonunda tekrarlanan menüleri temizle
 */
function insurance_crm_remove_duplicate_menus() {
    global $submenu, $menu;
    
    // Eğer admin menüsü yüklendiyse
    if (isset($submenu) && isset($menu)) {
        $insurance_crm_menu_positions = array();
        
        // Aynı menü öğesi için tüm pozisyonları bul
        foreach ($menu as $position => $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'insurance-crm') {
                $insurance_crm_menu_positions[] = $position;
            }
        }
        
        // Sadece ilk menü öğesini tut, diğerlerini kaldır
        if (count($insurance_crm_menu_positions) > 1) {
            $first_position = array_shift($insurance_crm_menu_positions);
            foreach ($insurance_crm_menu_positions as $position) {
                unset($menu[$position]);
            }
        }
        
        // Alt menü öğelerindeki tekrarları temizle
        if (isset($submenu['insurance-crm'])) {
            $seen_items = array();
            $new_submenu = array();
            
            foreach ($submenu['insurance-crm'] as $position => $item) {
                $menu_slug = $item[2];
                
                // Bu alt menü daha önce görülmemiş ise ekle
                if (!isset($seen_items[$menu_slug])) {
                    $seen_items[$menu_slug] = true;
                    $new_submenu[$position] = $item;
                }
            }
            
            // Yeni altmenü listesini ayarla
            if (!empty($new_submenu)) {
                $submenu['insurance-crm'] = $new_submenu;
            }
        }
    }
}
add_action('admin_menu', 'insurance_crm_remove_duplicate_menus', 9999);

/**
 * Admin sayfaları için callback fonksiyonları
 */
function insurance_crm_dashboard() {
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-dashboard.php';
}

/**
 * 1.0.3 versiyonunda güncellenen müşteriler sayfası
 */
function insurance_crm_customers() {
    // Müşteri düzenleme sayfasını kontrol et
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        if (file_exists(INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php')) {
            require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php';
        } else {
            require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-customers.php';
        }
    } else {
        require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-customers.php';
    }
}

function insurance_crm_policies() {
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-policies.php';
}

function insurance_crm_tasks() {
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-tasks.php';
}

function insurance_crm_reports() {
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-reports.php';
}

function insurance_crm_settings() {
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-settings.php';
}

/**
 * Admin script ve stilleri ekle
 */
function insurance_crm_admin_scripts() {
    wp_enqueue_style('insurance-crm-admin-style', INSURANCE_CRM_URL . 'admin/css/insurance-crm-admin.css', array(), INSURANCE_CRM_VERSION, 'all');
    wp_enqueue_script('insurance-crm-admin-script', INSURANCE_CRM_URL . 'admin/js/insurance-crm-admin.js', array('jquery'), INSURANCE_CRM_VERSION, false);
}
add_action('admin_enqueue_scripts', 'insurance_crm_admin_scripts');

/**
 * Public script ve stilleri ekle
 */
function insurance_crm_public_scripts() {
    wp_enqueue_style('insurance-crm-public-style', INSURANCE_CRM_URL . 'public/css/insurance-crm-public.css', array(), INSURANCE_CRM_VERSION, 'all');
    wp_enqueue_script('insurance-crm-public-script', INSURANCE_CRM_URL . 'public/js/insurance-crm-public.js', array('jquery'), INSURANCE_CRM_VERSION, false);
    
    // Dashicons ekleme
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'insurance_crm_public_scripts');

/**
 * AJAX işlemleri için hooks
 */
function insurance_crm_ajax_handler() {
    // Ajax işlemlerinin yönetimi burada
    wp_die();
}

add_action('wp_ajax_insurance_crm_ajax', 'insurance_crm_ajax_handler');

/**
 * Müşteri Temsilcileri sayfasını görüntüle
 */
function insurance_crm_display_representatives_page() {
    // 1.0.3 versiyonunda güncellenen temsilciler sayfası
    if (file_exists(INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-representatives.php')) {
        require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-representatives.php';
    } else {
        if (!current_user_can('manage_insurance_crm')) {
            wp_die(__('Bu sayfaya erişim izniniz yok.'));
        }

        // Temsilci ekleme/düzenleme formu işleme
        if (isset($_POST['submit_representative']) && isset($_POST['representative_nonce']) && 
            wp_verify_nonce($_POST['representative_nonce'], 'add_edit_representative')) {
            
            $rep_data = array(
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'title' => sanitize_text_field($_POST['title']),
                'phone' => sanitize_text_field($_POST['phone']),
                'department' => sanitize_text_field($_POST['department']),
                'monthly_target' => floatval($_POST['monthly_target'])
            );

            global $wpdb;
            $table_name = $wpdb->prefix . 'insurance_crm_representatives';

            if (isset($_POST['rep_id']) && !empty($_POST['rep_id'])) {
                // Güncelleme
                $wpdb->update(
                    $table_name,
                    array(
                        'title' => $rep_data['title'],
                        'phone' => $rep_data['phone'],
                        'department' => $rep_data['department'],
                        'monthly_target' => $rep_data['monthly_target'],
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => intval($_POST['rep_id']))
                );
                echo '<div class="notice notice-success"><p>Müşteri temsilcisi güncellendi.</p></div>';
            } else {
                // Yeni kullanıcı oluştur
                if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
                    $username = sanitize_user($_POST['username']);
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if (empty($username) || empty($password) || empty($confirm_password)) {
                        echo '<div class="notice notice-error"><p>Kullanıcı adı ve şifre alanlarını doldurunuz.</p></div>';
                    } else if ($password !== $confirm_password) {
                        echo '<div class="notice notice-error"><p>Şifreler eşleşmiyor.</p></div>';
                    } else if (username_exists($username)) {
                        echo '<div class="notice notice-error"><p>Bu kullanıcı adı zaten kullanımda.</p></div>';
                    } else if (email_exists($rep_data['email'])) {
                        echo '<div class="notice notice-error"><p>Bu e-posta adresi zaten kullanımda.</p></div>';
                    } else {
                        // Kullanıcı oluştur
                        $user_id = wp_create_user($username, $password, $rep_data['email']);
                        
                        if (!is_wp_error($user_id)) {
                            // Kullanıcı detaylarını güncelle
                            wp_update_user(
                                array(
                                    'ID' => $user_id,
                                    'first_name' => $rep_data['first_name'],
                                    'last_name' => $rep_data['last_name'],
                                    'display_name' => $rep_data['first_name'] . ' ' . $rep_data['last_name']
                                )
                            );
                            
                            // Kullanıcıya rol ata
                            $user = new WP_User($user_id);
                            $user->set_role('insurance_representative');
                            
                            // Müşteri temsilcisi kaydı oluştur
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'user_id' => $user_id,
                                    'title' => $rep_data['title'],
                                    'phone' => $rep_data['phone'],
                                    'department' => $rep_data['department'],
                                    'monthly_target' => $rep_data['monthly_target'],
                                    'status' => 'active',
                                    'created_at' => current_time('mysql'),
                                    'updated_at' => current_time('mysql')
                                )
                            );
                            
                            echo '<div class="notice notice-success"><p>Müşteri temsilcisi başarıyla eklendi.</p></div>';
                        } else {
                            echo '<div class="notice notice-error"><p>Kullanıcı oluşturulurken bir hata oluştu: ' . $user_id->get_error_message() . '</p></div>';
                        }
                    }
                } else {
                    echo '<div class="notice notice-error"><p>Gerekli alanlar doldurulmadı.</p></div>';
                }
            }
        }

        // Silme işlemi
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_representative_' . $_GET['id'])) {
                global $wpdb;
                $rep_id = intval($_GET['id']);
                $table_name = $wpdb->prefix . 'insurance_crm_representatives';
                
                // Önce kullanıcıyı bul ve sil
                $user_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT user_id FROM $table_name WHERE id = %d",
                    $rep_id
                ));
                
                if ($user_id) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                    wp_delete_user($user_id);
                }
                
                // Sonra temsilci kaydını sil
                $wpdb->delete($table_name, array('id' => $rep_id));
                
                echo '<div class="notice notice-success"><p>Müşteri temsilcisi silindi.</p></div>';
            }
        }

        // Mevcut temsilcileri listele
        global $wpdb;
        $table_name = $wpdb->prefix . 'insurance_crm_representatives';
        $representatives = $wpdb->get_results(
            "SELECT r.*, u.user_email as email, u.display_name 
             FROM $table_name r 
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
             WHERE r.status = 'active' 
             ORDER BY r.created_at DESC"
        );
        ?>
        <div class="wrap">
            <h1>Müşteri Temsilcileri</h1>
            
            <!-- MEVSİMİ TEMSİLCİLERİ LİSTELEME KISMI GÜNCELLEME İLE BU KISIM KAPATILMIŞTIR -->
            <h2>Mevcut Müşteri Temsilcileri</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Ünvan</th>
                        <th>Telefon</th>
                        <th>Departman</th>
                        <th>Aylık Hedef</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($representatives as $rep): ?>
                    <tr>
                        <td><?php echo esc_html($rep->display_name); ?></td>
                        <td><?php echo esc_html($rep->email); ?></td>
                        <td><?php echo esc_html($rep->title); ?></td>
                        <td><?php echo esc_html($rep->phone); ?></td>
                        <td><?php echo esc_html($rep->department); ?></td>
                        <td>₺<?php echo number_format($rep->monthly_target, 2); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=insurance-crm-representatives&action=edit&id=' . $rep->id); ?>" 
                               class="button button-small">
                                Düzenle
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=insurance-crm-representatives&action=delete&id=' . $rep->id), 'delete_representative_' . $rep->id); ?>" 
                               class="button button-small" 
                               onclick="return confirm('Bu müşteri temsilcisini silmek istediğinizden emin misiniz?');">
                                Sil
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <hr>
            
            <h2>Yeni Müşteri Temsilcisi Ekle</h2>
            <form method="post" action="">
                <?php wp_nonce_field('add_edit_representative', 'representative_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="username">Kullanıcı Adı</label></th>
                        <td><input type="text" name="username" id="username" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="password">Şifre</label></th>
                        <td>
                            <input type="password" name="password" id="password" class="regular-text" required>
                            <p class="description">En az 8 karakter uzunluğunda olmalıdır.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="confirm_password">Şifre (Tekrar)</label></th>
                        <td><input type="password" name="confirm_password" id="confirm_password" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="first_name">Ad</label></th>
                        <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Soyad</label></th>
                        <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="email">E-posta</label></th>
                        <td><input type="email" name="email" id="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="title">Ünvan</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Telefon</label></th>
                        <td><input type="tel" name="phone" id="phone" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="department">Departman</label></th>
                        <td><input type="text" name="department" id="department" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="monthly_target">Aylık Hedef (₺)</label></th>
                        <td>
                            <input type="number" step="0.01" name="monthly_target" id="monthly_target" class="regular-text" required>
                            <p class="description">Temsilcinin aylık satış hedefi (₺)</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_representative" class="button button-primary" value="Müşteri Temsilcisi Ekle">
                </p>
            </form>
        </div>
        <?php
    }
}

/**
 * Sayfa şablonlarını kaydet
 */
function insurance_crm_create_pages() {
    // Login sayfası
    $login_page = array(
        'post_title'    => 'Temsilci Girişi',
        'post_content'  => '[insurance_crm_login]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'temsilci-girisi'
    );
    
    // Panel sayfası
    $panel_page = array(
        'post_title'    => 'Temsilci Paneli',
        'post_content'  => '[insurance_crm_panel]',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'temsilci-paneli'
    );

    // Sayfaları oluştur
    if (!get_page_by_path('temsilci-girisi')) {
        wp_insert_post($login_page);
    }
    if (!get_page_by_path('temsilci-paneli')) {
        wp_insert_post($panel_page);
    }
}
register_activation_hook(__FILE__, 'insurance_crm_create_pages');

/**
 * Shortcode'ları ekle
 */
function insurance_crm_add_shortcodes() {
    add_shortcode('insurance_crm_login', 'insurance_crm_login_shortcode');
    add_shortcode('insurance_crm_panel', 'insurance_crm_panel_shortcode');
}
add_action('init', 'insurance_crm_add_shortcodes');

/**
 * Login sayfası shortcode
 */
function insurance_crm_login_shortcode() {
    // Eğer kullanıcı zaten giriş yapmış ve müşteri temsilcisi ise panele yönlendir
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('insurance_representative', (array)$user->roles)) {
            wp_safe_redirect(home_url('/temsilci-paneli/'));
            exit;
        }
    }

    // Login template'i yükle
    ob_start();
    if (file_exists(plugin_dir_path(__FILE__) . 'templates/login.php')) {
        include plugin_dir_path(__FILE__) . 'templates/login.php';
    } else {
        echo 'Login template bulunamadı.';
    }
    return ob_get_clean();
}

/**
 * Panel sayfası shortcode
 */
function insurance_crm_panel_shortcode() {
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

    // Panel template'i yükle
    ob_start();
    if (file_exists(plugin_dir_path(__FILE__) . 'templates/panel.php')) {
        include plugin_dir_path(__FILE__) . 'templates/panel.php';
    } else {
        echo 'Panel template bulunamadı.';
    }
    return ob_get_clean();
}


/**
 * Login işlemini yönet - GÜNCELLENDİ 1.0.4 için
 */
function insurance_crm_process_login() {
    if (isset($_POST['insurance_crm_login']) && isset($_POST['insurance_crm_login_nonce'])) {
        if (!wp_verify_nonce($_POST['insurance_crm_login_nonce'], 'insurance_crm_login')) {
            wp_safe_redirect(add_query_arg('login', 'failed', home_url('/temsilci-girisi/')));
            exit;
        }
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        // Kullanıcı adı veya e-posta ile giriş yapabilme
        if (is_email($username)) {
            $user_data = get_user_by('email', $username);
            if ($user_data) {
                $username = $user_data->user_login;
            }
        }
        
        $creds = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );
        
        // WordPress giriş kontrolü
        $user = wp_signon($creds, is_ssl());
        
        if (is_wp_error($user)) {
            // Hata logla ve kullanıcıya bildir
            error_log('Insurance CRM Login Error: ' . $user->get_error_message());
            wp_safe_redirect(add_query_arg('login', 'failed', home_url('/temsilci-girisi/')));
            exit;
        }
        
        // Müşteri temsilcisi rolü kontrolü
        if (!in_array('insurance_representative', (array)$user->roles)) {
            wp_logout();
            error_log('Insurance CRM Login Error: User is not a representative');
            wp_safe_redirect(add_query_arg('login', 'failed', home_url('/temsilci-girisi/')));
            exit;
        }
        
        // Hesap aktif mi kontrolü
        global $wpdb;
        $rep_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}insurance_crm_representatives WHERE user_id = %d",
            $user->ID
        ));
        
        if ($rep_status !== 'active') {
            wp_logout();
            error_log('Insurance CRM Login Error: Representative status is not active');
            wp_safe_redirect(add_query_arg('login', 'inactive', home_url('/temsilci-girisi/')));
            exit;
        }
        
        // Giriş başarılı, yönlendirme
        wp_safe_redirect(home_url('/temsilci-paneli/'));
        exit;
    }
}
add_action('init', 'insurance_crm_process_login', 5); // Daha erken çalışması için önceliği 5'e çektik




// Admin notifikasyonu ekle
function insurance_crm_admin_notice() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'insurance-crm') !== false) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Insurance CRM v1.0.3:</strong> Müşteri Temsilcisi menüsü düzenlendi ve hata düzeltmeleri yapıldı.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'insurance_crm_admin_notice');