<div class="wrap">
    <h1><?php _e('Configuración WP SRI Security', 'wp-sri-security'); ?></h1>
    
    <form action="options.php" method="post">
        <?php
        settings_fields('wp_sri_security_options');
        do_settings_sections('wp-sri-security');
        submit_button(__('Guardar Cambios', 'wp-sri-security'));
        ?>
    </form>
</div>