<?php
/**
 * Raporlama model sınıfı
 *
 * @package     Insurance_CRM
 * @subpackage  Models
 * @author      Anadolu Birlik
 * @since       1.0.0 (2025-05-02)
 */

if (!defined('WPINC')) {
    die;
}

class Insurance_CRM_Reports {
    /**
     * Özet istatistikleri getirir
     *
     * @param string $start_date Başlangıç tarihi
     * @param string $end_date   Bitiş tarihi
     * @param string $policy_type Poliçe türü (opsiyonel)
     * @return object
     */
    public static function get_summary_stats($start_date, $end_date, $policy_type = '') {
        global $wpdb;

        $where = array("DATE(p.created_at) BETWEEN %s AND %s");
        $values = array($start_date, $end_date);

        if (!empty($policy_type)) {
            $where[] = "p.policy_type = %s";
            $values[] = $policy_type;
        }

        $where_clause = implode(' AND ', $where);

        // Toplam poliçe sayısı
        $total_policies = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_policies p WHERE {$where_clause}",
            $values
        ));

        // Toplam prim tutarı
        $total_premium = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(premium_amount) FROM {$wpdb->prefix}insurance_crm_policies p WHERE {$where_clause}",
            $values
        ));

        // Yeni müşteri sayısı
        $new_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}insurance_crm_customers WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Yenileme oranı hesaplama
        $renewal_stats = self::calculate_renewal_rate($start_date, $end_date, $policy_type);

        // Poliçe türü dağılımı
        $type_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                policy_type,
                COUNT(*) as count,
                CASE 
                    WHEN policy_type = 'trafik' THEN '" . __('Trafik Sigortası', 'insurance-crm') . "'
                    WHEN policy_type = 'kasko' THEN '" . __('Kasko', 'insurance-crm') . "'
                    WHEN policy_type = 'konut' THEN '" . __('Konut Sigortası', 'insurance-crm') . "'
                    WHEN policy_type = 'dask' THEN '" . __('DASK', 'insurance-crm') . "'
                    WHEN policy_type = 'saglik' THEN '" . __('Sağlık Sigortası', 'insurance-crm') . "'
                    WHEN policy_type = 'hayat' THEN '" . __('Hayat Sigortası', 'insurance-crm') . "'
                    ELSE policy_type
                END as label
            FROM {$wpdb->prefix}insurance_crm_policies p 
            WHERE {$where_clause}
            GROUP BY policy_type
            ORDER BY count DESC",
            $values
        ));

        // Aylık prim dağılımı
        $monthly_premium = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(premium_amount) as amount
            FROM {$wpdb->prefix}insurance_crm_policies p 
            WHERE {$where_clause}
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC",
            $values
        ));

        // Türkçe ay isimleri
        $months_tr = array(
            '01' => 'Ocak',
            '02' => 'Şubat',
            '03' => 'Mart',
            '04' => 'Nisan',
            '05' => 'Mayıs',
            '06' => 'Haziran',
            '07' => 'Temmuz',
            '08' => 'Ağustos',
            '09' => 'Eylül',
            '10' => 'Ekim',
            '11' => 'Kasım',
            '12' => 'Aralık'
        );

        // Ay isimlerini Türkçeleştir
        foreach ($monthly_premium as $data) {
            $month_parts = explode('-', $data->month);
            $data->month = $months_tr[$month_parts[1]] . ' ' . $month_parts[0];
        }

        return (object) array(
            'total_policies' => $total_policies,
            'total_premium' => $total_premium,
            'new_customers' => $new_customers,
            'renewal_rate' => $renewal_stats->renewal_rate,
            'policy_type_distribution' => $type_distribution,
            'monthly_premium_distribution' => $monthly_premium
        );
    }

    /**
     * Yenileme oranını hesaplar
     *
     * @param string $start_date  Başlangıç tarihi
     * @param string $end_date    Bitiş tarihi
     * @param string $policy_type Poliçe türü (opsiyonel)
     * @return object
     */
    private static function calculate_renewal_rate($start_date, $end_date, $policy_type = '') {
        global $wpdb;

        $where = array("DATE(end_date) BETWEEN %s AND %s");
        $values = array($start_date, $end_date);

        if (!empty($policy_type)) {
            $where[] = "policy_type = %s";
            $values[] = $policy_type;
        }

        $where_clause = implode(' AND ', $where);

        // Süresi biten poliçeleri bul
        $expired_policies = $wpdb->get_results($wpdb->prepare(
            "SELECT id, customer_id, policy_type, end_date 
            FROM {$wpdb->prefix}insurance_crm_policies 
            WHERE {$where_clause}",
            $values
        ));

        $total_expired = count($expired_policies);
        $renewed = 0;

        if ($total_expired > 0) {
            foreach ($expired_policies as $policy) {
                // Müşterinin aynı türde yeni poliçesi var mı kontrol et
                $has_renewal = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$wpdb->prefix}insurance_crm_policies 
                    WHERE customer_id = %d 
                    AND policy_type = %s 
                    AND start_date > %s",
                    $policy->customer_id,
                    $policy->policy_type,
                    $policy->end_date
                ));

                if ($has_renewal > 0) {
                    $renewed++;
                }
            }
        }

        $renewal_rate = $total_expired > 0 ? ($renewed / $total_expired) * 100 : 0;

        return (object) array(
            'total_expired' => $total_expired,
            'renewed' => $renewed,
            'renewal_rate' => $renewal_rate
        );
    }

    /**
     * Detaylı poliçe listesini getirir
     *
     * @param string $start_date  Başlangıç tarihi
     * @param string $end_date    Bitiş tarihi
     * @param string $policy_type Poliçe türü (opsiyonel)
     * @return array
     */
    public static function get_detailed_policies($start_date, $end_date, $policy_type = '') {
        global $wpdb;

        $where = array("DATE(p.created_at) BETWEEN %s AND %s");
        $values = array($start_date, $end_date);

        if (!empty($policy_type)) {
            $where[] = "p.policy_type = %s";
            $values[] = $policy_type;
        }

        $where_clause = implode(' AND ', $where);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.*,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.email as customer_email,
                c.phone as customer_phone
            FROM {$wpdb->prefix}insurance_crm_policies p
            LEFT JOIN {$wpdb->prefix}insurance_crm_customers c ON p.customer_id = c.id
            WHERE {$where_clause}
            ORDER BY p.created_at DESC",
            $values
        ));
    }

    /**
     * Görev istatistiklerini getirir
     *
     * @param string $start_date Başlangıç tarihi
     * @param string $end_date   Bitiş tarihi
     * @return object
     */
    public static function get_task_stats($start_date, $end_date) {
        global $wpdb;

        // Durum bazında görev sayıları
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as count,
                CASE 
                    WHEN status = 'pending' THEN '" . __('Bekleyen', 'insurance-crm') . "'
                    WHEN status = 'in_progress' THEN '" . __('Devam Eden', 'insurance-crm') . "'
                    WHEN status = 'completed' THEN '" . __('Tamamlanan', 'insurance-crm') . "'
                    WHEN status = 'cancelled' THEN '" . __('İptal', 'insurance-crm') . "'
                    ELSE status
                END as label
            FROM {$wpdb->prefix}insurance_crm_tasks
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY status",
            $start_date,
            $end_date
        ));

        // Öncelik bazında görev sayıları
        $priority_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                priority,
                COUNT(*) as count,
                CASE 
                    WHEN priority = 'low' THEN '" . __('Düşük', 'insurance-crm') . "'
                    WHEN priority = 'medium' THEN '" . __('Orta', 'insurance-crm') . "'
                    WHEN priority = 'high' THEN '" . __('Yüksek', 'insurance-crm') . "'
                    ELSE priority
                END as label
            FROM {$wpdb->prefix}insurance_crm_tasks
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY priority",
            $start_date,
            $end_date
        ));

        // Geçen görevler
        $overdue_tasks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}insurance_crm_tasks
            WHERE status IN ('pending', 'in_progress')
            AND due_date < %s",
            current_time('mysql')
        ));

        // Ortalama tamamlanma süresi (saat)
        $avg_completion_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) 
            FROM {$wpdb->prefix}insurance_crm_tasks
            WHERE status = 'completed'
            AND DATE(created_at) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        return (object) array(
            'status_distribution' => $status_counts,
            'priority_distribution' => $priority_counts,
            'overdue_tasks' => $overdue_tasks,
            'avg_completion_time' => round($avg_completion_time, 1)
        );
    }

    /**
     * Müşteri istatistiklerini getirir
     *
     * @param string $start_date Başlangıç tarihi
     * @param string $end_date   Bitiş tarihi
     * @return object
     */
    public static function get_customer_stats($start_date, $end_date) {
        global $wpdb;

        // Toplam müşteri sayısı
        $total_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}insurance_crm_customers
            WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Kategori bazında müşteri dağılımı
        $category_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                category,
                COUNT(*) as count,
                CASE 
                    WHEN category = 'bireysel' THEN '" . __('Bireysel', 'insurance-crm') . "'
                    WHEN category = 'kurumsal' THEN '" . __('Kurumsal', 'insurance-crm') . "'
                    ELSE category
                END as label
            FROM {$wpdb->prefix}insurance_crm_customers
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY category",
            $start_date,
            $end_date
        ));

        // En çok poliçesi olan müşteriler
        $top_customers = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                c.id,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                COUNT(p.id) as policy_count,
                SUM(p.premium_amount) as total_premium
            FROM {$wpdb->prefix}insurance_crm_customers c
            LEFT JOIN {$wpdb->prefix}insurance_crm_policies p ON c.id = p.customer_id
            WHERE DATE(c.created_at) BETWEEN %s AND %s
            GROUP BY c.id
            ORDER BY policy_count DESC, total_premium DESC
            LIMIT 10",
            $start_date,
            $end_date
        ));

        return (object) array(
            'total_customers' => $total_customers,
            'category_distribution' => $category_distribution,
            'top_customers' => $top_customers
        );
    }
}