<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function insurance_crm_settings_page() {
    // Ayarları kaydet
    if (isset($_POST['insurance_crm_save_settings']) && check_admin_referer('insurance_crm_settings')) {
        $api_url = sanitize_text_field($_POST['api_url']);
        $api_key = sanitize_text_field($_POST['api_key']);
        $api_secret = sanitize_text_field($_POST['api_secret']);
        $email_notifications = isset($_POST['email_notifications']) ? '1' : '0';
        $reminder_days = absint($_POST['reminder_days']);
        
        update_option('insurance_crm_api_url', $api_url);
        update_option('insurance_crm_api_key', $api_key);
        update_option('insurance_crm_api_secret', $api_secret);
        update_option('insurance_crm_email_notifications', $email_notifications);
        update_option('insurance_crm_reminder_days', $reminder_days);
        
        echo '<div class="updated"><p>Ayarlar kaydedildi.</p></div>';
    }
    
    // Mevcut ayarları al
    $api_url = get_option('insurance_crm_api_url', '');
    $api_key = get_option('insurance_crm_api_key', '');
    $api_secret = get_option('insurance_crm_api_secret', '');
    $email_notifications = get_option('insurance_crm_email_notifications', '1');
    $reminder_days = get_option('insurance_crm_reminder_days', '30');
    ?>
    <div class="wrap">
        <h1>Insurance CRM Ayarları</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('insurance_crm_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">API URL</th>
                    <td>
                        <input type="url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text">
                        <p class="description">Sigorta şirketinin API endpoint URL'si</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">API Anahtarı</th>
                    <td>
                        <input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        <p class="description">API erişim anahtarı</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">API Gizli Anahtarı</th>
                    <td>
                        <input type="password" name="api_secret" value="<?php echo esc_attr($api_secret); ?>" class="regular-text">
                        <p class="description">API gizli anahtarı</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">E-posta Bildirimleri</th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_notifications" value="1" <?php checked($email_notifications, '1'); ?>>
                            E-posta bildirimlerini etkinleştir
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Hatırlatma Günleri</th>
                    <td>
                        <input type="number" name="reminder_days" value="<?php echo esc_attr($reminder_days); ?>" min="1" max="90" class="small-text">
                        <p class="description">Poliçe yenileme hatırlatmaları için gün sayısı</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="insurance_crm_save_settings" class="button button-primary" value="Ayarları Kaydet">
            </p>
        </form>
    </div>
    <?php
}