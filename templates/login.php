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

// Şirket bilgilerini al
$company_settings = get_option('insurance_crm_settings');
$company_name = !empty($company_settings['company_name']) ? $company_settings['company_name'] : get_bloginfo('name');

// Logo URL'ini al
$logo_url = !empty($company_settings['company_logo']) ? $company_settings['company_logo'] : plugins_url('/assets/images/insurance-logo.png', dirname(__FILE__));
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($company_name); ?> - Müşteri Temsilcisi Girişi</title>
    <?php wp_head(); ?>
</head>

<body class="insurance-crm-login-page">
    <div class="insurance-crm-login-wrapper">
        <div class="insurance-crm-login-box">
            <div class="login-header">
                <h2><?php echo esc_html($company_name); ?></h2>
                <div class="login-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?> Logo">
                </div>
                <h3>Müşteri Temsilcisi Girişi</h3>
            </div>
            
            <?php echo $login_error; ?>
            
            <form method="post" class="insurance-crm-login-form" id="loginform">
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
                    <input type="submit" name="insurance_crm_login" value="Giriş Yap" class="login-button" id="wp-submit">
                    <div class="login-loading" style="display:none;">
                        <i class="dashicons dashicons-update spin"></i> Giriş yapılıyor...
                    </div>
                </div>
                
                <?php wp_nonce_field('insurance_crm_login', 'insurance_crm_login_nonce'); ?>
            </form>
            
            <div class="login-footer">
                <p><?php echo date('Y'); ?> &copy; <?php echo esc_html($company_name); ?> - Sigorta CRM</p>
            </div>
        </div>
    </div>

<style>
body.insurance-crm-login-page {
    background: #f7f9fc;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.insurance-crm-login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.insurance-crm-login-box {
    width: 400px;
    max-width: 100%;
    background: #fff;
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 40px;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-header h2 {
    font-size: 28px;
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
    margin: 20px 0;
}

.login-logo img {
    max-height: 100px;
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
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-sizing: border-box;
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
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.login-button:hover {
    background-color: #3498db;
}

.login-button:active {
    background-color: #1c638d;
}

.login-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #f5c6cb;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.login-footer {
    text-align: center;
    margin-top: 30px;
    color: #7f8c8d;
    font-size: 14px;
}

.login-loading {
    text-align: center;
    margin-top: 10px;
    color: #7f8c8d;
}

.spin {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 480px) {
    .insurance-crm-login-box {
        padding: 30px 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Login formunu gönderirken buton devre dışı bırakılsın ve yükleniyor animasyonu gösterilsin
    $("#loginform").on("submit", function() {
        $("#wp-submit").prop("disabled", true);
        $(".login-loading").show();
    });
});
</script>
<?php wp_footer(); ?>
</body>
</html>