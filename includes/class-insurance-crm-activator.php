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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY tc_identity (tc_identity),
            KEY email (email),
            KEY status (status)
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY policy_number (policy_number),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY end_date (end_date)
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY policy_id (policy_id),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_customers);
        dbDelta($sql_policies);
        dbDelta($sql_tasks);

        // Varsayılan ayarları oluştur
        $default_settings = array(
            'company_name' => get_bloginfo('name'),
            'company_email' => get_bloginfo('admin_email'),
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

        // Aktivasyon zamanını kaydet
        add_option('insurance_crm_activation_time', time());

        // Aktivasyon hook'unu çalıştır
        do_action('insurance_crm_activated');

        // Yönlendirme flag'ini ayarla
        set_transient('insurance_crm_activation_redirect', true, 30);
    }
}