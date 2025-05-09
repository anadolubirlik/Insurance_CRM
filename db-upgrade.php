/**
 * Veritabanı güncelleme fonksiyonu - Versiyon 1.0.5
 */
function insurance_crm_update_db_105() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Müşteriler tablosuna yeni alanlar ekle
    $customers_table = $wpdb->prefix . 'insurance_crm_customers';
    
    $wpdb->query("ALTER TABLE $customers_table 
        ADD COLUMN birth_date DATE DEFAULT NULL,
        ADD COLUMN gender VARCHAR(10) DEFAULT NULL,
        ADD COLUMN is_pregnant TINYINT(1) DEFAULT NULL,
        ADD COLUMN pregnancy_week INT DEFAULT NULL,
        ADD COLUMN occupation VARCHAR(100) DEFAULT NULL,
        ADD COLUMN spouse_name VARCHAR(100) DEFAULT NULL,
        ADD COLUMN spouse_birth_date DATE DEFAULT NULL,
        ADD COLUMN children_count INT DEFAULT 0,
        ADD COLUMN children_names TEXT DEFAULT NULL,
        ADD COLUMN children_birth_dates TEXT DEFAULT NULL,
        ADD COLUMN has_vehicle TINYINT(1) DEFAULT NULL,
        ADD COLUMN vehicle_plate VARCHAR(20) DEFAULT NULL,
        ADD COLUMN has_pet TINYINT(1) DEFAULT NULL,
        ADD COLUMN pet_name VARCHAR(50) DEFAULT NULL,
        ADD COLUMN pet_type VARCHAR(50) DEFAULT NULL,
        ADD COLUMN pet_age VARCHAR(20) DEFAULT NULL,
        ADD COLUMN owns_home TINYINT(1) DEFAULT NULL,
        ADD COLUMN has_dask_policy TINYINT(1) DEFAULT NULL,
        ADD COLUMN dask_policy_expiry DATE DEFAULT NULL,
        ADD COLUMN has_home_policy TINYINT(1) DEFAULT NULL,
        ADD COLUMN home_policy_expiry DATE DEFAULT NULL
    ");
    
    // Müşteri notları tablosunu oluştur
    $notes_table = $wpdb->prefix . 'insurance_crm_customer_notes';
    
    $sql = "CREATE TABLE IF NOT EXISTS $notes_table (
        id INT NOT NULL AUTO_INCREMENT,
        customer_id INT NOT NULL,
        note_content TEXT NOT NULL,
        note_type VARCHAR(20) NOT NULL,
        rejection_reason VARCHAR(50) DEFAULT NULL,
        created_by BIGINT(20) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY customer_id (customer_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    update_option('insurance_crm_db_version', '1.0.5');
}