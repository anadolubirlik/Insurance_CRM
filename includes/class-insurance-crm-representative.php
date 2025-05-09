<?php
/**
 * Müşteri Temsilcisi sınıfı
 */
class Insurance_CRM_Representative {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'insurance_crm_representatives';
    }

    /**
     * Yeni müşteri temsilcisi ekle
     */
    public function add_representative($data) {
        global $wpdb;
        
        // WordPress kullanıcısı oluştur
        $userdata = array(
            'user_login'  => $data['email'],
            'user_email'  => $data['email'],
            'user_pass'   => wp_generate_password(),
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'role'        => 'insurance_representative'
        );
        
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            return false;
        }

        // Şifre sıfırlama bağlantısı gönder
        wp_send_new_user_notifications($user_id, 'both');
        
        // Temsilci bilgilerini kaydet
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id'    => $user_id,
                'title'      => $data['title'],
                'phone'      => $data['phone'],
                'department' => $data['department'],
                'status'     => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $user_id : false;
    }

    /**
     * Temsilci bilgilerini güncelle
     */
    public function update_representative($id, $data) {
        global $wpdb;
        
        $rep = $this->get_representative($id);
        if (!$rep) return false;
        
        // WordPress kullanıcı bilgilerini güncelle
        $userdata = array(
            'ID'          => $rep->user_id,
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'user_email'  => $data['email']
        );
        
        wp_update_user($userdata);
        
        // Temsilci bilgilerini güncelle
        return $wpdb->update(
            $this->table_name,
            array(
                'title'      => $data['title'],
                'phone'      => $data['phone'],
                'department' => $data['department'],
                'status'     => $data['status']
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Temsilci bilgilerini getir
     */
    public function get_representative($id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT r.*, u.user_email as email, u.first_name, u.last_name
             FROM {$this->table_name} r
             JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.id = %d",
            $id
        );
        
        return $wpdb->get_row($query);
    }

    /**
     * Tüm aktif temsilcileri listele
     */
    public function get_all_representatives($status = 'active') {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT r.*, u.user_email as email, u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_customers WHERE representative_id = r.id) as customer_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_policies WHERE representative_id = r.id) as policy_count
             FROM {$this->table_name} r
             JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.status = %s
             ORDER BY u.last_name, u.first_name",
            $status
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Temsilcinin müşterilerini listele
     */
    public function get_representative_customers($id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT c.*
             FROM {$wpdb->prefix}insurance_crm_customers c
             WHERE c.representative_id = %d
             ORDER BY c.last_name, c.first_name",
            $id
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Temsilcinin poliçelerini listele
     */
    public function get_representative_policies($id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT p.*, c.first_name, c.last_name
             FROM {$wpdb->prefix}insurance_crm_policies p
             JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
             WHERE p.representative_id = %d
             ORDER BY p.end_date ASC",
            $id
        );
        
        return $wpdb->get_results($query);
    }
}