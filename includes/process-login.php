<?php
function insurance_crm_process_login() {
    if(isset($_POST['insurance_crm_login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $user = wp_authenticate($username, $password);
        
        if(is_wp_error($user)) {
            wp_redirect(add_query_arg('login', 'failed', $_SERVER['HTTP_REFERER']));
            exit;
        }
        
        // Kullanıcının müşteri temsilcisi olup olmadığını kontrol et
        if(!in_array('insurance_representative', (array)$user->roles)) {
            wp_redirect(add_query_arg('login', 'failed', $_SERVER['HTTP_REFERER']));
            exit;
        }
        
        wp_set_auth_cookie($user->ID);
        wp_redirect(home_url('/temsilci-dashboard'));
        exit;
    }
}
add_action('init', 'insurance_crm_process_login');