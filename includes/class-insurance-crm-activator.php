<?php
/**
 * Eklenti aktivasyon işlemleri
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 * @author     Anadolu Birlik
 * @since      1.0.0
 */

class Insurance_CRM_Activator {
    /**
     * Eklenti aktivasyon işlemlerini gerçekleştirir
     */
    public static function activate() {
        global $wpdb;

        // Veritabanı karakter seti
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

        // Varsayılan ayarları oluştur
        $default_settings = array(
            'company_name' => get_bloginfo('name'),
            'company_email' => get_bloginfo('admin_email'),
            'company_logo' => '',
            'renewal_reminder_days' => 30,
            'task_reminder_days' => 1,
            'default_policy_types' => array(
                'trafik',
                'kasko',
                'konut',
                'dask',
                'saglik',
                'hayat'
            ),
            'default_task_types' => array(
                'renewal',
                'payment',
                'document',
                'meeting',
                'other'
            )
        );
        
        add_option('insurance_crm_settings', $default_settings);

        // Yetkiler tanımla
        $role = get_role('administrator');
        $capabilities = array(
            'read_insurance_crm',
            'edit_insurance_crm',
            'edit_others_insurance_crm',
            'publish_insurance_crm',
            'read_private_insurance_crm',
            'manage_insurance_crm'
        );

        foreach ($capabilities as $cap) {
            $role->add_cap($cap);
        }
        
        // Müşteri Temsilcisi rolünü oluştur ve yetkilendir
        if (!get_role('insurance_representative')) {
            add_role('insurance_representative', 'Müşteri Temsilcisi', array(
                'read' => true,
                'upload_files' => true,
                'read_insurance_crm' => true,
                'edit_insurance_crm' => true,
                'publish_insurance_crm' => true
            ));
        }

        // Aktivasyon zamanını kaydet
        add_option('insurance_crm_activation_time', time());

        // Aktivasyon hook'unu çalıştır
        do_action('insurance_crm_activated');

        // Yönlendirme flag'ini ayarla
        set_transient('insurance_crm_activation_redirect', true, 30);
        
        // Örnek veri ekle
        self::add_sample_data();
    }
    
