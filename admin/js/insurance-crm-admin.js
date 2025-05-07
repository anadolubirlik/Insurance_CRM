(function($) {
    'use strict';

    // Müşteri seçildiğinde poliçeleri getir
    $('#customer_id').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            $.ajax({
                url: insurance_crm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'insurance_crm_get_customer_policies',
                    customer_id: customerId,
                    nonce: insurance_crm_ajax.nonce
                },
                beforeSend: function() {
                    $('#policy_id').html('<option value="">' + loading_text + '</option>');
                },
                success: function(response) {
                    if (response.success) {
                        $('#policy_id').html(response.data);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert(insurance_crm_ajax.strings.error);
                }
            });
        } else {
            $('#policy_id').html('<option value="">' + empty_text + '</option>');
        }
    });

    // Silme işlemi onayı
    $('.insurance-crm-delete').on('click', function(e) {
        if (!confirm(insurance_crm_ajax.strings.confirm_delete)) {
            e.preventDefault();
        }
    });

    // Tarih alanları için datepicker
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }

    // Form validation
    $('form').on('submit', function(e) {
        var $requiredFields = $(this).find('[required]');
        var valid = true;

        $requiredFields.each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(insurance_crm_ajax.strings.fill_required);
        }
    });

    // TC Kimlik validation
    $('#tc_identity').on('input', function() {
        var value = $(this).val();
        if (value.length > 11) {
            $(this).val(value.substr(0, 11));
        }
    });

    // Telefon numarası formatting
    $('#phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 0) {
            value = value.match(new RegExp('.{1,3}', 'g')).join(' ');
        }
        $(this).val(value);
    });

    // Premium amount formatting
    $('#premium_amount').on('input', function() {
        var value = $(this).val();
        if (value.indexOf('.') !== -1) {
            var parts = value.split('.');
            if (parts[1].length > 2) {
                $(this).val(parseFloat(value).toFixed(2));
            }
        }
    });

    // Task priority color indication
    $('#priority').on('change', function() {
        var value = $(this).val();
        $(this).removeClass('priority-low priority-medium priority-high')
               .addClass('priority-' + value);
    });

    // Auto save draft
    var autoSaveTimeout;
    $('form :input').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            var formData = $('form').serialize();
            $.ajax({
                url: insurance_crm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'insurance_crm_auto_save',
                    form_data: formData,
                    nonce: insurance_crm_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show auto save notification
                        showNotification('Auto saved');
                    }
                }
            });
        }, 3000);
    });

    // Notification system
    function showNotification(message, type) {
        var $notification = $('<div>', {
            class: 'insurance-crm-notification ' + (type || 'info'),
            text: message
        });

        $('body').append($notification);
        $notification.fadeIn();

        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Filter form handling
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serialize();
        
        $.ajax({
            url: insurance_crm_ajax.ajax_url,
            type: 'POST',
            data: data + '&action=insurance_crm_filter_data',
            beforeSend: function() {
                $('.insurance-crm-table-container').addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    $('.insurance-crm-table-container').html(response.data);
                }
            },
            complete: function() {
                $('.insurance-crm-table-container').removeClass('loading');
            }
        });
    });

    // Export functionality
    $('#export-button').on('click', function() {
        var type = $('#export-type').val();
        var filters = $('#filter-form').serialize();
        
        window.location.href = insurance_crm_ajax.ajax_url + '?' + filters + '&action=insurance_crm_export&type=' + type;
    });

    // Initialize tooltips
    $('[data-tooltip]').tooltip();

    // Initialize select2 for searchable dropdowns
    if ($.fn.select2) {
        $('.searchable-select').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

})(jQuery);