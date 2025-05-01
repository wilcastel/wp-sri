<?php

class WP_SRI_Scanner {
    private $database;
    private $cache;   
    private $options;
  
    public function __construct($database) {
        $this->database = $database;
        $this->cache = new WP_SRI_Cache();
        $this->options = get_option('wp_sri_security_options', array(
            'enable_scripts' => 1,
            'enable_styles' => 1,
            'hash_algorithm' => 'sha384',
            'exclude_domains' => array()
        ));
    }

    /**
     * Procesa todo el contenido HTML de una página
     *
     * Analiza y modifica las etiquetas de script y estilo para añadir atributos SRI
     *
     * @since    1.0.0
     * @param    string    $content     Contenido HTML completo
     * @param    string    $found_on    URL donde se encontró el contenido
     * @return   string    Contenido HTML modificado con atributos SRI
     */
    public function process_entire_output($content, $found_on) {
        // Procesar scripts
        $content = preg_replace_callback(
            '/<script\b([^>]*?)src=["\']([^"\'>]+)["\']([^>]*)>/i',
            function($matches) use ($found_on) {
                $src = $matches[2];
                if ($this->should_process($src, 'script')) {
                    $resource = $this->get_or_create_resource($src, 'script', $found_on);
                    if ($resource && $resource->is_valid()) {
                        return $this->generate_script_tag($src, $matches[1].$matches[3], $resource);
                    }
                }
                return $matches[0];
            },
            $content
        );

        // Procesar estilos
        $content = preg_replace_callback(
            '/<link\b([^>]*?)href=["\']([^"\'>]+)["\']([^>]*?)rel=["\']stylesheet["\']([^>]*)>/i',
            function($matches) use ($found_on) {
                $href = $matches[2];
                if ($this->should_process($href, 'style')) {
                    $resource = $this->get_or_create_resource($href, 'style', $found_on);
                    if ($resource && $resource->is_valid()) {
                        return $this->generate_style_tag($href, $matches[1].$matches[3].$matches[4], $resource);
                    }
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }
    
    /**
     * Procesa un recurso individual
     *
     * Añade atributos SRI a una etiqueta HTML específica
     *
     * @since    1.0.0
     * @param    string    $tag         Etiqueta HTML completa
     * @param    string    $src         URL del recurso
     * @param    string    $type        Tipo de recurso (script o style)
     * @param    string    $found_on    URL donde se encontró el recurso
     * @return   string    Etiqueta HTML modificada con atributos SRI
     */
    public function process_resource($tag, $src, $type, $found_on = null) {
        if (!$this->should_process($src, $type)) {
            return $tag;
        }
    
        // Obtener o crear el recurso
        $resource = $this->get_or_create_resource($src, $type, $found_on);

        // Si el tag está vacío (para recursos encontrados en el contenido)
        if (empty($tag) && $resource && $resource->is_valid()) {
            $tag = sprintf('<script src="%s" integrity="%s-%s" crossorigin="anonymous"%s></script>',
                esc_url($src),
                $resource->hash_algorithm,
                $resource->hash,
                strpos($src, 'async') !== false ? ' async' : ''
            );
            return $tag;
        }

        // Para tags normales procesados por los hooks
        if ($resource && $resource->is_valid() && !empty($tag)) {
            return $this->add_attributes_to_tag($tag, $resource);
        }
        
        return $tag;
    }
    
    /**
     * Determina si un recurso debe ser procesado
     *
     * Verifica si el recurso cumple con los criterios para añadir SRI
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $src     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     * @return   bool      True si debe procesarse, false en caso contrario
     */
    private function should_process($src, $type) {
        // Verificar si está habilitado para este tipo
        if (('script' === $type && empty($this->options['enable_scripts'])) || 
            ('style' === $type && empty($this->options['enable_styles']))) {
            return false;
        }
        
        // Verificar si es recurso externo
        $host = parse_url($src, PHP_URL_HOST);
        if (!$host || $host === $_SERVER['HTTP_HOST']) {
            return false;
        }
        
        // Verificar dominios excluidos
        foreach ($this->options['exclude_domains'] as $domain) {
            $domain = trim($domain);
            if (!empty($domain) && strpos($host, $domain) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtiene o crea un recurso en la base de datos
     *
     * Busca un recurso existente o genera uno nuevo con su hash SRI
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $src         URL del recurso
     * @param    string    $type        Tipo de recurso (script o style)
     * @param    string    $found_on    URL donde se encontró el recurso
     * @return   WP_SRI_Resource|null   Objeto recurso o null si no se pudo procesar
     */
    private function get_or_create_resource($src, $type, $found_on = null) {
        // Primero verificar caché
        $cached = $this->cache->get($src, $type);
        if ($cached && $cached['hash_algorithm'] === $this->options['hash_algorithm']) {
            return new WP_SRI_Resource($cached);
        }
        
        // Buscar en la base de datos
        $resource_data = $this->database->get_resource($src, $type);
        
        // Si no existe o necesita actualización
        if (!$resource_data || $this->resource_needs_update($resource_data)) {
            $content = $this->get_resource_content($src);
            
            if ($content) {
                $hash = base64_encode(hash($this->options['hash_algorithm'], $content, true));
                $resource = new WP_SRI_Resource(array(
                    'url' => $src,
                    'type' => $type,
                    'hash' => $hash,
                    'hash_algorithm' => $this->options['hash_algorithm'],
                    'last_checked' => current_time('mysql'),
                    'found_on' => $found_on ?: $this->get_current_detection_url()
                ));
                
                // Guardar en base de datos
                if ($resource->id ?? false) {
                    $this->database->update_resource($resource->to_array(), array('id' => $resource->id));
                } else {
                    $this->database->insert_resource($resource->to_array());
                }
                                    
                // Actualizar caché
                $this->cache->set($src, $type, $resource_data);
            }
        }
        
        return $resource_data ? new WP_SRI_Resource($resource_data) : null;
    }
    
    /**
     * Añade atributos SRI a la etiqueta HTML
     *
     * Modifica una etiqueta HTML para incluir los atributos integrity y crossorigin
     *
     * @since    1.0.0
     * @access   private
     * @param    string           $tag        Etiqueta HTML original
     * @param    WP_SRI_Resource  $resource   Objeto recurso con datos SRI
     * @return   string           Etiqueta HTML modificada
     */
    private function add_attributes_to_tag($tag, $resource) {
        // Verificar si ya tiene atributos SRI
        if (strpos($tag, 'integrity=') !== false || strpos($tag, 'crossorigin=') !== false) {
            return $tag;
        }
    
        // Crear los nuevos atributos
        $integrity = sprintf('integrity="%s-%s"', 
            $resource->hash_algorithm, 
            $resource->hash);
        
        $crossorigin = 'crossorigin="anonymous"';
    
        // Patrones para insertar los atributos correctamente
        if (strpos($tag, '/>') !== false) {
            // Para etiquetas autocerradas (XHTML)
            $tag = str_replace('/>', " {$integrity} {$crossorigin} />", $tag);
        } elseif (strpos($tag, '>') !== false) {
            // Para etiquetas normales
            $tag = str_replace('>', " {$integrity} {$crossorigin}>", $tag);
        } else {
            // Como último recurso, añadir al final
            $tag .= " {$integrity} {$crossorigin}";
        }
    
        return $tag;
    }
    
    /**
     * Obtiene el contenido de un recurso externo
     *
     * Descarga el contenido de un recurso remoto con caché temporal
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $url    URL del recurso a descargar
     * @return   string|false      Contenido del recurso o false si falló
     */
    private function get_resource_content($url) {
        $cache_key = 'wp_sri_content_' . md5($url);
        $content = get_transient($cache_key);
        
        if ($content !== false) {
            return $content;
        }
        
        $args = array(
            'timeout' => 10,
            'sslverify' => false,
            'redirection' => 3
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('WP SRI Error fetching resource: ' . $url . ' - ' . $response->get_error_message());
            return false;
        }
        
        $content = wp_remote_retrieve_body($response);
        
        if (!empty($content)) {
            set_transient($cache_key, $content, 12 * HOUR_IN_SECONDS);
        }
        
        return $content;
    }
    
    /**
     * Verifica si un recurso necesita actualización
     *
     * Determina si un recurso debe ser actualizado basado en su algoritmo o fecha
     *
     * @since    1.0.0
     * @access   private
     * @param    object    $resource    Datos del recurso
     * @return   bool      True si necesita actualización, false en caso contrario
     */
    private function resource_needs_update($resource) {
        return $resource->hash_algorithm !== $this->options['hash_algorithm'] || 
               strtotime($resource->last_checked) < strtotime('-7 days');
    }
    
    /**
     * Obtiene la URL donde se detectó el recurso
     *
     * Determina la URL actual o el contexto donde se encontró el recurso
     *
     * @since    1.0.0
     * @access   private
     * @return   string    URL o descripción del contexto
     */
    private function get_current_detection_url() {
        if (!is_admin()) {
            return home_url($_SERVER['REQUEST_URI']);
        }
        
        // Si es una petición AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'AJAX Request';
        }
        
        // Si estamos analizando desde el admin
        if (isset($_POST['wp_sri_analyze_resources'])) {
            return 'Full Site Analysis';
        }
        
        // URL por defecto para recursos registrados
        return 'Registered Resource';
    }

    /**
     * Analiza recursos externos en todo el sitio
     *
     * Escanea la página principal y los recursos registrados en WordPress
     *
     * @since    1.0.0
     * @return   array    Resultados del análisis con estadísticas
     */
    public function analyze_external_resources() {
        $start_time = microtime(true);
        $processed = array(
            'frontend' => array('scripts' => 0, 'styles' => 0),
            'admin' => array('scripts' => 0, 'styles' => 0),
            'failed' => 0
        );

        // 1. Analizar frontend - home page
        $frontend_url = home_url();
        $this->scan_frontend_resources($frontend_url, $processed);

        // 2. Analizar recursos registrados en WordPress
        $this->scan_registered_resources($processed);

        return array(
            'status' => 'completed',
            'processed' => $processed,
            'time' => round(microtime(true) - $start_time, 2)
        );
    }

    /**
     * Escanea recursos en la página principal
     *
     * Obtiene y procesa el contenido de la página principal
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $url          URL a escanear
     * @param    array     $processed    Referencia al array de estadísticas
     */
    private function scan_frontend_resources($url, &$processed) {
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (!is_wp_error($response)) {
            $content = wp_remote_retrieve_body($response);
            $this->find_resources_in_content($content, $processed, $url);
        }
    }

    /**
     * Busca recursos en el contenido HTML
     *
     * Extrae y procesa scripts y estilos externos del contenido
     *
     * @since    1.0.0
     * @access   protected
     * @param    string    $content      Contenido HTML
     * @param    array     $processed    Referencia al array de estadísticas
     * @param    string    $found_on     URL donde se encontró el contenido
     */
    protected function find_resources_in_content($content, &$processed, $found_on) {
        // Buscar scripts externos
        if (preg_match_all('/<script\b[^>]*src=["\']([^"\'>]+)["\'][^>]*>/i', $content, $script_matches)) {
            foreach ($script_matches[1] as $src) {
                if ($this->should_process($src, 'script')) {
                    $this->process_resource('', $src, 'script', $found_on);
                    $processed['frontend']['scripts']++;
                }
            }
        }
        
        // Buscar estilos externos
        if (preg_match_all('/<link\b[^>]*href=["\']([^"\'>]+)["\'][^>]*rel=["\']stylesheet["\'][^>]*>/i', $content, $style_matches)) {
            foreach ($style_matches[1] as $href) {
                if ($this->should_process($href, 'style')) {
                    $this->process_resource('', $href, 'style', $found_on);
                    $processed['frontend']['styles']++;
                }
            }
        }
    }
    
    /**
     * Escanea recursos registrados en WordPress
     *
     * Procesa scripts y estilos registrados en el sistema
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $processed    Referencia al array de estadísticas
     */
    private function scan_registered_resources(&$processed) {
        // Analizar scripts registrados
        global $wp_scripts, $wp_styles;
        
        if (is_a($wp_scripts, 'WP_Scripts')) {
            foreach ($wp_scripts->registered as $script) {
                if (!empty($script->src)) {
                    $this->process_resource('', $script->src, 'script', 'Registered Script');
                    $processed['admin']['scripts']++;
                }
            }
        }
        
        if (is_a($wp_styles, 'WP_Styles')) {
            foreach ($wp_styles->registered as $style) {
                if (!empty($style->src)) {
                    $this->process_resource('', $style->src, 'style', 'Registered Style');
                    $processed['admin']['styles']++;
                }
            }
        }
    }

    /**
     * Escanea recursos en el contenido
     *
     * Busca y procesa scripts en el contenido HTML
     *
     * @since    1.0.0
     * @param    string    $content     Contenido HTML
     * @param    string    $found_on    URL donde se encontró el contenido
     * @return   string    Contenido HTML modificado
     */
    public function scan_content_resources($content, $found_on) {
        // Buscar y reemplazar scripts directamente en el contenido
        $content = preg_replace_callback(
            '/<script\b([^>]*?)src=["\']([^"\'>]+)["\']([^>]*)>/i',
            function($matches) use ($found_on) {
                $attributes = $matches[1] . $matches[3];
                $src = $matches[2];
                
                if ($this->should_process($src, 'script')) {
                    $resource = $this->get_or_create_resource($src, 'script', $found_on);
                    if ($resource && $resource->is_valid()) {
                        return sprintf('<script src="%s" integrity="%s-%s" crossorigin="anonymous"%s%s>',
                            esc_url($src),
                            $resource->hash_algorithm,
                            $resource->hash,
                            strpos($attributes, 'async') !== false ? ' async' : '',
                            $attributes
                        );
                    }
                }
                
                return $matches[0];
            },
            $content
        );
        
        return $content;
    }

    /**
     * Genera una etiqueta de script con atributos SRI
     *
     * Crea una etiqueta <script> completa con los atributos necesarios
     *
     * @since    1.0.0
     * @access   private
     * @param    string           $src        URL del script
     * @param    string           $attrs      Atributos adicionales
     * @param    WP_SRI_Resource  $resource   Objeto recurso con datos SRI
     * @return   string           Etiqueta HTML completa
     */
    private function generate_script_tag($src, $attrs, $resource) {
        $async = strpos($attrs, 'async') !== false ? ' async' : '';
        $defer = strpos($attrs, 'defer') !== false ? ' defer' : '';
        
        return sprintf('<script src="%s" integrity="%s-%s" crossorigin="anonymous"%s%s%s></script>',
            esc_url($src),
            $resource->hash_algorithm,
            $resource->hash,
            $async,
            $defer,
            $attrs
        );
    }

    /**
     * Genera una etiqueta de estilo con atributos SRI
     *
     * Crea una etiqueta <link> completa con los atributos necesarios
     *
     * @since    1.0.0
     * @access   private
     * @param    string           $href       URL del estilo
     * @param    string           $attrs      Atributos adicionales
     * @param    WP_SRI_Resource  $resource   Objeto recurso con datos SRI
     * @return   string           Etiqueta HTML completa
     */
    private function generate_style_tag($href, $attrs, $resource) {
        return sprintf('<link href="%s" rel="stylesheet" integrity="%s-%s" crossorigin="anonymous"%s>',
            esc_url($href),
            $resource->hash_algorithm,
            $resource->hash,
            $attrs
        );
    }


}