<?php
function insurance_crm_frontend_dashboard() {
    if(!is_user_logged_in()) {
        wp_redirect(home_url('/temsilci-girisi'));
        exit;
    }

    $current_user = wp_get_current_user();
    $rep_id = get_user_meta($current_user->ID, '_insurance_representative_id', true);

    global $wpdb;
    $customers_table = $wpdb->prefix . 'insurance_crm_customers';
    $policies_table = $wpdb->prefix . 'insurance_crm_policies';
    $tasks_table = $wpdb->prefix . 'insurance_crm_tasks';
    $rep_table = $wpdb->prefix . 'insurance_crm_representatives';

    // Temsilcinin bilgilerini al
    $rep_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $rep_table WHERE id = %d",
        $rep_id
    ));

    // Bu ayki toplam üretimi hesapla
    $current_month = date('Y-m');
    $monthly_production = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(premium_amount) 
         FROM $policies_table 
         WHERE representative_id = %d 
         AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
        $rep_id,
        $current_month
    ));

    // Hedef yüzdesini hesapla
    $target_percentage = 0;
    if($rep_info->monthly_target > 0) {
        $target_percentage = min(100, ($monthly_production / $rep_info->monthly_target) * 100);
    }

    ob_start();
    ?>
    <div class="insurance-crm-dashboard">
        <div class="dashboard-header">
            <h2>Hoş Geldiniz, <?php echo esc_html($current_user->display_name); ?></h2>
            <a href="<?php echo wp_logout_url(home_url('/temsilci-girisi')); ?>" class="logout-button">Çıkış Yap</a>
        </div>

        <div class="target-progress">
            <h3>Aylık Hedef Durumu</h3>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo $target_percentage; ?>%"></div>
            </div>
            <div class="progress-info">
                <span>Hedef: ₺<?php echo number_format($rep_info->monthly_target, 2); ?></span>
                <span>Gerçekleşen: ₺<?php echo number_format($monthly_production, 2); ?></span>
                <span>Tamamlanma: %<?php echo number_format($target_percentage, 1); ?></span>
            </div>
        </div>

        <div class="dashboard-tabs">
            <button class="tab-button active" data-tab="customers">Müşteriler</button>
            <button class="tab-button" data-tab="policies">Poliçeler</button>
            <button class="tab-button" data-tab="tasks">Görevler</button>
        </div>

        <div class="tab-content active" id="customers">
            <h3>Müşterilerim</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $customers = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $customers_table WHERE representative_id = %d",
                        $rep_id
                    ));
                    foreach($customers as $customer): ?>
                    <tr>
                        <td><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></td>
                        <td><?php echo esc_html($customer->phone); ?></td>
                        <td><?php echo esc_html($customer->email); ?></td>
                        <td><?php echo esc_html($customer->status); ?></td>
                        <td>
                            <button class="edit-customer" data-id="<?php echo $customer->id; ?>">Düzenle</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button id="add-customer" class="add-button">Yeni Müşteri Ekle</button>
        </div>

        <div class="tab-content" id="policies">
            <h3>Poliçeler</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Poliçe No</th>
                        <th>Müşteri</th>
                        <th>Tür</th>
                        <th>Tutar</th>
                        <th>Bitiş Tarihi</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $policies = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.*, c.first_name, c.last_name 
                         FROM $policies_table p
                         JOIN $customers_table c ON p.customer_id = c.id
                         WHERE p.representative_id = %d",
                        $rep_id
                    ));
                    foreach($policies as $policy): ?>
                    <tr>
                        <td><?php echo esc_html($policy->policy_number); ?></td>
                        <td><?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?></td>
                        <td><?php echo esc_html($policy->policy_type); ?></td>
                        <td>₺<?php echo number_format($policy->premium_amount, 2); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($policy->end_date)); ?></td>
                        <td><?php echo esc_html($policy->status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-content" id="tasks">
            <h3>Görevlerim</h3>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Görev</th>
                        <th>Müşteri</th>
                        <th>Son Tarih</th>
                        <th>Öncelik</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tasks = $wpdb->get_results($wpdb->prepare(
                        "SELECT t.*, c.first_name, c.last_name 
                         FROM $tasks_table t
                         JOIN $customers_table c ON t.customer_id = c.id
                         WHERE t.representative_id = %d",
                        $rep_id
                    ));
                    foreach($tasks as $task): ?>
                    <tr>
                        <td><?php echo esc_html($task->task_description); ?></td>
                        <td><?php echo esc_html($task->first_name . ' ' . $task->last_name); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($task->due_date)); ?></td>
                        <td><?php echo esc_html($task->priority); ?></td>
                        <td><?php echo esc_html($task->status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .insurance-crm-dashboard {
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .logout-button {
            padding: 8px 15px;
            background: #dc3545;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .target-progress {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .progress-bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress {
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease;
        }
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .dashboard-tabs {
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 10px 20px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            margin-right: 5px;
        }
        .tab-button.active {
            background: #0073aa;
            color: #fff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .dashboard-table th,
        .dashboard-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        .dashboard-table th {
            background: #f8f9fa;
        }
        .add-button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Tab işlevselliği
        $('.tab-button').click(function() {
            $('.tab-button').removeClass('active');
            $('.tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + $(this).data('tab')).addClass('active');
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('insurance_crm_dashboard', 'insurance_crm_frontend_dashboard');