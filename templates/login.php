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
    padding: 0;
    margin: 0;
    background: linear-gradient(135deg, #0073aa, #005082);
}

.insurance-crm-login-box {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    width: 100%;
    max-width: 350px;
    text-align: center;
}

.login-header {
    margin-bottom: 25px;
}

.login-header h2 {
    color: #005082;
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 700;
}

.login-header h3 {
    color: #666;
    font-size: 16px;
    margin: 10px 0;
    font-weight: 500;
}

.login-logo {
    margin: 15px 0;
}

.login-logo img {
    max-width: 120px;
    height: auto;
}

.insurance-crm-login-form {
    padding: 0 10px;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-group label {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    line-height: 1;
}

.form-group .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0,115,170,0.2);
    outline: none;
    background-color: #fff;
}

.form-remember {
    text-align: left;
    margin-bottom: 20px;
    color: #666;
    font-size: 14px;
}

.form-remember input[type="checkbox"] {
    margin-right: 5px;
}

.login-button {
    width: 100%;
    padding: 12px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.login-button:hover {
    background: #005a87;
}

.login-error {
    background: #fff0f0;
    color: #e53935;
    border-left: 4px solid #e53935;
    padding: 10px 15px;
    margin-bottom: 20px;
    text-align: left;
    font-size: 14px;
    border-radius: 4px;
}

.login-footer {
    margin-top: 25px;
    font-size: 13px;
    color: #888;
}

/* Mobile responsive */
@media (max-width: 480px) {
    .insurance-crm-login-box {
        margin: 0 15px;
        padding: 20px;
    }
}
</style>