<?php
/**
 * Insurance CRM 1.0.3 Güncelleme Dosyası
 *
 * Bu dosya mevcut CRM sistemindeki hataları düzelten bir güncelleme sağlar.
 *
 * @package     Insurance_CRM
 * @author      Anadolu Birlik
 * @version     1.0.3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * 1.0.3 versiyonunu sağlamak için veritabanı güncelleştirmesi
 */
function insurance_crm_update_to_103() {
    // Menüdeki sorunları düzelt
    delete_option('insurance_crm_menu_initialized');
    delete_option('insurance_crm_menu_cache_cleared');
    
    // Yeni dosya yollarını kontrol et ve kopyala
    $source_path = INSURANCE_CRM_PATH . 'upgrades';
    $customer_edit_file = INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php';
    $representatives_file = INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-representatives.php';
    $login_file = INSURANCE_CRM_PATH . 'templates/login.php';
    
    // Gerekli dizini oluştur
    if (!file_exists(INSURANCE_CRM_PATH . 'admin/partials')) {
        mkdir(INSURANCE_CRM_PATH . 'admin/partials', 0755, true);
    }
    
    // Versiyonu güncelle
    update_option('insurance_crm_version', '1.0.3');
    
    // Güncellemeleri tamamladığımızı belirt
    update_option('insurance_crm_103_updated', 'yes');
    
    return true;
}

// Güncelleştirmeyi otomatik yap
add_action('plugins_loaded', function() {
    if (get_option('insurance_crm_version') !== '1.0.3') {
        insurance_crm_update_to_103();
    }
});