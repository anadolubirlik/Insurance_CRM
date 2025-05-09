<?php
/**
 * Müşteri Temsilcisi model sınıfı
 *
 * @package     Insurance_CRM
 * @subpackage  Models
 * @author      Anadolu Birlik
 * @since       1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM_Representative {
    /**
     * Veritabanı tablosu
     *
     * @var string
     */
    private $table;

    /**
     * Temsilci ID'si
     *
     * @var int
     */
    public $id;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'insurance_crm_representatives';
    }

    /**
     * Yeni temsilci ekler
     *
     * @param array $data Temsilci verileri
     * @return int|false Eklenen temsilci ID'si veya hata durumunda false
     */
    public function add($data) {
        global $wpdb;

        // Kullanıcı ID kontrolü
        if (!isset($data['user_id']) || !get_user_by('id', $data['user_id'])) {
            return new WP_Error('invalid_user', __('Geçersiz kullanıcı ID.', 'insurance-crm'));
        }

        // Kullanıcı zaten temsilci mi kontrolü
        if ($this->user_exists($data['user_id'])) {
            return new WP_Error('user_exists', __('Bu kullanıcı zaten müşteri temsilcisi olarak kayıtlı.', 'insurance-crm'));
        }

        $defaults = array(
            'monthly_target' => 0.00,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        // Veri temizleme
        $data = $this->sanitize_representative_data($data);

        $inserted = $wpdb->insert(
            $this->table,
            $data,
            array(
                '%d', // user_id
                '%s', // title
                '%s', // phone
                '%s', // department
                '%f', // monthly_target
                '%s', // status
                '%s', // created_at
                '%s'  // updated_at
            )
        );

        if ($inserted) {
            $representative_id = $wpdb->insert_id;
            
            // Kullanıcıya insurance_representative rolünü ekle
            $user = new WP_User($data['user_id']);
            $user->add_role('insurance_representative');
            
            return $representative_id;
        }

        return false;
    }

    /**
     * Temsilci günceller
     *
     * @param int   $id   Temsilci ID
     * @param array $data Güncellenecek veriler
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;

        // Mevcut temsilci kontrolü
        $current = $this->get($id);
        if (!$current) {
            return new WP_Error('not_found', __('Müşteri temsilcisi bulunamadı.', 'insurance-crm'));
        }

        $data['updated_at'] = current_time('mysql');

        // Veri temizleme
        $data = $this->sanitize_representative_data($data);

        $updated = $wpdb->update(
            $this->table,
            $data,
            array('id' => $id),
            array(
                '%s', // title
                '%s', // phone
                '%s', // department
                '%f', // monthly_target
                '%s', // status
                '%s'  // updated_at
            ),
            array('%d')
        );

        if ($updated !== false) {
            // Eğer durum pasif yapıldıysa, temsilcinin yetkilerini kaldır
            if (isset($data['status']) && $data['status'] === 'inactive') {
                $user = new WP_User($current->user_id);
                $user->remove_role('insurance_representative');
            }
            
            return true;
        }

        return false;
    }

    /**
     * Temsilci siler
     *
     * @param int $id Temsilci ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;

        // Mevcut temsilci kontrolü
        $representative = $this->get($id);
        if (!$representative) {
            return new WP_Error('not_found', __('Müşteri temsilcisi bulunamadı.', 'insurance-crm'));
        }

        // İlişkili müşteri kontrolü
        $customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_customers WHERE representative_id = %d",
            $id
        ));

        if ($customers > 0) {
            return new WP_Error(
                'has_customers',
                sprintf(
                    __('Bu temsilciye atanmış %d adet müşteri bulunmaktadır.', 'insurance-crm'),
                    $customers
                )
            );
        }

        // İlişkili poliçe kontrolü
        $policies = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_policies WHERE representative_id = %d",
            $id
        ));

        if ($policies > 0) {
            return new WP_Error(
                'has_policies',
                sprintf(
                    __('Bu temsilcinin oluşturduğu %d adet poliçe bulunmaktadır.', 'insurance-crm'),
                    $policies
                )
            );
        }

        // İlişkili görev kontrolü
        $tasks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_tasks WHERE representative_id = %d",
            $id
        ));

        if ($tasks > 0) {
            return new WP_Error(
                'has_tasks',
                sprintf(
                    __('Bu temsilciye atanmış %d adet görev bulunmaktadır.', 'insurance-crm'),
                    $tasks
                )
            );
        }

        $deleted = $wpdb->delete(
            $this->table,
            array('id' => $id),
            array('%d')
        );

        if ($deleted) {
            // Kullanıcıdan insurance_representative rolünü kaldır
            $user = new WP_User($representative->user_id);
            $user->remove_role('insurance_representative');
            
            return true;
        }

        return false;
    }

    /**
     * Temsilci getirir
     *
     * @param int $id Temsilci ID
     * @return object|null
     */
    public function get($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Kullanıcı ID'sine göre temsilci getirir
     *
     * @param int $user_id WordPress Kullanıcı ID'si
     * @return object|null
     */
    public function get_by_user_id($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Tüm temsilcileri getirir
     *
     * @param array $args Filtre parametreleri
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'department' => '',
            'search' => '',
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['department'])) {
            $where[] = 'department = %s';
            $values[] = $args['department'];
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(title LIKE %s OR phone LIKE %s)';
            $values = array_merge($values, array($search, $search));
        }

        $sql = "SELECT r.*, u.display_name, u.user_email FROM {$wpdb->prefix}insurance_crm_representatives r 
                LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
                WHERE " . implode(' AND ', $where);
        
        if (!empty($args['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($args['orderby']) . ' ' . esc_sql($args['order']);
        }

        if (!empty($args['limit'])) {
            $sql .= ' LIMIT ' . absint($args['limit']);
            
            if (!empty($args['offset'])) {
                $sql .= ' OFFSET ' . absint($args['offset']);
            }
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Temsilci verilerini temizler
     *
     * @param array $data Temsilci verileri
     * @return array
     */
    private function sanitize_representative_data($data) {
        $clean = array();

        if (isset($data['user_id'])) {
            $clean['user_id'] = absint($data['user_id']);
        }

        if (isset($data['title'])) {
            $clean['title'] = sanitize_text_field($data['title']);
        }

        if (isset($data['phone'])) {
            $clean['phone'] = sanitize_text_field($data['phone']);
        }

        if (isset($data['department'])) {
            $clean['department'] = sanitize_text_field($data['department']);
        }

        if (isset($data['monthly_target'])) {
            $clean['monthly_target'] = (float) $data['monthly_target'];
        }

        if (isset($data['status'])) {
            $clean['status'] = sanitize_text_field($data['status']);
        }

        if (isset($data['created_at'])) {
            $clean['created_at'] = $data['created_at'];
        }

        if (isset($data['updated_at'])) {
            $clean['updated_at'] = $data['updated_at'];
        }

        return $clean;
    }

    /**
     * Kullanıcı ID'sinin müşteri temsilcisi olup olmadığını kontrol eder
     *
     * @param int $user_id WordPress Kullanıcı ID'si
     * @return bool
     */
    private function user_exists($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $user_id
        ));
        
        return (bool) $count;
    }
}