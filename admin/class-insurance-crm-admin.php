<?php
/**
 * Admin tarafı işlevleri ve ayarları
 *
 * @link       https://github.com/anadolubirlik/Insurance_CRM
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin
 */

/**
 * Admin tarafı işlevleri ve ayarları
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin
 * @author     Anadolu Birlik
 */
class Insurance_CRM_Admin {

    /**
     * Plugin adı
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    Plugin adı
     */
    private $plugin_name;

    /**
     * Plugin versiyon
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    Plugin versiyon numarası
     */
    private $version;

    /**
     * Sınıfı başlat ve özellikleri tanımla
     *
     * @since    1.0.0
     * @param    string    $plugin_name       Plugin adı
     * @param    string    $version           Plugin versiyon numarası
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Admin stil dosyalarını kaydet
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/insurance-crm-admin.css', array(), $this->version, 'all');
    }

    /**
     * Admin JS dosyalarını kaydet
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/insurance-crm-admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Dashboard sayfası için callback fonksiyonu
     */
    public function dashboard_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-dashboard.php';
    }

    /**
     * Müşteriler sayfası için callback fonksiyonu
     */
    public function customers_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-customers.php';
    }

    /**
     * Müşteri detayları sayfası için callback fonksiyonu
     */
    public function customer_details_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-customer-details.php';
    }

    /**
     * Poliçeler sayfası için callback fonksiyonu
     */
    public function policies_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-policies.php';
    }

    /**
     * Görevler sayfası için callback fonksiyonu
     */
    public function tasks_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-tasks.php';
    }

    /**
     * Müşteri temsilcileri sayfası için callback fonksiyonu
     */
    public function representatives_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-representatives.php';
    }

    /**
     * Admin menüleri kaydet
     */
    public function register_admin_menus() {
        // Ana menü
        add_menu_page(
            'Insurance CRM',            // Sayfa başlığı
            'Insurance CRM',            // Menü adı
            'manage_options',           // Yetki
            'insurance-crm',            // Menü slug
            array($this, 'dashboard_page'), // Callback fonksiyonu
            'dashicons-businessman',    // Icon
            30                          // Pozisyon
        );

        // Dashboard alt menü
        add_submenu_page(
            'insurance-crm',             // Parent slug
            'Gösterge Paneli',           // Sayfa başlığı
            'Gösterge Paneli',           // Menü başlığı
            'manage_options',            // Yetki
            'insurance-crm',             // Menü slug
            array($this, 'dashboard_page') // Callback fonksiyonu
        );

        // Müşteriler alt menü
        add_submenu_page(
            'insurance-crm',            // Parent slug
            'Müşteriler',               // Sayfa başlığı
            'Müşteriler',               // Menü başlığı
            'manage_options',           // Yetki
            'insurance-crm-customers',  // Menü slug
            array($this, 'customers_page') // Callback fonksiyonu
        );

        // Poliçeler alt menü
        add_submenu_page(
            'insurance-crm',            // Parent slug
            'Poliçeler',                // Sayfa başlığı
            'Poliçeler',                // Menü başlığı
            'manage_options',           // Yetki
            'insurance-crm-policies',   // Menü slug
            array($this, 'policies_page') // Callback fonksiyonu
        );

        // Görevler alt menü
        add_submenu_page(
            'insurance-crm',            // Parent slug
            'Görevler',                 // Sayfa başlığı
            'Görevler',                 // Menü başlığı
            'manage_options',           // Yetki
            'insurance-crm-tasks',      // Menü slug
            array($this, 'tasks_page')  // Callback fonksiyonu
        );
        
        // Müşteri Temsilcileri alt menü
        add_submenu_page(
            'insurance-crm',                 // Parent slug
            'Müşteri Temsilcileri',          // Sayfa başlığı
            'Müşteri Temsilcileri',          // Menü başlığı
            'manage_options',                // Yetki
            'insurance-crm-representatives', // Menü slug
            array($this, 'representatives_page') // Callback fonksiyonu
        );
        
        // Müşteri Detayları sayfası (görünmez menü)
        add_submenu_page(
            null,                          // Görünmez menü için parent slug null
            'Müşteri Detayları',           // Sayfa başlığı
            'Müşteri Detayları',           // Menü başlığı (görünmeyecek)
            'manage_options',              // Yetki
            'insurance-crm-customer-details', // Menü slug
            array($this, 'customer_details_page') // Callback fonksiyonu
        );
    }

}