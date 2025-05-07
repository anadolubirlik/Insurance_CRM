<?php
/**
 * Ana eklenti sınıfı
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 * @author     Anadolu Birlik
 * @since      1.0.0
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM {
    /**
     * Eklenti loader'ı
     *
     * @since    1.0.0
     * @access   protected
     * @var      Insurance_CRM_Loader    $loader    Tüm hooks için loader
     */
    protected $loader;

    /**
     * Eklentinin adı
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    Eklentinin adı
     */
    protected $plugin_name;

    /**
     * Eklentinin sürümü
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    Eklentinin sürümü
     */
    protected $version;

    /**
     * Sınıfı başlat ve gerekli özellikleri tanımla
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('INSURANCE_CRM_VERSION')) {
            $this->version = INSURANCE_CRM_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'insurance-crm';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Gerekli bağımlılıkları yükle
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-insurance-crm-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-insurance-crm-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-insurance-crm-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-insurance-crm-customer.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-insurance-crm-policy.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-insurance-crm-task.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/models/class-insurance-crm-reports.php';

        $this->loader = new Insurance_CRM_Loader();
    }

    /**
     * Eklentinin dil ayarlarını yap
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Insurance_CRM_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Admin tarafı için gerekli hook'ları tanımla
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Insurance_CRM_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Eklentiyi çalıştır
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Eklentinin adını getir
     *
     * @since     1.0.0
     * @return    string    Eklentinin adı
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Loader sınıfını getir
     *
     * @since     1.0.0
     * @return    Insurance_CRM_Loader    Loader sınıfı
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Eklentinin sürümünü getir
     *
     * @since     1.0.0
     * @return    string    Eklentinin sürümü
     */
    public function get_version() {
        return $this->version;
    }
}