<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Insurance CRM Policy Model
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes/models
 * @author     Anadolu Birlik
 */
class Insurance_CRM_Policy {
    /**
     * The table name for policies
     *
     * @access   private
     * @var      string    $table_name    The table name
     */
    private $table_name;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'insurance_crm_policies';
    }

    /**
     * Handle document upload
     *
     * @param array $file $_FILES array
     * @return string|false File URL if successful, false otherwise
     */
    public function handle_document_upload($file) {
        if (!empty($file['name'])) {
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/insurance-documents/';
            
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }
            
            $file_name = sanitize_file_name($file['name']);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                return $upload_dir['baseurl'] . '/insurance-documents/' . $file_name;
            }
        }
        return false;
    }

    /**
     * Create a new policy
     *
     * @param array $data Policy data
     * @return int|false The number of rows inserted, or false on error
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'customer_id' => 0,
            'policy_number' => '',
            'policy_type' => '',
            'start_date' => current_time('mysql'),
            'end_date' => '',
            'premium_amount' => 0,
            'status' => 'aktif',
            'document_path' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert($this->table_name, $data);
    }

    /**
     * Update a policy
     *
     * @param int $id Policy ID
     * @param array $data Policy data
     * @return int|false The number of rows updated, or false on error
     */
    public function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
    }

    /**
     * Get a single policy
     *
     * @param int $id Policy ID
     * @return object|null Database query result
     */
    public function get($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.first_name, c.last_name, c.tc_identity, c.email, c.phone
             FROM {$this->table_name} p
             LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
             WHERE p.id = %d",
            $id
        ));
    }

    /**
     * Get all policies
     *
     * @param array $args Query arguments
     * @return array Array of policies
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'customer_id' => null,
            'status' => null,
            'policy_type' => null,
            'orderby' => 'p.id',
            'order' => 'DESC',
            'limit' => 10,
            'offset' => 0,
            'search' => '',
            'date_range' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $where_values = array();
        
        if ($args['customer_id']) {
            $where[] = 'p.customer_id = %d';
            $where_values[] = $args['customer_id'];
        }
        
        if ($args['status']) {
            $where[] = 'p.status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['policy_type']) {
            $where[] = 'p.policy_type = %s';
            $where_values[] = $args['policy_type'];
        }
        
        if ($args['search']) {
            $where[] = '(p.policy_number LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s OR c.tc_identity LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term));
        }
        
        if (!empty($args['date_range'])) {
            if (!empty($args['date_range']['start'])) {
                $where[] = 'p.start_date >= %s';
                $where_values[] = $args['date_range']['start'];
            }
            if (!empty($args['date_range']['end'])) {
                $where[] = 'p.end_date <= %s';
                $where_values[] = $args['date_range']['end'];
            }
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = $wpdb->prepare(
            "SELECT p.*, c.first_name, c.last_name, c.tc_identity, c.email, c.phone
             FROM {$this->table_name} p
             LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
             {$where_clause}
             ORDER BY {$args['orderby']} {$args['order']}
             LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['limit'], $args['offset']))
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Count total policies
     *
     * @param array $args Query arguments
     * @return int Total number of policies
     */
    public function count_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'customer_id' => null,
            'status' => null,
            'policy_type' => null,
            'search' => '',
            'date_range' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $where_values = array();
        
        if ($args['customer_id']) {
            $where[] = 'p.customer_id = %d';
            $where_values[] = $args['customer_id'];
        }
        
        if ($args['status']) {
            $where[] = 'p.status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['policy_type']) {
            $where[] = 'p.policy_type = %s';
            $where_values[] = $args['policy_type'];
        }
        
        if ($args['search']) {
            $where[] = '(p.policy_number LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s OR c.tc_identity LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term));
        }
        
        if (!empty($args['date_range'])) {
            if (!empty($args['date_range']['start'])) {
                $where[] = 'p.start_date >= %s';
                $where_values[] = $args['date_range']['start'];
            }
            if (!empty($args['date_range']['end'])) {
                $where[] = 'p.end_date <= %s';
                $where_values[] = $args['date_range']['end'];
            }
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} p
             LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
             {$where_clause}",
            $where_values
        );
        
        return (int) $wpdb->get_var($query);
    }
}