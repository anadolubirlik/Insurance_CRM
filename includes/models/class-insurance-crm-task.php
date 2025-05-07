<?php
/**
 * Görev model sınıfı
 *
 * @package     Insurance_CRM
 * @subpackage  Models
 * @author      Anadolu Birlik
 * @since       1.0.0 (2025-05-02)
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM_Task {
    /**
     * Veritabanı tablosu
     *
     * @var string
     */
    private $table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'insurance_crm_tasks';
    }

    /**
     * Yeni görev ekler
     *
     * @param array $data Görev verileri
     * @return int|WP_Error Eklenen görev ID'si veya hata
     */
    public function add($data) {
        global $wpdb;

        // Müşteri kontrolü
        $customer = new Insurance_CRM_Customer();
        if (!$customer->get($data['customer_id'])) {
            return new WP_Error('invalid_customer', __('Geçersiz müşteri.', 'insurance-crm'));
        }

        // Poliçe kontrolü (eğer belirtilmişse)
        if (!empty($data['policy_id'])) {
            $policy = new Insurance_CRM_Policy();
            if (!$policy->get($data['policy_id'])) {
                return new WP_Error('invalid_policy', __('Geçersiz poliçe.', 'insurance-crm'));
            }
        }

        $defaults = array(
            'priority' => 'medium',
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $data = wp_parse_args($data, $defaults);

        // Veri temizleme
        $data = $this->sanitize_task_data($data);

        $inserted = $wpdb->insert(
            $this->table,
            $data,
            array(
                '%d', // customer_id
                '%d', // policy_id
                '%s', // task_description
                '%s', // due_date
                '%s', // priority
                '%s', // status
                '%s', // created_at
                '%s'  // updated_at
            )
        );

        if ($inserted) {
            $task_id = $wpdb->insert_id;

            // Görev hatırlatması için e-posta gönder
            $this->send_task_notification($task_id, 'new');

            // Aktivite logu
            $this->log_activity($task_id, 'create', sprintf(
                __('Yeni görev eklendi: %s', 'insurance-crm'),
                wp_trim_words($data['task_description'], 10)
            ));

            return $task_id;
        }

        return new WP_Error('db_insert_error', __('Görev eklenirken bir hata oluştu.', 'insurance-crm'));
    }

    /**
     * Görev günceller
     *
     * @param int   $id   Görev ID
     * @param array $data Güncellenecek veriler
     * @return bool|WP_Error
     */
    public function update($id, $data) {
        global $wpdb;

        // Mevcut görev kontrolü
        $current = $this->get($id);
        if (!$current) {
            return new WP_Error('not_found', __('Görev bulunamadı.', 'insurance-crm'));
        }

        $data['updated_at'] = current_time('mysql');

        // Veri temizleme
        $data = $this->sanitize_task_data($data);

        $updated = $wpdb->update(
            $this->table,
            $data,
            array('id' => $id),
            array(
                '%d', // customer_id
                '%d', // policy_id
                '%s', // task_description
                '%s', // due_date
                '%s', // priority
                '%s', // status
                '%s'  // updated_at
            ),
            array('%d')
        );

        if ($updated !== false) {
            // Görev durumu değiştiyse bildirim gönder
            if (isset($data['status']) && $data['status'] !== $current->status) {
                $this->send_task_notification($id, 'status_change');
            }

            // Aktivite logu
            $this->log_activity($id, 'update', sprintf(
                __('Görev güncellendi: %s', 'insurance-crm'),
                wp_trim_words($data['task_description'], 10)
            ));

            return true;
        }

        return new WP_Error('db_update_error', __('Görev güncellenirken bir hata oluştu.', 'insurance-crm'));
    }

    /**
     * Görev siler
     *
     * @param int $id Görev ID
     * @return bool|WP_Error
     */
    public function delete($id) {
        global $wpdb;

        // Mevcut görev kontrolü
        $task = $this->get($id);
        if (!$task) {
            return new WP_Error('not_found', __('Görev bulunamadı.', 'insurance-crm'));
        }

        $deleted = $wpdb->delete(
            $this->table,
            array('id' => $id),
            array('%d')
        );

        if ($deleted) {
            // Aktivite logu
            $this->log_activity($id, 'delete', sprintf(
                __('Görev silindi: %s', 'insurance-crm'),
                wp_trim_words($task->task_description, 10)
            ));

            return true;
        }

        return new WP_Error('db_delete_error', __('Görev silinirken bir hata oluştu.', 'insurance-crm'));
    }

    /**
     * Görev getirir
     *
     * @param int $id Görev ID
     * @return object|null
     */
    public function get($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, c.first_name, c.last_name, c.email, p.policy_number 
            FROM {$this->table} t 
            LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON t.customer_id = c.id 
            LEFT JOIN {$wpdb->prefix}insurance_crm_policies p ON t.policy_id = p.id 
            WHERE t.id = %d",
            $id
        ));
    }

    /**
     * Tüm görevleri getirir
     *
     * @param array $args Filtre parametreleri
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'customer_id' => 0,
            'policy_id' => 0,
            'status' => '',
            'priority' => '',
            'due_date_start' => '',
            'due_date_end' => '',
            'search' => '',
            'orderby' => 'due_date',
            'order' => 'ASC',
            'limit' => 0,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        $where = array('1=1');
        $values = array();

        if (!empty($args['customer_id'])) {
            $where[] = 't.customer_id = %d';
            $values[] = $args['customer_id'];
        }

        if (!empty($args['policy_id'])) {
            $where[] = 't.policy_id = %d';
            $values[] = $args['policy_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 't.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['priority'])) {
            $where[] = 't.priority = %s';
            $values[] = $args['priority'];
        }

        if (!empty($args['due_date_start'])) {
            $where[] = 't.due_date >= %s';
            $values[] = $args['due_date_start'];
        }

        if (!empty($args['due_date_end'])) {
            $where[] = 't.due_date <= %s';
            $values[] = $args['due_date_end'];
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(t.task_description LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s)';
            $values = array_merge($values, array($search, $search, $search));
        }

        $sql = "SELECT t.*, c.first_name, c.last_name, c.email, p.policy_number 
                FROM {$wpdb->prefix}insurance_crm_tasks t 
                LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON t.customer_id = c.id 
                LEFT JOIN {$wpdb->prefix}insurance_crm_policies p ON t.policy_id = p.id 
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
     * Görev verilerini temizler
     *
     * @param array $data Görev verileri
     * @return array
     */
    private function sanitize_task_data($data) {
        $clean = array();

        if (isset($data['customer_id'])) {
            $clean['customer_id'] = absint($data['customer_id']);
        }

        if (isset($data['policy_id'])) {
            $clean['policy_id'] = absint($data['policy_id']);
        }

        if (isset($data['task_description'])) {
            $clean['task_description'] = sanitize_textarea_field($data['task_description']);
        }

        if (isset($data['due_date'])) {
            $clean['due_date'] = sanitize_text_field($data['due_date']);
        }

        if (isset($data['priority'])) {
            $clean['priority'] = sanitize_text_field($data['priority']);
        }

        if (isset($data['status'])) {
            $clean['status'] = sanitize_text_field($data['status']);
        }

        return $clean;
    }

    /**
     * Görev bildirimi gönderir
     *
     * @param int    $task_id Görev ID
     * @param string $type    Bildirim türü (new/status_change)
     */
    private function send_task_notification($task_id, $type) {
        $task = $this->get($task_id);
        if (!$task) {
            return;
        }

        $settings = get_option('insurance_crm_settings');
        $to = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');

        switch ($type) {
            case 'new':
                $subject = sprintf(
                    __('[%s] Yeni Görev: %s', 'insurance-crm'),
                    $company_name,
                    wp_trim_words($task->task_description, 5)
                );
                $message = sprintf(
                    __("Yeni görev oluşturuldu:\n\nGörev: %s\nMüşteri: %s %s\nSon Tarih: %s\nÖncelik: %s\n\nGörevi görüntülemek için: %s", 'insurance-crm'),
                    $task->task_description,
                    $task->first_name,
                    $task->last_name,
                    date_i18n('d.m.Y H:i', strtotime($task->due_date)),
                    $task->priority,
                    admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task_id)
                );
                break;

            case 'status_change':
                $subject = sprintf(
                    __('[%s] Görev Durumu Güncellendi: %s', 'insurance-crm'),
                    $company_name,
                    wp_trim_words($task->task_description, 5)
                );
                $message = sprintf(
                    __("Görev durumu güncellendi:\n\nGörev: %s\nMüşteri: %s %s\nSon Tarih: %s\nYeni Durum: %s\n\nGörevi görüntülemek için: %s", 'insurance-crm'),
                    $task->task_description,
                    $task->first_name,
                    $task->last_name,
                    date_i18n('d.m.Y H:i', strtotime($task->due_date)),
                    $task->status,
                    admin_url('admin.php?page=insurance-crm-tasks&action=edit&id=' . $task_id)
                );
                break;
        }

        wp_mail(
            $to,
            $subject,
            $message,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    /**
     * Görev aktivitelerini loglar
     *
     * @param int    $task_id Görev ID
     * @param string $action  İşlem türü
     * @param string $message Log mesajı
     */
    private function log_activity($task_id, $action, $message) {
        $current_user = wp_get_current_user();
        
        $log = array(
            'post_title' => sprintf(
                __('Görev %s - %s', 'insurance-crm'),
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
            add_post_meta($log_id, '_task_id', $task_id);
            add_post_meta($log_id, '_action_type', $action);
        }
    }
}