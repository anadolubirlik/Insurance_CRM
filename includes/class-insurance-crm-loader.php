<?php
/**
 * Hook'ları yükleyen ve yöneten sınıf
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 * @author     Anadolu Birlik
 * @since      1.0.0
 */

class Insurance_CRM_Loader {
    /**
     * Eklentinin kayıtlı action'ları
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    Kayıtlı action'lar
     */
    protected $actions;

    /**
     * Eklentinin kayıtlı filter'ları
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    Kayıtlı filter'lar
     */
    protected $filters;

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Yeni bir action ekle
     *
     * @since    1.0.0
     * @param    string    $hook             Hook adı
     * @param    object    $component        Hook'u içeren nesne
     * @param    string    $callback         Çağrılacak method
     * @param    int       $priority         Öncelik
     * @param    int       $accepted_args    Kabul edilen parametre sayısı
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Yeni bir filter ekle
     *
     * @since    1.0.0
     * @param    string    $hook             Hook adı
     * @param    object    $component        Hook'u içeren nesne
     * @param    string    $callback         Çağrılacak method
     * @param    int       $priority         Öncelik
     * @param    int       $accepted_args    Kabul edilen parametre sayısı
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Hook koleksiyonuna yeni bir hook ekle
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks            Hook koleksiyonu
     * @param    string    $hook             Hook adı
     * @param    object    $component        Hook'u içeren nesne
     * @param    string    $callback         Çağrılacak method
     * @param    int       $priority         Öncelik
     * @param    int       $accepted_args    Kabul edilen parametre sayısı
     * @return   array                       Hook koleksiyonu
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Kayıtlı tüm hook'ları WordPress'e ekle
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}