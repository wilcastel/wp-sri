<?php
class WP_SRI_Admin {
    private $database;   
    private $scanner;
    private $options;
    
    public function __construct($database, $scanner) {
        $this->database = $database;
        $this->scanner = $scanner;
        $this->init_hooks();
    }
    
    /**
     * Inicializa los hooks de WordPress para la administración
     *
     * Configura las acciones necesarias para registrar menús y opciones
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Procesar acciones
        add_action('admin_post_wp_sri_update_hash', array($this, 'handle_update_hash'));
        add_action('admin_post_wp_sri_delete_resource', array($this, 'handle_delete_resource'));
        add_action('admin_post_wp_sri_scan_url', array($this, 'handle_scan_url'));
    }
    
    /**
     * Añade los menús y submenús de administración
     *
     * Crea la estructura de menús en el panel de administración de WordPress
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WP SRI Security', 'wp-sri-security'),
            __('WP SRI Security', 'wp-sri-security'),
            'manage_options',
            'wp-sri-security',
            array($this, 'render_resources_page'),
            'dashicons-shield-alt',
            80
        );
        
        add_submenu_page(
            'wp-sri-security',
            __('Recursos Externos', 'wp-sri-security'),
            __('Recursos Externos', 'wp-sri-security'),
            'manage_options',
            'wp-sri-security',
            array($this, 'render_resources_page')
        );
        
        add_submenu_page(
            'wp-sri-security',
            __('Configuración', 'wp-sri-security'),
            __('Configuración', 'wp-sri-security'),
            'manage_options',
            'wp-sri-security-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registra las opciones y secciones de configuración
     *
     * Configura los campos y secciones para la página de configuración
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting('wp_sri_security_options', 'wp_sri_security_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        add_settings_section(
            'wp_sri_security_general',
            __('Configuración General', 'wp-sri-security'),
            array($this, 'settings_section_callback'),
            'wp-sri-security'
        );
        
        add_settings_field(
            'enable_scripts',
            __('Habilitar SRI para Scripts', 'wp-sri-security'),
            array($this, 'enable_scripts_callback'),
            'wp-sri-security',
            'wp_sri_security_general'
        );
        
        add_settings_field(
            'enable_styles',
            __('Habilitar SRI para Estilos', 'wp-sri-security'),
            array($this, 'enable_styles_callback'),
            'wp-sri-security',
            'wp_sri_security_general'
        );
        
        add_settings_field(
            'hash_algorithm',
            __('Algoritmo de Hash', 'wp-sri-security'),
            array($this, 'hash_algorithm_callback'),
            'wp-sri-security',
            'wp_sri_security_general'
        );
        
        add_settings_field(
            'exclude_domains',
            __('Dominios a Excluir', 'wp-sri-security'),
            array($this, 'exclude_domains_callback'),
            'wp-sri-security',
            'wp_sri_security_general'
        );
    }
    
    /**
     * Callback para la descripción de la sección de configuración
     *
     * Muestra el texto descriptivo para la sección de configuración general
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configura cómo WP SRI Security implementará los atributos de integridad para recursos externos.', 'wp-sri-security') . '</p>';
    }
    
    /**
     * Callback para el campo de habilitar SRI en scripts
     *
     * Renderiza el campo de checkbox para habilitar SRI en scripts
     *
     * @since    1.0.0
     */
    public function enable_scripts_callback() {
        $options = get_option('wp_sri_security_options');
        ?>
        <input type="checkbox" id="enable_scripts" name="wp_sri_security_options[enable_scripts]" value="1" <?php checked(1, $options['enable_scripts'] ?? 1); ?> />
        <label for="enable_scripts"><?php _e('Añadir atributos SRI a scripts externos', 'wp-sri-security'); ?></label>
        <?php
    }
    
    /**
     * Callback para el campo de habilitar SRI en estilos
     *
     * Renderiza el campo de checkbox para habilitar SRI en hojas de estilo
     *
     * @since    1.0.0
     */
    public function enable_styles_callback() {
        $options = get_option('wp_sri_security_options');
        ?>
        <input type="checkbox" id="enable_styles" name="wp_sri_security_options[enable_styles]" value="1" <?php checked(1, $options['enable_styles'] ?? 1); ?> />
        <label for="enable_styles"><?php _e('Añadir atributos SRI a hojas de estilo externas', 'wp-sri-security'); ?></label>
        <?php
    }
    
    /**
     * Callback para el campo de algoritmo de hash
     *
     * Renderiza el selector de algoritmo de hash para los atributos SRI
     *
     * @since    1.0.0
     */
    public function hash_algorithm_callback() {
        $options = get_option('wp_sri_security_options');
        $algorithms = array('sha256', 'sha384', 'sha512');
        ?>
        <select id="hash_algorithm" name="wp_sri_security_options[hash_algorithm]">
            <?php foreach ($algorithms as $algorithm) : ?>
                <option value="<?php echo esc_attr($algorithm); ?>" <?php selected($options['hash_algorithm'] ?? 'sha384', $algorithm); ?>>
                    <?php echo esc_html($algorithm); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Selecciona el algoritmo de hash para los atributos SRI', 'wp-sri-security'); ?></p>
        <?php
    }
    
