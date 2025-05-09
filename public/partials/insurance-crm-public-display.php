<?php
/**
 * Public görünüm dosyası
 */
?>
<div class="insurance-crm-public">
    <form class="insurance-crm-public-form" method="post">
        <div class="form-row">
            <label for="first_name"><?php _e('Adınız', 'insurance-crm'); ?> <span class="required">*</span></label>
            <input type="text" name="first_name" id="first_name" required>
        </div>

        <div class="form-row">
            <label for="last_name"><?php _e('Soyadınız', 'insurance-crm'); ?> <span class="required">*</span></label>
            <input type="text" name="last_name" id="last_name" required>
        </div>

        <div class="form-row">
            <label for="tc_identity"><?php _e('TC Kimlik No', 'insurance-crm'); ?> <span class="required">*</span></label>
            <input type="text" name="tc_identity" id="tc_identity" required pattern="[0-9]{11}" title="TC Kimlik No 11 haneli olmalıdır">
        </div>

        <div class="form-row">
            <label for="email"><?php _e('E-posta', 'insurance-crm'); ?> <span class="required">*</span></label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-row">
            <label for="phone"><?php _e('Telefon', 'insurance-crm'); ?> <span class="required">*</span></label>
            <input type="tel" name="phone" id="phone" required>
        </div>

        <div class="form-row">
            <label for="message"><?php _e('Mesajınız', 'insurance-crm'); ?></label>
            <textarea name="message" id="message" rows="4"></textarea>
        </div>

        <div class="form-row">
            <button type="submit"><?php _e('Gönder', 'insurance-crm'); ?></button>
        </div>
    </form>
</div>