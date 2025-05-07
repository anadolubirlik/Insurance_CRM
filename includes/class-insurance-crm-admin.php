<?php
/**
 * Admin sınıfı
 *
 * @package     Insurance_CRM
 * @subpackage  Admin
 * @author      Anadolu Birlik
 * @since       1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM_Admin {
    /**
     * Plugin adı
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin sürümü
     *
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @param string $plugin_name
     * @param string $version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_insurance_crm_get_customer_policies', array($this, 'ajax_get_customer_policies'));
        add_action('wp_ajax_insurance_crm_test_email', array($this, 'ajax_test_email'));
        
        // Form handlers
        add_action('admin_post_insurance_crm_save_customer', array($this, 'handle_save_customer'));
        add_action('admin_post_insurance_crm_save_policy', array($this, 'handle_save_policy'));
        add_action('admin_post_insurance_crm_save_task', array($this, 'handle_save_task'));
    }

    /**
     * Admin menülerini ekler
     */
    public function add_menu_pages() {
        add_menu_page(
            __('Insurance CRM', 'insurance-crm'),
            __('Insurance CRM', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm',
            array($this, 'display_dashboard_page'),
            'dashicons-businessman',
            30
        );

        add_submenu_page(
            'insurance-crm',
            __('Gösterge Paneli', 'insurance-crm'),
            __('Gösterge Paneli', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm',
            array($this, 'display_dashboard_page')
        );

        add_submenu_page(
            'insurance-crm',
            __('Müşteriler', 'insurance-crm'),
            __('Müşteriler', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm-customers',
            array($this, 'display_customers_page')
        );

        add_submenu_page(
            'insurance-crm',
            __('Poliçeler', 'insurance-crm'),
            __('Poliçeler', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm-policies',
            array($this, 'display_policies_page')
        );

        add_submenu_page(
            'insurance-crm',
            __('Görevler', 'insurance-crm'),
            __('Görevler', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm-tasks',
            array($this, 'display_tasks_page')
        );

        add_submenu_page(
            'insurance-crm',
            __('Raporlar', 'insurance-crm'),
            __('Raporlar', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm-reports',
            array($this, 'display_reports_page')
        );

        add_submenu_page(
            'insurance-crm',
            __('Ayarlar', 'insurance-crm'),
            __('Ayarlar', 'insurance-crm'),
            'manage_insurance_crm',
            'insurance-crm-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Admin stil dosyalarını ekler
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'insurance-crm') !== false) {
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url(dirname(__FILE__)) . 'admin/css/insurance-crm-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Admin script dosyalarını ekler
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'insurance-crm') !== false) {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(dirname(__FILE__)) . 'admin/js/insurance-crm-admin.js',
                array('jquery'),
                $this->version,
                false
            );

            wp_localize_script(
                $this->plugin_name,
                'insurance_crm_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('insurance_crm_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('Bu kaydı silmek istediğinizden emin misiniz?', 'insurance-crm'),
                        'error' => __('Bir hata oluştu!', 'insurance-crm'),
                        'success' => __('İşlem başarıyla tamamlandı.', 'insurance-crm')
                    )
                )
            );
        }
    }

    /**
     * Gösterge paneli sayfasını görüntüler
     */
    public function display_dashboard_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-dashboard.php';
    }

    /**
     * Müşteriler sayfasını görüntüler
     */
    public function display_customers_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-customers.php';
    }

    /**
     * Poliçeler sayfasını görüntüler
     */
    public function display_policies_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-policies.php';
    }

    /**
     * Görevler sayfasını görüntüler
     */
    public function display_tasks_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-tasks.php';
    }

    /**
     * Raporlar sayfasını görüntüler
     */
    public function display_reports_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-reports.php';
    }

    /**
     * Ayarlar sayfasını görüntüler
     */
    public function display_settings_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/insurance-crm-admin-settings.php';
    }

    /**
     * Müşteriyle ilişkili poliçeleri getirir (AJAX)
     */
    public function ajax_get_customer_policies() {
        check_ajax_referer('insurance_crm_get_policies', 'nonce');

        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if (!$customer_id) {
            wp_send_json_error(__('Geçersiz müşteri.', 'insurance-crm'));
        }

        $policy = new Insurance_CRM_Policy();
        $policies = $policy->get_all(array('customer_id' => $customer_id));

        $options = '<option value="">' . __('Poliçe Seçin', 'insurance-crm') . '</option>';
        foreach ($policies as $policy) {
            $options .= sprintf(
                '<option value="%d">%s - %s</option>',
                $policy->id,
                esc_html($policy->policy_number),
                esc_html($policy->policy_type)
            );
        }

        wp_send_json_success($options);
    }

    /**
     * Test e-postası gönderir (AJAX)
     */
    public function ajax_test_email() {
        check_ajax_referer('insurance_crm_test_email', 'nonce');

        $settings = get_option('insurance_crm_settings');
        $to = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');
        $subject = sprintf(__('[%s] Test E-postası', 'insurance-crm'), get_bloginfo('name'));
        $message = __('Bu bir test e-postasıdır. E-posta ayarlarınız doğru çalışıyor.', 'insurance-crm');

        $sent = wp_mail($to, $subject, $message);

        if ($sent) {
            wp_send_json_success(__('Test e-postası başarıyla gönderildi.', 'insurance-crm'));
        } else {
            wp_send_json_error(__('Test e-postası gönderilemedi!', 'insurance-crm'));
        }
    }

    /**
     * Müşteri kaydetme işlemini yönetir
     */
    public function handle_save_customer() {
        if (!current_user_can('manage_insurance_crm')) {
            wp_die(__('Bu işlem için yetkiniz bulunmuyor.', 'insurance-crm'));
        }

        check_admin_referer('insurance_crm_save_customer', 'insurance_crm_nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $customer = new Insurance_CRM_Customer();

        $data = array(
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'tc_identity' => $_POST['tc_identity'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'category' => $_POST['category'],
            'status' => $_POST['status']
        );

        if ($id > 0) {
            $result = $customer->update($id, $data);
        } else {
            $result = $customer->add($data);
        }

        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'insurance-crm-customers',
                    'action' => $id ? 'edit' : 'new',
                    'id' => $id,
                    'error' => urlencode($result->get_error_message())
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'insurance-crm-customers',
                'message' => $id ? 'updated' : 'added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Poliçe kaydetme işlemini yönetir
     */
    public function handle_save_policy() {
        if (!current_user_can('manage_insurance_crm')) {
            wp_die(__('Bu işlem için yetkiniz bulunmuyor.', 'insurance-crm'));
        }

        check_admin_referer('insurance_crm_save_policy', 'insurance_crm_nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $policy = new Insurance_CRM_Policy();

        $data = array(
            'customer_id' => $_POST['customer_id'],
            'policy_number' => $_POST['policy_number'],
            'policy_type' => $_POST['policy_type'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'premium_amount' => $_POST['premium_amount'],
            'status' => $_POST['status']
        );

        if ($id > 0) {
            $result = $policy->update($id, $data);
        } else {
            $result = $policy->add($data);
        }

        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'insurance-crm-policies',
                    'action' => $id ? 'edit' : 'new',
                    'id' => $id,
                    'error' => urlencode($result->get_error_message())
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'insurance-crm-policies',
                'message' => $id ? 'updated' : 'added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Görev kaydetme işlemini yönetir
     */
    public function handle_save_task() {
        if (!current_user_can('manage_insurance_crm')) {
            wp_die(__('Bu işlem için yetkiniz bulunmuyor.', 'insurance-crm'));
        }

        check_admin_referer('insurance_crm_save_task', 'insurance_crm_nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $task = new Insurance_CRM_Task();

        $data = array(
            'customer_id' => $_POST['customer_id'],
            'policy_id' => !empty($_POST['policy_id']) ? $_POST['policy_id'] : null,
            'task_description' => $_POST['task_description'],
            'due_date' => $_POST['due_date'],
            'priority' => $_POST['priority'],
            'status' => $_POST['status']
        );

        if ($id > 0) {
            $result = $task->update($id, $data);
        } else {
            $result = $task->add($data);
        }

        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'page' => 'insurance-crm-tasks',
                    'action' => $id ? 'edit' : 'new',
                    'id' => $id,
                    'error' => urlencode($result->get_error_message())
                ),
                admin_url('admin.php')
            ));
            exit;
        }

        wp_redirect(add_query_arg(
            array(
                'page' => 'insurance-crm-tasks',
                'message' => $id ? 'updated' : 'added'
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}