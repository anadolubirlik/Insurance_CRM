<?php
function insurance_crm_frontend_login() {
    if(is_user_logged_in()) {
        wp_redirect(home_url('/temsilci-dashboard'));
        exit;
    }

    ob_start();
    ?>
    <div class="insurance-crm-login-container">
        <form id="insurance-crm-login-form" method="post">
            <h2>Müşteri Temsilcisi Girişi</h2>
            
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <button type="submit" name="insurance_crm_login">Giriş Yap</button>
            </div>

            <?php if(isset($_GET['login']) && $_GET['login'] == 'failed'): ?>
                <div class="error-message">
                    Geçersiz kullanıcı adı veya şifre!
                </div>
            <?php endif; ?>
        </form>
    </div>

    <style>
        .insurance-crm-login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background: #0073aa;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error-message {
            color: #ff0000;
            text-align: center;
            margin-top: 10px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('insurance_crm_login', 'insurance_crm_frontend_login');