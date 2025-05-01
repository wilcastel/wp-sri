<div class="wrap">
    <h1><?php _e('Recursos Externos', 'wp-sri-security'); ?></h1>

    <?php if (!empty($resources)) : ?>
        <div class="notice notice-info">
            <p><?php printf(
                __('Total de recursos registrados: %d', 'wp-sri-security'),
                count($resources)
            ); ?></p>
        </div>
    <?php endif; ?>
        
    
    <div class="card">
        <h2><?php _e('Escanear sitio', 'wp-sri-security'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('wp_sri_analyze_action', '_wpnonce'); ?>
            <p>
                <input type="submit" name="wp_sri_analyze_resources" class="button button-primary" 
                    value="<?php _e('Analizar recursos externos', 'wp-sri-security'); ?>">
                <span class="description"><?php _e('Escanea tu sitio en busca de recursos externos.', 'wp-sri-security'); ?></span>
            </p>
        </form>
        
        <h2><?php _e('Escanear URL específica', 'wp-sri-security'); ?></h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('wp_sri_scan_url_action'); ?>
            <input type="hidden" name="action" value="wp_sri_scan_url">
            <p>
                <input type="url" name="target_url" placeholder="https://misitio.dev/contacto" required 
                    class="regular-text" style="width: 50%;">
                <input type="submit" class="button button-primary" 
                    value="<?php _e('Escanear URL', 'wp-sri-security'); ?>">
            </p>
            <span class="description">
                    <?php _e('Escanea la página principal y recursos registrados en WordPress.', 'wp-sri-security'); ?>
                </span>
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
                        <th>Acciones</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                    <tr>
                        <td><?php echo esc_html($resource->type); ?></td>
                        <td><?php echo esc_url($resource->url); ?></td>
                        <td><?php echo esc_html($resource->hash_algorithm); ?> - <?php echo esc_html($resource->hash); ?> </td>
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
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url(
                                admin_url('admin-post.php?action=wp_sri_update_hash&resource_id=' . $resource->id),
                                'wp_sri_update_hash_' . $resource->id
                            )); ?>" class="button button-small">Actualizar</a>
                            
                            <a href="<?php echo esc_url(wp_nonce_url(
                                admin_url('admin-post.php?action=wp_sri_delete_resource&resource_id=' . $resource->id),
                                'wp_sri_delete_resource_' . $resource->id
                            )); ?>" class="button button-small button-link-delete" onclick="return confirm('¿Eliminar este recurso?');">Eliminar</a>
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