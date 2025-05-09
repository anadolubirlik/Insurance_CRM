<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function insurance_crm_send_notification($to, $subject, $message, $attachments = []) {
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
    ];
    
    return wp_mail($to, $subject, $message, $headers, $attachments);
}

function insurance_crm_schedule_notifications() {
    if (!wp_next_scheduled('insurance_crm_daily_notifications')) {
        wp_schedule_event(time(), 'daily', 'insurance_crm_daily_notifications');
    }
}

function insurance_crm_send_policy_renewal_notifications() {
    global $wpdb;
    
    $policies = $wpdb->get_results("
        SELECT p.*, c.first_name, c.last_name, c.email, u.user_email as agent_email
        FROM {$wpdb->prefix}insurance_policies p
        JOIN {$wpdb->prefix}insurance_customers c ON p.customer_id = c.id
        JOIN {$wpdb->users} u ON p.created_by = u.ID
        WHERE p.status = 'aktif'
        AND p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    
    foreach ($policies as $policy) {
        // Send to customer
        $customer_subject = 'Poliçe Yenileme Hatırlatması';
        $customer_message = sprintf(
            'Sayın %s %s,<br><br>
            %s numaralı poliçenizin bitiş tarihi yaklaşıyor. Bitiş tarihi: %s<br>
            Poliçenizi yenilemek için lütfen bizimle iletişime geçin.<br><br>
            Saygılarımızla,<br>
            %s',
            $policy->first_name,
            $policy->last_name,
            $policy->policy_number,
            $policy->end_date,
            get_bloginfo('name')
        );
        insurance_crm_send_notification($policy->email, $customer_subject, $customer_message);
        
        // Send to agent
        $agent_subject = 'Poliçe Yenileme Hatırlatması - ' . $policy->policy_number;
        $agent_message = sprintf(
            '%s %s müşterisinin %s numaralı poliçesinin bitiş tarihi yaklaşıyor.<br>
            Bitiş tarihi: %s<br>
            Poliçe türü: %s<br>
            Prim tutarı: %s<br><br>
            Müşteri bilgileri:<br>
            Email: %s<br>
            Telefon: %s<br>',
            $policy->first_name,
            $policy->last_name,
            $policy->policy_number,
            $policy->end_date,
            $policy->policy_type,
            $policy->premium_amount,
            $policy->email,
            $policy->phone
        );
        insurance_crm_send_notification($policy->agent_email, $agent_subject, $agent_message);
    }
}

function insurance_crm_send_task_notifications() {
    global $wpdb;
    
    $tasks = $wpdb->get_results("
        SELECT t.*, c.first_name, c.last_name, c.email, u.user_email as agent_email,
               p.policy_number
        FROM {$wpdb->prefix}insurance_tasks t
        JOIN {$wpdb->prefix}insurance_customers c ON t.customer_id = c.id
        JOIN {$wpdb->users} u ON t.created_by = u.ID
        LEFT JOIN {$wpdb->prefix}insurance_policies p ON t.policy_id = p.id
        WHERE t.status = 'pending'
        AND DATE(t.due_date) = CURDATE()
    ");
    
    foreach ($tasks as $task) {
        $agent_subject = 'Görev Hatırlatması - ' . $task->task_description;
        $agent_message = sprintf(
            'Bugün için planlanmış göreviniz var:<br><br>
            Müşteri: %s %s<br>
            Görev: %s<br>
            %s
            Son tarih: %s<br><br>
            Müşteri bilgileri:<br>
            Email: %s',
            $task->first_name,
            $task->last_name,
            $task->task_description,
            $task->policy_number ? 'Poliçe: ' . $task->policy_number . '<br>' : '',
            $task->due_date,
            $task->email
        );
        insurance_crm_send_notification($task->agent_email, $agent_subject, $agent_message);
    }
}

// Register notification hooks
add_action('insurance_crm_daily_notifications', 'insurance_crm_send_policy_renewal_notifications');
add_action('insurance_crm_daily_notifications', 'insurance_crm_send_task_notifications');