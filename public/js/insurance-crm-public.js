(function($) {
    'use strict';

    $(document).ready(function() {
        // Public form submit
        $('.insurance-crm-public-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            
            $form.find('.insurance-crm-message').remove();
            $submitButton.prop('disabled', true).text('Gönderiliyor...');
            
            $.ajax({
                url: insurance_crm_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'insurance_crm_public_submit',
                    nonce: insurance_crm_public.nonce,
                    formData: $form.serialize()
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                        $form[0].reset();
                    } else {
                        showMessage($form, 'error', response.data);
                    }
                },
                error: function() {
                    showMessage($form, 'error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Gönder');
                }
            });
        });
    });

    function showMessage($form, type, message) {
        var $message = $('<div>')
            .addClass('insurance-crm-message')
            .addClass(type)
            .text(message);
        
        $form.prepend($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);