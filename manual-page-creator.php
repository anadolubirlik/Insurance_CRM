<?php
// Bu kodu geçici bir PHP dosyası olarak wp-content/plugins/insurance-crm/ klasörüne kaydedin
// ve tarayıcıdan bir kez çalıştırın

require_once('../../../../wp-load.php');

// Login sayfası
$login_page = array(
    'post_title'    => 'Temsilci Girişi',
    'post_content'  => '[insurance_crm_login]',
    'post_status'   => 'publish',
    'post_type'     => 'page',
    'post_name'     => 'temsilci-girisi'
);

// Panel sayfası
$panel_page = array(
    'post_title'    => 'Temsilci Paneli',
    'post_content'  => '[insurance_crm_panel]',
    'post_status'   => 'publish',
    'post_type'     => 'page',
    'post_name'     => 'temsilci-paneli'
);

// Sayfaları oluştur
if (!get_page_by_path('temsilci-girisi')) {
    $login_id = wp_insert_post($login_page);
    echo "Login sayfası oluşturuldu. ID: " . $login_id . "<br>";
}

if (!get_page_by_path('temsilci-paneli')) {
    $panel_id = wp_insert_post($panel_page);
    echo "Panel sayfası oluşturuldu. ID: " . $panel_id . "<br>";
}