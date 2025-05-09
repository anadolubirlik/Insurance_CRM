<?php
/**
 * Public tarafı işlemleri için sınıf
 */
class Insurance_CRM_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/insurance-crm-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/insurance-crm-public.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'insurance_crm_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('insurance_crm_public_nonce')
        ));
    }
}