    /**
     * Örnek veri ekle
     */
    private static function add_sample_data() {
        // Etkinleştirme sonrası örnek veri ekleme kontrolü
        if (get_option('insurance_crm_sample_data_added')) {
            return;
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
                
                // Temsilci bilgilerini güncelle
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => 'Ahmet',
                    'last_name' => 'Yılmaz',
                    'display_name' => 'Ahmet Yılmaz'
                ));
                
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
                        'status' => 'active',
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    )
                );
                
                $rep_id = $wpdb->insert_id;
                
                // Örnek müşteriler ekle
                $customers = array(
                    array(
                        'first_name' => 'Mehmet',
                        'last_name' => 'Özdemir',
                        'tc_identity' => '12345678901',
                        'email' => 'mehmet@example.com',
                        'phone' => '5551112233',
                        'address' => 'Atatürk Cad. No:123 İstanbul',
                        'category' => 'bireysel',
                        'status' => 'aktif',
                        'representative_id' => $rep_id
                    ),
                    array(
                        'first_name' => 'Ayşe',
                        'last_name' => 'Kaya',
                        'tc_identity' => '98765432109',
                        'email' => 'ayse@example.com',
                        'phone' => '5552223344',
                        'address' => 'Cumhuriyet Mah. 1453 Sok. No:7 Ankara',
                        'category' => 'bireysel',
                        'status' => 'aktif',
                        'representative_id' => $rep_id
                    )
                );
                
                $customer_ids = array();
                foreach ($customers as $customer) {
                    $wpdb->insert(
                        $wpdb->prefix . 'insurance_crm_customers',
                        $customer
                    );
                    $customer_ids[] = $wpdb->insert_id;
                }
                
                // Örnek poliçeler ekle
                $policies = array(
                    array(
                        'customer_id' => $customer_ids[0],
                        'policy_number' => 'TRF-2025-0001',
                        'policy_type' => 'trafik',
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+1 year')),
                        'premium_amount' => 1500.00,
                        'status' => 'aktif',
                        'representative_id' => $rep_id
                    ),
                    array(
                        'customer_id' => $customer_ids[0],
                        'policy_number' => 'KASKO-2025-0001',
                        'policy_type' => 'kasko',
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+1 year')),
                        'premium_amount' => 3500.00,
                        'status' => 'aktif',
                        'representative_id' => $rep_id
                    ),
                    array(
                        'customer_id' => $customer_ids[1],
                        'policy_number' => 'KONUT-2025-0001',
                        'policy_type' => 'konut',
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d', strtotime('+1 year')),
                        'premium_amount' => 750.00,
                        'status' => 'aktif',
                        'representative_id' => $rep_id
                    )
                );
                
                $policy_ids = array();
                foreach ($policies as $policy) {
                    $wpdb->insert(
                        $wpdb->prefix . 'insurance_crm_policies',
                        $policy
                    );
                    $policy_ids[] = $wpdb->insert_id;
                }
                
                // Örnek görevler ekle
                $tasks = array(
                    array(
                        'customer_id' => $customer_ids[0],
                        'policy_id' => $policy_ids[0],
                        'task_description' => 'Müşteri ile yenileme görüşmesi yapılacak',
                        'due_date' => date('Y-m-d H:i:s', strtotime('+15 days')),
                        'priority' => 'medium',
                        'status' => 'pending',
                        'representative_id' => $rep_id
                    ),
                    array(
                        'customer_id' => $customer_ids[1],
                        'policy_id' => $policy_ids[2],
                        'task_description' => 'Konut poliçesi ek teminat görüşmesi',
                        'due_date' => date('Y-m-d H:i:s', strtotime('+3 days')),
                        'priority' => 'high',
                        'status' => 'pending',
                        'representative_id' => $rep_id
                    )
                );
                
                foreach ($tasks as $task) {
                    $wpdb->insert(
                        $wpdb->prefix . 'insurance_crm_tasks',
                        $task
                    );
                }
            }
        }
        
        // Örnek veri eklendi olarak işaretle
        update_option('insurance_crm_sample_data_added', true);
    }
    
    /**
     * Deactivation işlemleri
     */
    public static function deactivate() {
        // Cron job kaldır
        wp_clear_scheduled_hook('insurance_crm_daily_cron');
        
        // Deactivation işlemlerini yap
        do_action('insurance_crm_deactivated');
    }
    
    /**
     * Uninstall işlemleri - dikkat bu sadece uninstall.php dosyasından çağrılmalı
     */
    public static function uninstall() {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            exit;
        }
        
        global $wpdb;
        
        // Ayarları sil
        delete_option('insurance_crm_settings');
        delete_option('insurance_crm_version');
        delete_option('insurance_crm_activation_time');
        delete_option('insurance_crm_sample_data_added');
        
        // Tablolar siliniyor mu kontrol et (eğer ayarlarda belirtilmişse)
        $delete_data = get_option('insurance_crm_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            // Tabloları sil
            $tables = array(
                $wpdb->prefix . 'insurance_crm_customers',
                $wpdb->prefix . 'insurance_crm_policies',
                $wpdb->prefix . 'insurance_crm_tasks',
                $wpdb->prefix . 'insurance_crm_representatives'
            );
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS $table");
            }
            
            // Aktivite loglarını sil
            $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'insurance_crm_log'");
            
            // Post meta'ları temizle
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_insurance_crm_%'");
            
            // User meta'ları temizle
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_insurance_representative_%'");
            
            // Rolü sil
            remove_role('insurance_representative');
        }
        
        // Plugin sayfalarını sil
        $pages = array(
            'temsilci-girisi',
            'temsilci-paneli'
        );
        
        foreach ($pages as $page_slug) {
            $page = get_page_by_path($page_slug);
            if ($page) {
                wp_delete_post($page->ID, true);
            }
        }
        
        // Uninstall işlemlerini yap
        do_action('insurance_crm_uninstalled');
    }
}