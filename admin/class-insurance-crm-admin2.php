<?php

/**
 * Admin işlevselliği için sınıf
 */

if (!class_exists('Insurance_CRM_Admin')) {
    class Insurance_CRM_Admin {
        /**
         * The ID of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string    $plugin_name    The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string    $version    The current version of this plugin.
         */
        private $version;

        /**
         * Initialize the class and set its properties.
         *
         * @since    1.0.0
         * @param    string    $plugin_name    The name of this plugin.
         * @param    string    $version        The version of this plugin.
         */
        public function __construct($plugin_name, $version) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;

            add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }

        /**
         * Register the stylesheets for the admin area.
         *
         * @since    1.0.0
         */
        public function enqueue_styles() {
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'css/insurance-crm-admin.css',
                array(),
                $this->version,
                'all'
            );
        }

        /**
         * Register the JavaScript for the admin area.
         *
         * @since    1.0.0
         */
        public function enqueue_scripts() {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/insurance-crm-admin.js',
                array('jquery'),
                $this->version,
                false
            );
        }

        /**
         * Add menu items
         */
        public function add_plugin_admin_menu() {
            add_menu_page(
                'Insurance CRM',
                'Insurance CRM',
                'manage_insurance_crm',
                'insurance-crm',
                array($this, 'display_plugin_setup_page'),
                'dashicons-businessman',
                6
            );

            add_submenu_page(
                'insurance-crm',
                'Müşteriler',
                'Müşteriler',
                'manage_insurance_crm',
                'insurance-crm-customers',
                array($this, 'display_customers_page')
            );

            add_submenu_page(
                'insurance-crm',
                'Poliçeler',
                'Poliçeler',
                'manage_insurance_crm',
                'insurance-crm-policies',
                array($this, 'display_policies_page')
            );

            add_submenu_page(
                'insurance-crm',
                'Görevler',
                'Görevler',
                'manage_insurance_crm',
                'insurance-crm-tasks',
                array($this, 'display_tasks_page')
            );

            add_submenu_page(
                'insurance-crm',
                'Raporlar',
                'Raporlar',
                'manage_insurance_crm',
                'insurance-crm-reports',
                array($this, 'display_reports_page')
            );

            add_submenu_page(
                'insurance-crm',
                'Ayarlar',
                'Ayarlar',
                'manage_insurance_crm',
                'insurance-crm-settings',
                array($this, 'display_settings_page')
            );
        }

        /**
         * Ana sayfa görüntüleme
         */
        public function display_plugin_setup_page() {
            include_once('partials/insurance-crm-admin-display.php');
        }

        /**
         * Müşteriler sayfası görüntüleme
         */
        public function display_customers_page() {
            include_once('partials/insurance-crm-admin-customers.php');
        }

        /**
         * Poliçeler sayfası görüntüleme
         */
        public function display_policies_page() {
            include_once('partials/insurance-crm-admin-policies.php');
        }

        /**
         * Görevler sayfası görüntüleme
         */
        public function display_tasks_page() {
            include_once('partials/insurance-crm-admin-tasks.php');
        }

        /**
         * Raporlar sayfası görüntüleme
         */
        public function display_reports_page() {
            include_once('partials/insurance-crm-admin-reports.php');
        }

        /**
         * Ayarlar sayfası görüntüleme
         */
        public function display_settings_page() {
            include_once('partials/insurance-crm-admin-settings.php');
        }


/**
 * Müşteri detayları sayfası için callback fonksiyonu
 */
public function customer_details_page() {
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-customer-details.php';
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
}