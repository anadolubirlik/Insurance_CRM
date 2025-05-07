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
 * Version:     1.0.0
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
define('INSURANCE_CRM_VERSION', '1.0.0');

/**
 * Plugin activation.
 */
function activate_insurance_crm() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-insurance-crm-activator.php';
    Insurance_CRM_Activator::activate();
}

/**
 * Plugin deactivation.
 */
function deactivate_insurance_crm() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-insurance-crm-deactivator.php';
    Insurance_CRM_Deactivator::deactivate();
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

/**
 * Run the plugin
 */
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