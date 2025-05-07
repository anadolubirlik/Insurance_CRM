jQuery(document).ready(function($) {
    // Silme onayı
    $('.delete-customer').on('click', function(e) {
        var customerName = $(this).data('customer-name');
        if (!confirm(customerName + ' müşterisini silmek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });

    $('.delete-policy').on('click', function(e) {
        var policyNumber = $(this).data('policy-number');
        if (!confirm(policyNumber + ' numaralı poliçeyi silmek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });

    // Tarih alanları için datepicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Poliçe doğrulama
    $('#verify-policy').on('click', function(e) {
        e.preventDefault();
        var policyNumber = $('#policy_number').val();
        var customerTC = $('#customer_tc').val();

        if (!policyNumber || !customerTC) {
            alert('Lütfen poliçe numarası ve TC kimlik numarası giriniz.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'insurance_crm_verify_policy',
                nonce: insurance_crm.nonce,
                policy_number: policyNumber,
                customer_tc: customerTC
            },
            beforeSend: function() {
                $('#verify-policy').prop('disabled', true).text('Doğrulanıyor...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Poliçe doğrulandı!');
                    $('#policy-details').html(response.data.html);
                } else {
                    alert('Hata: ' + response.data);
                }
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            },
            complete: function() {
                $('#verify-policy').prop('disabled', false).text('Poliçeyi Doğrula');
            }
        });
    });

    // Dinamik form alanları
    $('#policy_type').on('change', function() {
        var policyType = $(this).val();
        $('.policy-type-fields').hide();
        $('#' + policyType + '-fields').show();
    });

    // Rapor filtreleri
    $('#report-filters').on('submit', function() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();

        if (startDate && endDate && startDate > endDate) {
            alert('Başlangıç tarihi bitiş tarihinden sonra olamaz.');
            return false;
        }
    });

    // Müşteri arama
    var searchTimer;
    $('#customer-search').on('input', function() {
        clearTimeout(searchTimer);
        var searchTerm = $(this).val();

        searchTimer = setTimeout(function() {
            if (searchTerm.length > 2) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'insurance_crm_search_customers',
                        nonce: insurance_crm.nonce,
                        term: searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#customer-list').html(response.data);
                        }
                    }
                });
            }
        }, 500);
    });
});