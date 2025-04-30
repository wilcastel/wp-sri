<div class="wrap">
    <h1><?php _e('Recursos Externos', 'wp-sri-security'); ?></h1>
    
    <div class="card">
        <form method="post">
            <?php wp_nonce_field('wp_sri_analyze_action', '_wpnonce'); ?>
            <p>
                <input type="submit" name="wp_sri_analyze_resources" class="button button-primary" 
                    value="<?php _e('Analizar recursos externos', 'wp-sri-security'); ?>">
                <span class="description"><?php _e('Escanea tu sitio en busca de recursos externos.', 'wp-sri-security'); ?></span>
            </p>
        </form>
    </div>
    
    <div >
        <h2><?php _e('Recursos Registrados', 'wp-sri-security'); ?></h2>
        
        <?php if (!empty($resources)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>URL</th>
                        <th>Algoritmo</th>
                        <th>Última verificación</th>
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td><?php echo esc_html($resource->type); ?></td>
                        <td><?php echo esc_url($resource->url); ?></td>
                        <td><?php echo esc_html($resource->hash_algorithm); ?></td>
                        <td><?php echo esc_html($resource->last_checked); ?></td>
                        <td>
                            <?php if (strpos($resource->found_on, 'http') === 0): ?>
                                <a href="<?php echo esc_url($resource->found_on); ?>" target="_blank">
                                    <?php echo esc_html(parse_url($resource->found_on, PHP_URL_HOST)); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($resource->found_on); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No se han encontrado recursos externos. Haz clic en "Analizar recursos externos" para comenzar.', 'wp-sri-security'); ?></p>
        <?php endif; ?>
    </div>
</div>