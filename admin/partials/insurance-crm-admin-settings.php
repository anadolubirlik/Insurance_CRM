<?php
/**
 * Ayarlar Sayfası
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/admin/partials
 * @author     Anadolu Birlik
 * @since      1.0.0 (2025-05-02)
 */

if (!defined('WPINC')) {
    die;
}

// Ayarları kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insurance_crm_settings_nonce'])) {
    if (!wp_verify_nonce($_POST['insurance_crm_settings_nonce'], 'insurance_crm_save_settings')) {
        wp_die(__('Güvenlik doğrulaması başarısız', 'insurance-crm'));
    }

    $settings = array(
        'company_name' => sanitize_text_field($_POST['company_name']),
        'company_email' => sanitize_email($_POST['company_email']),
        'renewal_reminder_days' => intval($_POST['renewal_reminder_days']),
        'task_reminder_days' => intval($_POST['task_reminder_days']),
        'default_policy_types' => array_map('sanitize_text_field', explode("\n", trim($_POST['default_policy_types']))),
        'default_task_types' => array_map('sanitize_text_field', explode("\n", trim($_POST['default_task_types']))),
        'notification_settings' => array(
            'email_notifications' => isset($_POST['email_notifications']),
            'renewal_notifications' => isset($_POST['renewal_notifications']),
            'task_notifications' => isset($_POST['task_notifications'])
        ),
        'pdf_settings' => array(
            'header_logo' => sanitize_text_field($_POST['header_logo']),
            'footer_text' => sanitize_textarea_field($_POST['footer_text']),
            'paper_size' => sanitize_text_field($_POST['paper_size'])
        ),
        'email_templates' => array(
            'renewal_reminder' => wp_kses_post($_POST['renewal_reminder_template']),
            'task_reminder' => wp_kses_post($_POST['task_reminder_template']),
            'new_policy' => wp_kses_post($_POST['new_policy_template'])
        )
    );

    update_option('insurance_crm_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Ayarlar başarıyla kaydedildi.', 'insurance-crm') . '</p></div>';
}

// Mevcut ayarları al
$settings = get_option('insurance_crm_settings');
?>

