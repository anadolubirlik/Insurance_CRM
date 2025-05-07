<?php
/**
 * Eklenti deaktivasyon işlemleri
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 * @author     Anadolu Birlik
 * @since      1.0.0
 */

class Insurance_CRM_Deactivator {
    /**
     * Eklenti deaktivasyon işlemlerini gerçekleştirir
     */
    public static function deactivate() {
        // Zamanlanmış görevleri temizle
        wp_clear_scheduled_hook('insurance_crm_daily_cron');

        // Geçici verileri temizle
        delete_transient('insurance_crm_cache');
        delete_transient('insurance_crm_activation_redirect');

        // Deaktivasyon hook'unu çalıştır
        do_action('insurance_crm_deactivated');

        // Deaktivasyon zamanını kaydet
        update_option('insurance_crm_deactivation_time', time());
    }

    /**
     * Eklentiyi tamamen kaldırır (uninstall)
     */
    public static function uninstall() {
        global $wpdb;

        // Veritabanı tablolarını sil
        $tables = array(
            $wpdb->prefix . 'insurance_crm_customers',
            $wpdb->prefix . 'insurance_crm_policies',
            $wpdb->prefix . 'insurance_crm_tasks'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Ayarları sil
        delete_option('insurance_crm_settings');
        delete_option('insurance_crm_activation_time');
        delete_option('insurance_crm_deactivation_time');

        // Yetkileri kaldır
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
            $role->remove_cap($cap);
        }

        // Yüklenen dosyaları temizle
        $upload_dir = wp_upload_dir();
        $insurance_crm_dir = $upload_dir['basedir'] . '/insurance-crm';
        
        if (is_dir($insurance_crm_dir)) {
            self::delete_directory($insurance_crm_dir);
        }

        // Uninstall hook'unu çalıştır
        do_action('insurance_crm_uninstalled');
    }

    /**
     * Dizin ve içeriğini recursive olarak siler
     *
     * @param string $dir Silinecek dizin
     * @return bool
     */
    private static function delete_directory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}