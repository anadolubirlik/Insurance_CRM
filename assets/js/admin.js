jQuery(document).ready(function($) {
    // Tarih alanları için datepicker
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }

    // Form doğrulama
    $('form.insurance-crm-form').on('submit', function(e) {
        var $form = $(this);
        var $required = $form.find('[required]');
        var valid = true;

        $required.each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert(insuranceCRM.texts.error);
        }
    });

    // TC Kimlik doğrulama
    $('#tc_identity').on('change', function() {
        var $input = $(this);
        var tc = $input.val();

        if (tc.length !== 11 || !validateTC(tc)) {
            $input.addClass('error');
            alert('Geçersiz TC Kimlik numarası!');
            $input.val('');
        } else {
            $input.removeClass('error');
        }
    });

    // TC Kimlik algoritma kontrolü
    function validateTC(value) {
        if (value.substring(0, 1) === '0') return false;
        if (!(/^[0-9]+$/.test(value))) return false;

        var digits = value.split('');
        var sum = 0;
        for (var i = 0; i < 10; i++) {
            sum += parseInt(digits[i]);
        }
        return sum % 10 === parseInt(digits[10]);
    }

    // Müşteri seçildiğinde poliçeleri getir
    $('#customer_id').on('change', function() {
        var customerId = $(this).val();
        
        if (customerId) {
            $.ajax({
                url: insuranceCRM.ajaxurl,
                type: 'POST',
                data: {
                    action: 'insurance_crm_get_customer_policies',
                    customer_id: customerId,
                    nonce: insuranceCRM.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#policy_id').html(response.data);
                    }
                }
            });
        } else {
            $('#policy_id').html('<option value="">' + 'Poliçe Seçin' + '</option>');
        }
    });

    // Dosya yükleme önizleme
    $('input[type="file"]').on('change', function() {
        var $input = $(this);
        var $preview = $input.siblings('.file-preview');
        
        if ($preview.length === 0) {
            $preview = $('<div class="file-preview"></div>').insertAfter($input);
        }

        if (this.files && this.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                if (this.files[0].type.indexOf('image') !== -1) {
                    $preview.html('<img src="' + e.target.result + '" style="max-width: 200px;">');
                } else {
                    $preview.html('<span class="dashicons dashicons-media-document"></span> ' + this.files[0].name);
                }
            }.bind(this);
            
            reader.readAsDataURL(this.files[0]);
        } else {
            $preview.empty();
        }
    });

    // Silme işlemi onayı
    $('.insurance-crm-delete').on('click', function(e) {
        if (!confirm(insuranceCRM.texts.confirm_delete)) {
            e.preventDefault();
        }