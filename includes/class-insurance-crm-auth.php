<?php

class Insurance_CRM_Auth {
    
    public function __construct() {
        add_action('admin_post_insurance_crm_login', array($this, 'handle_login'));
        add_action('admin_post_nopriv_insurance_crm_login', array($this, 'handle_login'));
        add_action('wp', array($this, 'check_auth'));
    }

    public function handle_login() {
        if (!isset($_POST['insurance_crm_login_nonce']) || 
            !wp_verify_nonce($_POST['insurance_crm_login_nonce'], 'insurance_crm_login')) {
            wp_safe_redirect(home_url('/temsilci-girisi?login=failed'));
            exit;
        }

        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            wp_safe_redirect(home_url('/temsilci-girisi?login=failed'));
            exit;
        }

        // Kullanıcının müşteri temsilcisi olup olmadığını kontrol et
        if (!in_array('insurance_representative', (array) $user->roles)) {
            wp_safe_redirect(home_url('/temsilci-girisi?login=failed'));
            exit;
        }

        wp_set_auth_cookie($user->ID);
        wp_safe_redirect(home_url('/temsilci-paneli'));
        exit;
    }

    public function check_auth() {
        if (is_page('temsilci-paneli')) {
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/temsilci-girisi'));
                exit;
            }

            $user = wp_get_current_user();
            if (!in_array('insurance_representative', (array) $user->roles)) {
                wp_redirect(home_url('/temsilci-girisi'));
                exit;
            }
        }
    }
}

new Insurance_CRM_Auth();