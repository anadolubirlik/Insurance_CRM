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
    }
}