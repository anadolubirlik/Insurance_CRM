<?php
/**
 * Yardımcı fonksiyonlar
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Müşteri sayfası
 */
function insurance_crm_customers() {
    // Müşteri ayrıntıları sayfası
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
        if (file_exists(INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-view.php')) {
            require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-view.php';
            return;
        }
    }
    
    // Müşteri düzenleme sayfası
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        if (file_exists(INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php')) {
            require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php';
            return;
        }
    }
    
    // Yeni müşteri ekleme
    if (isset($_GET['action']) && $_GET['action'] === 'new') {
        if (file_exists(INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php')) {
            require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-customer-edit.php';
            return;
        }
    }
    
    // Varsayılan müşteri listesi
    require_once INSURANCE_CRM_PATH . 'admin/partials/insurance-crm-admin-customers.php';
}

/**
 * Müşteriyi görüntülemek için yönlendirme fonksiyonu
 */
function insurance_crm_redirect_customer_links() {
    if (!is_admin() || !current_user_can('read_insurance_crm')) {
        return;
    }
    
    // URL'de müşteri ismi geçiyorsa ayrıntılar sayfasına yönlendir
    if (isset($_GET['page']) && $_GET['page'] === 'insurance-crm-customers') {
        if (isset($_GET['customer_name']) && isset($_GET['id'])) {
            $customer_id = intval($_GET['id']);
            wp_redirect(admin_url('admin.php?page=insurance-crm-customers&action=view&id=' . $customer_id));
            exit;
        }
    }
    
    // Poliçe sayfasından müşteri adına tıklandığında
    if (isset($_GET['page']) && $_GET['page'] === 'insurance-crm-policies' && isset($_GET['view_customer']) && isset($_GET['customer_id'])) {
        $customer_id = intval($_GET['customer_id']);
        wp_redirect(admin_url('admin.php?page=insurance-crm-customers&action=view&id=' . $customer_id));
        exit;
    }
}
add_action('admin_init', 'insurance_crm_redirect_customer_links');