    /**
     * Callback para el campo de dominios a excluir
     *
     * Renderiza el área de texto para ingresar dominios que se excluirán de SRI
     *
     * @since    1.0.0
     */
    public function exclude_domains_callback() {
        $options = get_option('wp_sri_security_options');
        $domains = isset($options['exclude_domains']) ? implode("\n", $options['exclude_domains']) : '';
        ?>
        <textarea id="exclude_domains" name="wp_sri_security_options[exclude_domains]" rows="5" cols="50"><?php echo esc_textarea($domains); ?></textarea>
        <p class="description"><?php _e('Ingresa los dominios que quieres excluir de SRI (un dominio por línea)', 'wp-sri-security'); ?></p>
        <?php
    }
    
    /**
     * Sanitiza las opciones antes de guardarlas
     *
     * Valida y limpia los datos de entrada del formulario de configuración
     *
     * @since    1.0.0
     * @param    array    $input    Array con los valores de entrada del formulario
     * @return   array    Array con los valores sanitizados
     */
    public function sanitize_options($input) {
        $new_input = array();
        
        $new_input['enable_scripts'] = isset($input['enable_scripts']) ? 1 : 0;
        $new_input['enable_styles'] = isset($input['enable_styles']) ? 1 : 0;
        
        $valid_algorithms = array('sha256', 'sha384', 'sha512');
        $new_input['hash_algorithm'] = in_array($input['hash_algorithm'], $valid_algorithms) ? $input['hash_algorithm'] : 'sha384';
        
        $exclude_domains = array();
        if (isset($input['exclude_domains'])) {
            $domains = explode("\n", $input['exclude_domains']);
            foreach ($domains as $domain) {
                $domain = trim($domain);
                if (!empty($domain)) {
                    $exclude_domains[] = sanitize_text_field($domain);
                }
            }
        }
        $new_input['exclude_domains'] = $exclude_domains;
        
        return $new_input;
    }
    
