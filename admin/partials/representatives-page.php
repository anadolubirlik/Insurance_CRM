<div class="wrap">
    <h1 class="wp-heading-inline">Müşteri Temsilcileri</h1>
    <a href="#" class="page-title-action" onclick="document.getElementById('add-representative-form').style.display='block'; return false;">Yeni Ekle</a>
    
    <?php settings_errors('insurance_crm_messages'); ?>
    
    <!-- Yeni Temsilci Ekleme Formu -->
    <div id="add-representative-form" style="display:none;">
        <div class="card">
            <h2>Yeni Müşteri Temsilcisi</h2>
            <form method="post" action="">
                <?php wp_nonce_field('add_representative'); ?>
                <input type="hidden" name="action" value="add">
                
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">Ad</label></th>
                        <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Soyad</label></th>
                        <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="email">E-posta</label></th>
                        <td><input type="email" name="email" id="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="title">Ünvan</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Telefon</label></th>
                        <td><input type="tel