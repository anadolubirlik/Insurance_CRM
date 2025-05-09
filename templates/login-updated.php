<?php
if (!defined('ABSPATH')) {
    exit;
}

// Eğer kullanıcı zaten giriş yapmışsa ve müşteri temsilcisi ise panele yönlendir
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('insurance_representative', (array)$user->roles)) {
        wp_safe_redirect(home_url('/temsilci-paneli/'));
        exit;
    }
}

// Login formundan gelen hataları kontrol et
$login_error = '';
if (isset($_GET['login']) && $_GET['login'] === 'failed') {
    $login_error = '<div class="login-error">Kullanıcı adı veya şifre hatalı.</div>';
}
if (isset($_GET['login']) && $_GET['login'] === 'inactive') {
    $login_error = '<div class="login-error">Hesabınız pasif durumda. Lütfen yöneticiniz ile iletişime geçin.</div>';
}

// Debug - Kullanıcı kontrolü
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    global $wpdb;
    $users = $wpdb->get_results("
        SELECT u.user_login, u.ID, u.user_email, GROUP_CONCAT(umeta.meta_value) as roles 
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} umeta ON u.ID = umeta.user_id AND umeta.meta_key = '{$wpdb->prefix}capabilities'
        GROUP BY u.ID
    ");
    
    echo '<pre>';
    foreach ($users as $user) {
        echo "User ID: {$user->ID}, Login: {$user->user_login}, Email: {$user->user_email}, Roles: {$user->roles}\n";
    }
    echo '</pre>';
}
?>

<div class="insurance-crm-login-wrapper">
    <div class="insurance-crm-login-box">
        <div class="login-header">
            <?php 
            $company_settings = get_option('insurance_crm_settings');
            $company_name = !empty($company_settings['company_name']) ? $company_settings['company_name'] : get_bloginfo('name');
            ?>
            <h2><?php echo esc_html($company_name); ?></h2>
            <div class="login-logo">
                <?php 
                $logo_url = !empty($company_settings['company_logo']) ? $company_settings['company_logo'] : plugins_url('/assets/images/insurance-logo.png', dirname(__FILE__));
                ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?> Logo">
            </div>
            <h3>Müşteri Temsilcisi Girişi</h3>
        </div>
        
        <?php echo $login_error; ?>
        
        <form method="post" class="insurance-crm-login-form">
            <div class="form-group">
                <label for="username"><i class="dashicons dashicons-admin-users"></i></label>
                <input type="text" name="username" id="username" placeholder="Kullanıcı Adı" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="dashicons dashicons-lock"></i></label>
                <input type="password" name="password" id="password" placeholder="Şifre" required autocomplete="current-password">
            </div>

            <div class="form-remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Beni hatırla</label>
            </div>
            
            <div class="form-group">
                <input type="submit" name="insurance_crm_login" value="Giriş Yap" class="login-button">
            </div>
            
            <?php wp_nonce_field('insurance_crm_login', 'insurance_crm_login_nonce'); ?>
        </form>
        
        <div class="login-footer">
            <p><?php echo date('Y'); ?> &copy; <?php echo esc_html($company_name); ?> - Sigorta CRM</p>
        </div>
    </div>
</div>

<style>
.insurance-crm-login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f7f9fc;
    padding: 20px;
}

.insurance-crm-login-box {
    width: 400px;
    max-width: 100%;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 30px;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 10px;
}

.login-header h3 {
    font-size: 18px;
    font-weight: 500;
    color: #7f8c8d;
    margin: 10px 0;
}

.login-logo {
    margin: 15px 0;
}

.login-logo img {
    max-height: 80px;
    max-width: 200px;
}

.insurance-crm-login-form .form-group {
    margin-bottom: 20px;
    position: relative;
}

.insurance-crm-login-form label {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
}

.insurance-crm-login-form input[type="text"],
.insurance-crm-login-form input[type="password"] {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.insurance-crm-login-form input[type="text"]:focus,
.insurance-crm-login-form input[type="password"]:focus {
    border-color: #2980b9;
    outline: none;
    box-shadow: 0 0 0 2px rgba(41, 128, 185, 0.2);
}

.form-remember {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.form-remember input {
    margin-right: 8px;
}

.login-button {
    width: 100%;
    background-color: #2980b9;
    color: white;
    border: none;
    padding: 12px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.login-button:hover {
    background-color: #3498db;
}

.login-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #f5c6cb;
}

.login-footer {
    text-align: center;
    margin-top: 30px;
    color: #7f8c8d;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 480px) {
    .insurance-crm-login-box {
        padding: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.insurance-crm-login-form').on('submit', function() {
        $('.login-button').prop('disabled', true).val('Giriş Yapılıyor...');
    });
});
</script>