    /**
     * Renderiza la página de recursos externos
     *
     * Muestra la lista de recursos externos detectados y sus atributos SRI
     *
     * @since    1.0.0
     */
    public function render_resources_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-sri-security'));
        }
    
       // Mostrar errores de dominio
        if ($error = get_transient('wp_sri_scan_error')) {
            add_settings_error(
                'wp_sri_messages',
                'wp_sri_message',
                $error,
                'error'
            );
            delete_transient('wp_sri_scan_error');
        }

        // Mostrar resultados exitosos
        if ($scan_results = get_transient('wp_sri_scan_results')) {
            add_settings_error(
                'wp_sri_messages',
                'wp_sri_message',
                $scan_results,
                'success'
            );
            delete_transient('wp_sri_scan_results');
        }

        // Manejar análisis completo del sitio
        if (isset($_POST['wp_sri_analyze_resources'])) {
            check_admin_referer('wp_sri_analyze_action');
            $result = $this->scanner->analyze_external_resources();
            
            // Preparar mensaje detallado
            $message = sprintf(
                __('Análisis completado en %s segundos. ', 'wp-sri-security'),
                $result['time']
            );
            
            if ($result['new_resources'] > 0) {
                $message .= sprintf(
                    _n(
                        'Se agregó %d nuevo recurso. ',
                        'Se agregaron %d nuevos recursos. ',
                        $result['new_resources'],
                        'wp-sri-security'
                    ),
                    $result['new_resources']
                );
            } else {
                $message .= __('No se encontraron nuevos recursos. ', 'wp-sri-security');
            }
            
            $message .= sprintf(
                __('Detalle: %d scripts frontend, %d estilos frontend, %d scripts admin, %d estilos admin procesados.', 'wp-sri-security'),
                $result['processed']['frontend']['scripts'],
                $result['processed']['frontend']['styles'],
                $result['processed']['admin']['scripts'],
                $result['processed']['admin']['styles']
            );

            add_settings_error(
                'wp_sri_messages',
                'wp_sri_message',
                $message,
                'success'
            );
        }
    
        // Mostrar todos los mensajes
        settings_errors('wp_sri_messages');
        // Obtener recursos para mostrar
        $resources = $this->database->get_all_resources();
        include WP_SRI_PLUGIN_DIR . 'templates/resources-page.php';
    }
    
    /**
     * Renderiza la página de configuración
     *
     * Muestra el formulario con las opciones de configuración del plugin
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-sri-security'));
        }
        
        include WP_SRI_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Encola los archivos CSS y JS necesarios para la página de administración
     *
     * Carga los archivos CSS y JS necesarios para la página de administración
     * y las páginas de configuración
     *
     * @since    1.0.0
     * @param    string    $hook    enqueue_admin_scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_wp-sri-security') {
            wp_enqueue_script(
                'wp-sri-admin-js',
                WP_SRI_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WP_SRI_VERSION,
                true
            );
        }
    }
    /**
     * Maneja la actualización del hash de un recurso
     *
     * Verifica la nonce y actualiza el hash del recurso en la base de datos
     * 
     * @since    1.0.0
     */

    public function handle_update_hash() {
        if (!current_user_can('manage_options') || !isset($_GET['resource_id'])) {
            wp_die(__('Acceso no autorizado', 'wp-sri-security'));
        }
    
        check_admin_referer('wp_sri_update_hash_' . $_GET['resource_id']);
    
        $resource_id = intval($_GET['resource_id']);
        $resource = $this->database->get_resource_by_id($resource_id); // Necesitarás implementar este método
    
        if ($resource) {
            // Lógica para actualizar el hash (similar a get_or_create_resource en WP_SRI_Scanner)
            $content = $this->scanner->get_resource_content($resource->url);
            if ($content) {
                $hash = base64_encode(hash($this->options['hash_algorithm'], $content, true));
                $this->database->update_resource(
                    array(
                        'hash' => $hash,
                        'hash_algorithm' => $this->options['hash_algorithm'],
                        'last_checked' => current_time('mysql')
                    ),
                    array('id' => $resource_id)
                );
            }
        }
    
        wp_redirect(admin_url('admin.php?page=wp-sri-security&updated=1'));
        exit;
    }
    
    /*
     * Maneja la eliminación de un recurso
     *
     * Elimina un recurso de la base de datos
     *
     * @since    1.0.0
     */

    public function handle_delete_resource() {
        if (!current_user_can('manage_options') || !isset($_GET['resource_id'])) {
            wp_die(__('Acceso no autorizado', 'wp-sri-security'));
        }
    
        check_admin_referer('wp_sri_delete_resource_' . $_GET['resource_id']);
    
        $this->database->delete_resource(intval($_GET['resource_id']));
    
        wp_redirect(admin_url('admin.php?page=wp-sri-security&deleted=1'));
        exit;
    }

    /**
     * Maneja el escaneo de una URL específica
     *
     * Escanea una URL específica para detectar recursos externos
     *
     * @since    1.0.0
     */
    public function handle_scan_url() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acceso no autorizado', 'wp-sri-security'));
        }
    
        check_admin_referer('wp_sri_scan_url_action');

        $url = esc_url_raw($_POST['target_url']);
        $current_domain = parse_url(home_url(), PHP_URL_HOST);
        $target_domain = parse_url($url, PHP_URL_HOST);
    
               
        // Verificar que sea del mismo dominio
        if (!$target_domain || $target_domain !== $current_domain) {
            set_transient('wp_sri_scan_error', 
                sprintf(
                    __('Error: Solo puedes escanear URLs de tu propio sitio (%s). La URL ingresada pertenece a %s.', 'wp-sri-security'),
                    $current_domain,
                    $target_domain ?: __('dominio desconocido', 'wp-sri-security')
                ),
            30);
            
            wp_redirect(admin_url('admin.php?page=wp-sri-security'));
            exit;
        }
    
        // Escanear la URL
        $result = $this->scanner->scan_single_url($url);
    
        // Preparar mensaje de resultado
        $message = sprintf(
            __('Escaneo completado para %s. %d nuevos recursos encontrados.', 'wp-sri-security'),
            $url,
            ($result['processed']['scripts'] + $result['processed']['styles'])
        );
    
        if ($result['processed']['scripts'] > 0) {
            $message .= ' ' . sprintf(
                _n('%d script', '%d scripts', $result['processed']['scripts'], 'wp-sri-security'),
                $result['processed']['scripts']
            );
        }
    
        if ($result['processed']['styles'] > 0) {
            $message .= ' ' . sprintf(
                _n('%d estilo', '%d estilos', $result['processed']['styles'], 'wp-sri-security'),
                $result['processed']['styles']
            );
        }
    
        if ($result['processed']['scripts'] + $result['processed']['styles'] === 0) {
            $message = sprintf(
                __('Escaneo completado para %s. No se encontraron nuevos recursos.', 'wp-sri-security'),
                $url
            );
        }
    
        add_settings_error(
            'wp_sri_messages',
            'wp_sri_message',
            $message,
            'success'
        );
    
        // Guardar en transitorio para mostrarlo después del redirect
        set_transient('wp_sri_scan_results', $message, 30);
    
        wp_redirect(admin_url('admin.php?page=wp-sri-security'));
        exit;
    }
}