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
?>

<div class="insurance-crm-login-wrapper">
    <div class="insurance-crm-login-box">
        <h2>Müşteri Temsilcisi Girişi</h2>
        
        <?php echo $login_error; ?>
        
        <form method="post" class="insurance-crm-login-form">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" name="username" id="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <div class="form-group">
                <input type="submit" name="insurance_crm_login" value="Giriş Yap" class="button button-primary">
            </div>
        </form>
    </div>
</div>

<style>
.insurance-crm-login-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background: #f5f5f5;
}

.insurance-crm-login-box {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
}

.insurance-crm-login-box h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #666;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-group input[type="submit"] {
    width: 100%;
    padding: 12px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.form-group input[type="submit"]:hover {
    background: #005177;
}

.login-error {
    background: #ff6b6b;
    color: #fff;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}
</style>