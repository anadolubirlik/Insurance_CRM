<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="insurance-crm-login-wrapper">
    <div class="insurance-crm-login-form">
        <h2>Müşteri Temsilcisi Girişi</h2>
        
        <?php if (isset($_GET['login']) && $_GET['login'] == 'failed'): ?>
            <div class="insurance-crm-error">
                Kullanıcı adı veya şifre hatalı!
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="insurance_crm_login">
            <?php wp_nonce_field('insurance_crm_login', 'insurance_crm_login_nonce'); ?>
            
            <p>
                <label for="username">Kullanıcı Adı</label>
                <input type="text" name="username" id="username" required>
            </p>
            
            <p>
                <label for="password">Şifre</label>
                <input type="password" name="password" id="password" required>
            </p>
            
            <p>
                <button type="submit" class="button button-primary">Giriş Yap</button>
            </p>
        </form>
    </div>
</div>

<style>
.insurance-crm-login-wrapper {
    max-width: 400px;
    margin: 50px auto;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 5px;
}

.insurance-crm-login-form h2 {
    text-align: center;
    margin-bottom: 20px;
}

.insurance-crm-login-form p {
    margin-bottom: 15px;
}

.insurance-crm-login-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.insurance-crm-login-form input[type="text"],
.insurance-crm-login-form input[type="password"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.insurance-crm-login-form button {
    width: 100%;
    padding: 10px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.insurance-crm-error {
    background: #ff0000;
    color: #fff;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 3px;
    text-align: center;
}
</style>