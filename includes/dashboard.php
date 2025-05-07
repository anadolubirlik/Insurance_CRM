<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function insurance_crm_display_dashboard() {
    $stats = insurance_crm_get_dashboard_statistics();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Insurance CRM Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            <!-- Policy Stats -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Poliçe İstatistikleri</h3>
                <div class="space-y-3">
                    <p><span class="text-gray-600">Toplam Poliçe:</span> <span class="font-bold"><?php echo $stats['policy_stats']->total_policies; ?></span></p>
                    <p><span class="text-gray-600">Aktif Poliçe:</span> <span class="font-bold text-green-600"><?php echo $stats['policy_stats']->active_policies; ?></span></p>
                    <p><span class="text-gray-600">Toplam Prim:</span> <span class="font-bold"><?php echo number_format($stats['policy_stats']->total_premium, 2); ?> TL</span></p>
                    <p><span class="text-gray-600">Aktif Prim:</span> <span class="font-bold text-green-600"><?php echo number_format($stats['policy_stats']->active_premium, 2); ?> TL</span></p>
                </div>
            </div>
            
            <!-- Customer Stats -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Müşteri İstatistikleri</h3>
                <div class="space-y-3">
                    <p><span class="text-gray-600">Toplam Müşteri:</span> <span class="font-bold"><?php echo $stats['customer_stats']->total_customers; ?></span></p>
                    <p><span class="text-gray-600">Aktif Müşteri:</span> <span class="font-bold text-green-600"><?php echo $stats['customer_stats']->active_customers; ?></span></p>
                    <p><span class="text-gray-600">Bireysel Müşteri:</span> <span class="font-bold"><?php echo $stats['customer_stats']->individual_customers; ?></span></p>
                    <p><span class="text-gray-600">Kurumsal Müşteri:</span> <span class="font-bold"><?php echo $stats['customer_stats']->corporate_customers; ?></span></p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Hızlı İşlemler</h3>
                <div class="space-y-3">
                    <a href="?page=insurance-crm-add-customer" class="inline-block w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-center">Yeni Müşteri</a>
                    <a href="?page=insurance-crm-add-policy" class="inline-block w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 text-center">Yeni Poliçe</a>
                    <a href="?page=insurance-crm-reports" class="inline-block w-full bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 text-center">Raporlar</a>
                </div>
            </div>
            
            <!-- System Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Sistem Bilgisi</h3>
                <div class="space-y-3">
                    <p><span class="text-gray-600">Kullanıcı:</span> <span class="font-bold"><?php echo wp_get_current_user()->display_name; ?></span></p>
                    <p><span class="text-gray-600">Rol:</span> <span class="font-bold"><?php echo implode(', ', wp_get_current_user()->roles); ?></span></p>
                    <p><span class="text-gray-600">Son Giriş:</span> <span class="font-bold"><?php echo get_user_meta(get_current_user_id(), 'last_login', true); ?></span></p>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Renewals -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Yaklaşan Poliçe Yenilemeleri</h3>
            <?php if (!empty($stats['upcoming_renewals'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Poliçe No</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Müşteri</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bitiş Tarihi</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prim</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['upcoming_renewals'] as $policy): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($policy->policy_number); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($policy->end_date); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo number_format($policy->premium_amount, 2); ?> TL</td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <a href="?page=insurance-crm-add-policy&renew=<?php echo $policy->id; ?>" class="text-indigo-600 hover:text-indigo-900">Yenile</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Yaklaşan poliçe yenilemesi bulunmuyor.</p>
            <?php endif; ?>
        </div>
        
        <!-- Pending Tasks -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Bekleyen Görevler</h3>
            <?php if (!empty($stats['pending_tasks'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Görev</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Müşteri</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Son Tarih</th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['pending_tasks'] as $task): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($task->task_description); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($task->first_name . ' ' . $task->last_name); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200"><?php echo esc_html($task->due_date); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <a href="?page=insurance-crm-tasks&action=complete&task_id=<?php echo $task->id; ?>" class="text-green-600 hover:text-green-900 mr-3">Tamamla</a>
                                        <a href="?page=insurance-crm-tasks&action=edit&task_id=<?php echo $task->id; ?>" class="text-blue-600 hover:text-blue-900">Düzenle</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Bekleyen görev bulunmuyor.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}