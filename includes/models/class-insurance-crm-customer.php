<?php
/**
 * Müşteri model sınıfı
 *
 * @package     Insurance_CRM
 * @subpackage  Models
 * @author      Anadolu Birlik
 * @since       1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM_Customer {
    /**
     * Veritabanı tablosu
     *
     * @var string
     */
    private $table;

    /**
     * Müşteri ID'si
     *
     * @var int
     */
    public $id;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'insurance_crm_customers';
    }

    /**
     * Yeni müşteri ekler
     *
     * @param array $data Müşteri verileri
     * @return int|false Eklenen müşteri ID'si veya hata durumunda false
     */
    public function add($data) {
        global $wpdb;

        // TC Kimlik kontrolü
        if (!$this->validate_tc_identity($data['tc_identity'])) {
            return new WP_Error('invalid_tc', __('Geçersiz TC Kimlik numarası.', 'insurance-crm'));
        }

        // TC Kimlik benzersizlik kontrolü
        if ($this->tc_identity_exists($data['tc_identity'])) {
            return new WP_Error('tc_exists', __('Bu TC Kimlik numarası ile kayıtlı müşteri bulunmaktadır.', 'insurance-crm'));
        }

        $defaults = array(
            'category' => 'bireysel',
            'status' => 'aktif',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        // Veri temizleme
        $data = $this->sanitize_customer_data($data);

        $inserted = $wpdb->insert(
            $this->table,
            $data,
            array(
                '%s', // first_name
                '%s', // last_name
                '%s', // tc_identity
                '%s', // email
                '%s', // phone
                '%s', // address
                '%s', // category
                '%s', // status
                '%s', // created_at
                '%s'  // updated_at
            )
        );

        if ($inserted) {
            $customer_id = $wpdb->insert_id;
            
            // Aktivite logu
            $this->log_activity($customer_id, 'create', sprintf(
                __('Yeni müşteri eklendi: %s %s', 'insurance-crm'),
                $data['first_name'],
                $data['last_name']
            ));

            return $customer_id;
        }

        return false;
    }

    /**
     * Müşteri günceller
     *
     * @param int   $id   Müşteri ID
     * @param array $data Güncellenecek veriler
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;

        // Mevcut müşteri kontrolü
        $current = $this->get($id);
        if (!$current) {
            return new WP_Error('not_found', __('Müşteri bulunamadı.', 'insurance-crm'));
        }

        // TC Kimlik kontrolü (değiştirilmişse)
        if (isset($data['tc_identity']) && $data['tc_identity'] !== $current->tc_identity) {
            if (!$this->validate_tc_identity($data['tc_identity'])) {
                return new WP_Error('invalid_tc', __('Geçersiz TC Kimlik numarası.', 'insurance-crm'));
            }
            if ($this->tc_identity_exists($data['tc_identity'])) {
                return new WP_Error('tc_exists', __('Bu TC Kimlik numarası ile kayıtlı müşteri bulunmaktadır.', 'insurance-crm'));
            }
        }

        $data['updated_at'] = current_time('mysql');

        // Veri temizleme
        $data = $this->sanitize_customer_data($data);

        $updated = $wpdb->update(
            $this->table,
            $data,
            array('id' => $id),
            array(
                '%s', // first_name
                '%s', // last_name
                '%s', // tc_identity
                '%s', // email
                '%s', // phone
                '%s', // address
                '%s', // category
                '%s', // status
                '%s'  // updated_at
            ),
            array('%d')
        );

        if ($updated !== false) {
            // Aktivite logu
            $this->log_activity($id, 'update', sprintf(
                __('Müşteri güncellendi: %s %s', 'insurance-crm'),
                $data['first_name'],
                $data['last_name']
            ));

            return true;
        }

        return false;
    }

    /**
     * Müşteri siler
     *
     * @param int $id Müşteri ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;

        // Mevcut müşteri kontrolü
        $customer = $this->get($id);
        if (!$customer) {
            return new WP_Error('not_found', __('Müşteri bulunamadı.', 'insurance-crm'));
        }

        // İlişkili poliçe kontrolü
        $policies = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_policies WHERE customer_id = %d",
            $id
        ));

        if ($policies > 0) {
            return new WP_Error(
                'has_policies',
                sprintf(
                    __('Bu müşteriye ait %d adet poliçe bulunmaktadır. Önce poliçeleri silmelisiniz.', 'insurance-crm'),
                    $policies
                )
            );
        }

        // İlişkili görev kontrolü
        $tasks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_tasks WHERE customer_id = %d",
            $id
        ));

        if ($tasks > 0) {
            return new WP_Error(
                'has_tasks',
                sprintf(
                    __('Bu müşteriye ait %d adet görev bulunmaktadır. Önce görevleri silmelisiniz.', 'insurance-crm'),
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
            // Aktivite logu
            $this->log_activity($id, 'delete', sprintf(
                __('Müşteri silindi: %s %s', 'insurance-crm'),
                $customer->first_name,
                $customer->last_name
            ));

            return true;
        }

        return false;
    }

    /**
     * Müşteri getirir
     *
     * @param int $id Müşteri ID
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
     * Tüm müşterileri getirir
     *
     * @param array $args Filtre parametreleri
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'category' => '',
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

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $values[] = $args['category'];
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(first_name LIKE %s OR last_name LIKE %s OR tc_identity LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $values = array_merge($values, array($search, $search, $search, $search, $search));
        }

        $sql = "SELECT * FROM {$wpdb->prefix}insurance_crm_customers WHERE " . implode(' AND ', $where);
        
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
     * Müşteri verilerini temizler
     *
     * @param array $data Müşteri verileri
     * @return array
     */
    private function sanitize_customer_data($data) {
        $clean = array();

        if (isset($data['first_name'])) {
            $clean['first_name'] = sanitize_text_field($data['first_name']);
        }

        if (isset($data['last_name'])) {
            $clean['last_name'] = sanitize_text_field($data['last_name']);
        }

        if (isset($data['tc_identity'])) {
            $clean['tc_identity'] = sanitize_text_field($data['tc_identity']);
        }

        if (isset($data['email'])) {
            $clean['email'] = sanitize_email($data['email']);
        }

        if (isset($data['phone'])) {
            $clean['phone'] = sanitize_text_field($data['phone']);
        }

        if (isset($data['address'])) {
            $clean['address'] = sanitize_textarea_field($data['address']);
        }

        if (isset($data['category'])) {
            $clean['category'] = sanitize_text_field($data['category']);
        }

        if (isset($data['status'])) {
            $clean['status'] = sanitize_text_field($data['status']);
        }

        return $clean;
    }

    /**
     * TC Kimlik numarasını doğrular
     *
     * @param string $tc_identity TC Kimlik numarası
     * @return bool
     */
    private function validate_tc_identity($tc_identity) {
        // 11 haneli olmalı
        if (strlen($tc_identity) != 11) {
            return false;
        }

        // Sadece rakam içermeli
        if (!ctype_digit($tc_identity)) {
            return false;
        }

        // İlk hane 0 olamaz
        if ($tc_identity[0] == '0') {
            return false;
        }

        // Son rakam çift olmalı
        if ($tc_identity[10] % 2 != 0) {
            return false;
        }

        // Algoritma kontrolü
        $digits = str_split($tc_identity);
        
        // 1, 3, 5, 7, 9. hanelerin toplamının 7 katından, 2, 4, 6, 8. hanelerin toplamı çıkartıldığında,
        // elde edilen sonucun 10'a bölümünden kalan, 10. haneyi vermelidir.
        $odd_sum = ($digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8]) * 7;
        $even_sum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        
        if (($odd_sum - $even_sum) % 10 != $digits[9]) {
            return false;
        }

        // İlk 10 hanenin toplamının 10'a bölümünden kalan, son haneyi vermelidir.
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $digits[$i];
        }
        
        if ($sum % 10 != $digits[10]) {
            return false;
        }

        return true;
    }

    /**
     * TC Kimlik numarasının benzersiz olup olmadığını kontrol eder
     *
     * @param string $tc_identity TC Kimlik numarası
     * @param int    $exclude_id  Hariç tutulacak müşteri ID (güncelleme için)
     * @return bool
     */
    private function tc_identity_exists($tc_identity, $exclude_id = 0) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE tc_identity = %s",
            $tc_identity
        );

        if ($exclude_id > 0) {
            $sql .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        return (bool) $wpdb->get_var($sql);
    }

    /**
     * Müşteri aktivitelerini loglar
     *
     * @param int    $customer_id Müşteri ID
     * @param string $action      İşlem türü
     * @param string $message     Log mesajı
     */
    private function log_activity($customer_id, $action, $message) {
        $current_user = wp_get_current_user();
        
        $log = array(
            'post_title' => sprintf(
                __('Müşteri %s - %s', 'insurance-crm'),
                $action,
                current_time('mysql')
            ),
            'post_content' => $message,
            'post_type' => 'insurance_crm_log',
            'post_status' => 'publish',
            'post_author' => $current_user->ID
        );

        $log_id = wp_insert_post($log);

        if ($log_id) {
            add_post_meta($log_id, '_customer_id', $customer_id);
            add_post_meta($log_id, '_action_type', $action);
        }
    }
}