<?php
/**
 * Uluslararasılaştırma sınıfı
 *
 * @package    Insurance_CRM
 * @subpackage Insurance_CRM/includes
 * @author     Anadolu Birlik
 * @since      1.0.0
 */

class Insurance_CRM_i18n {
    /**
     * Eklentinin metinlerini yükler
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'insurance-crm',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}