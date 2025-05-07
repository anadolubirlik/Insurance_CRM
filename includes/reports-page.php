                                <tr>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Poliçe No</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Müşteri</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tür</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Başlangıç</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bitiş</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prim</th>
                                    <th class="px-6 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($policies as $policy): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo esc_html($policy->policy_number); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo esc_html($policy->first_name . ' ' . $policy->last_name); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo esc_html($policy->policy_type); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo esc_html($policy->start_date); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo esc_html($policy->end_date); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <?php echo number_format($policy->premium_amount, 2); ?> TL
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $policy->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo esc_html($policy->status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Belirtilen kriterlere uygun poliçe bulunamadı.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Export Buttons -->
        <div class="mt-4 flex gap-4">
            <a href="<?php echo add_query_arg('export', 'pdf'); ?>" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">PDF olarak İndir</a>
            <a href="<?php echo add_query_arg('export', 'excel'); ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Excel olarak İndir</a>
        </div>
    </div>
    <?php
}