<div class="wrap insurance-crm-wrap">
    <h1><?php _e('Insurance CRM Ayarları', 'insurance-crm'); ?></h1>

    <form method="post" class="insurance-crm-settings-form">
        <?php wp_nonce_field('insurance_crm_save_settings', 'insurance_crm_settings_nonce'); ?>

        <div class="insurance-crm-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Genel', 'insurance-crm'); ?></a>
                <a href="#notifications" class="nav-tab"><?php _e('Bildirimler', 'insurance-crm'); ?></a>
                <a href="#templates" class="nav-tab"><?php _e('E-posta Şablonları', 'insurance-crm'); ?></a>
                <a href="#pdf" class="nav-tab"><?php _e('PDF Ayarları', 'insurance-crm'); ?></a>
            </nav>

            <!-- Genel Ayarlar -->
            <div id="general" class="insurance-crm-settings-tab active">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="company_name"><?php _e('Şirket Adı', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="company_name" id="company_name" class="regular-text" 
                                   value="<?php echo esc_attr($settings['company_name']); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="company_email"><?php _e('Şirket E-posta', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="company_email" id="company_email" class="regular-text" 
                                   value="<?php echo esc_attr($settings['company_email']); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="renewal_reminder_days"><?php _e('Yenileme Hatırlatma (Gün)', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="renewal_reminder_days" id="renewal_reminder_days" class="small-text" 
                                   value="<?php echo esc_attr($settings['renewal_reminder_days']); ?>" min="1" max="90">
                            <p class="description"><?php _e('Poliçe yenileme hatırlatması için kaç gün önceden bildirim gönderilsin?', 'insurance-crm'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="task_reminder_days"><?php _e('Görev Hatırlatma (Gün)', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="task_reminder_days" id="task_reminder_days" class="small-text" 
                                   value="<?php echo esc_attr($settings['task_reminder_days']); ?>" min="1" max="30">
                            <p class="description"><?php _e('Görev hatırlatması için kaç gün önceden bildirim gönderilsin?', 'insurance-crm'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="default_policy_types"><?php _e('Varsayılan Poliçe Türleri', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <textarea name="default_policy_types" id="default_policy_types" class="large-text code" rows="6"><?php 
                                echo esc_textarea(implode("\n", $settings['default_policy_types'])); 
                            ?></textarea>
                            <p class="description"><?php _e('Her satıra bir poliçe türü yazın.', 'insurance-crm'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="default_task_types"><?php _e('Varsayılan Görev Türleri', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <textarea name="default_task_types" id="default_task_types" class="large-text code" rows="6"><?php 
                                echo esc_textarea(implode("\n", $settings['default_task_types'])); 
                            ?></textarea>
                            <p class="description"><?php _e('Her satıra bir görev türü yazın.', 'insurance-crm'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Bildirim Ayarları -->
            <div id="notifications" class="insurance-crm-settings-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('E-posta Bildirimleri', 'insurance-crm'); ?></th>
                        <td>
                            <fieldset>
                                <label for="email_notifications">
                                    <input type="checkbox" name="email_notifications" id="email_notifications" 
                                           <?php checked($settings['notification_settings']['email_notifications']); ?>>
                                    <?php _e('E-posta bildirimlerini etkinleştir', 'insurance-crm'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Yenileme Bildirimleri', 'insurance-crm'); ?></th>
                        <td>
                            <fieldset>
                                <label for="renewal_notifications">
                                    <input type="checkbox" name="renewal_notifications" id="renewal_notifications" 
                                           <?php checked($settings['notification_settings']['renewal_notifications']); ?>>
                                    <?php _e('Poliçe yenileme bildirimlerini etkinleştir', 'insurance-crm'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Görev Bildirimleri', 'insurance-crm'); ?></th>
                        <td>
                            <fieldset>
                                <label for="task_notifications">
                                    <input type="checkbox" name="task_notifications" id="task_notifications" 
                                           <?php checked($settings['notification_settings']['task_notifications']); ?>>
                                    <?php _e('Görev bildirimlerini etkinleştir', 'insurance-crm'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- E-posta Şablonları -->
            <div id="templates" class="insurance-crm-settings-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="renewal_reminder_template"><?php _e('Yenileme Hatırlatma Şablonu', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                $settings['email_templates']['renewal_reminder'],
                                'renewal_reminder_template',
                                array(
                                    'textarea_name' => 'renewal_reminder_template',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false
                                )
                            );
                            ?>
                            <p class="description">
                                <?php _e('Kullanılabilir değişkenler: {customer_name}, {policy_number}, {policy_type}, {end_date}, {premium_amount}', 'insurance-crm'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="task_reminder_template"><?php _e('Görev Hatırlatma Şablonu', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                $settings['email_templates']['task_reminder'],
                                'task_reminder_template',
                                array(
                                    'textarea_name' => 'task_reminder_template',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false
                                )
                            );
                            ?>
                            <p class="description">
                                <?php _e('Kullanılabilir değişkenler: {customer_name}, {task_description}, {due_date}, {priority}', 'insurance-crm'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="new_policy_template"><?php _e('Yeni Poliçe Bildirimi', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_editor(
                                $settings['email_templates']['new_policy'],
                                'new_policy_template',
                                array(
                                    'textarea_name' => 'new_policy_template',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false
                                )
                            );
                            ?>
                            <p class="description">
                                <?php _e('Kullanılabilir değişkenler: {customer_name}, {policy_number}, {policy_type}, {start_date}, {end_date}, {premium_amount}', 'insurance-crm'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- PDF Ayarları -->
            <div id="pdf" class="insurance-crm-settings-tab">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="header_logo"><?php _e('Üst Logo', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="header_logo" id="header_logo" class="regular-text" 
                                   value="<?php echo esc_attr($settings['pdf_settings']['header_logo']); ?>">
                            <button type="button" class="button button-secondary" id="upload_logo">
                                <?php _e('Medya Kütüphanesini Aç', 'insurance-crm'); ?>
                            </button>
                            <p class="description"><?php _e('PDF dosyalarının üst kısmında görünecek logo.', 'insurance-crm'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="footer_text"><?php _e('Alt Metin', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <textarea name="footer_text" id="footer_text" class="large-text" rows="3"><?php 
                                echo esc_textarea($settings['pdf_settings']['footer_text']); 
                            ?></textarea>
                            <p class="description"><?php _e('PDF dosyalarının alt kısmında görünecek metin.', 'insurance-crm'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="paper_size"><?php _e('Kağıt Boyutu', 'insurance-crm'); ?></label>
                        </th>
                        <td>
                            <select name="paper_size" id="paper_size">
                                <option value="A4" <?php selected($settings['pdf_settings']['paper_size'], 'A4'); ?>>A4</option>
                                <option value="Letter" <?php selected($settings['pdf_settings']['paper_size'], 'Letter'); ?>>Letter</option>
                                <option value="Legal" <?php selected($settings['pdf_settings']['paper_size'], 'Legal'); ?>>Legal</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button(__('Ayarları Kaydet', 'insurance-crm')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab yönetimi
    $('.insurance-crm-settings-tabs nav a').click(function(e) {
        e.preventDefault();
        var tab = $(this).attr('href').substring(1);
        
        // Tab butonlarını güncelle
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Tab içeriklerini güncelle
        $('.insurance-crm-settings-tab').removeClass('active');
        $('#' + tab).addClass('active');
    });

    // Medya yükleyici
    $('#upload_logo').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: '<?php _e('Logo Seç', 'insurance-crm'); ?>',
            multiple: false
        }).open()
        .on('select', function(e) {
            var uploaded_image = image.state().get('selection').first();
            $('#header_logo').val(uploaded_image.toJSON().url);
        });
    });

    // Test e-postası gönder
    $('#send_test_email').click(function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'insurance_crm_test_email',
                nonce: '<?php echo wp_create_nonce('insurance_crm_test_email'); ?>'
            },
            success: function(response) {
                alert(response.data);
            }
        });
    });
});
</script>

<style>
.insurance-crm-settings-tabs {
    margin-top: 20px;
}

.insurance-crm-settings-tab {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.insurance-crm-settings-tab.active {
    display: block;
}

.insurance-crm-settings-form .form-table th {
    width: 300px;
}

.insurance-crm-settings-form .description {
    margin-top: 5px;
    color: #666;
}
</style>