<?php
class WP_SRI_Core {
    private $admin;
    private $database;
    private $scanner;
    
    public function __construct() {
        $this->database = new WP_SRI_Database();
        $this->scanner = new WP_SRI_Scanner($this->database);
        $this->admin = new WP_SRI_Admin($this->database, $this->scanner);
        
        $this->init_hooks();
    }
    
    /**
     * Inicializa los hooks de WordPress
     *
     * Configura los filtros y acciones necesarios para procesar recursos
     * y añadir atributos SRI al contenido HTML
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
         // Filtros de alta prioridad
         add_filter('script_loader_tag', array($this, 'add_sri_to_scripts'), PHP_INT_MAX, 3);
         add_filter('style_loader_tag', array($this, 'add_sri_to_styles'), PHP_INT_MAX, 3);
         
         // Capturar todo el output final
         add_action('template_redirect', array($this, 'start_output_buffering'), 1);
    }

    /**
     * Inicia el buffer de salida
     *
     * Captura todo el contenido HTML generado para procesarlo antes de enviarlo al navegador
     *
     * @since    1.0.0
     */
    public function start_output_buffering() {
        if (!is_admin()) {
            ob_start(array($this, 'process_final_output'));
        }
    }

    /**
     * Procesa el contenido final de la página
     *
     * Analiza y modifica el HTML completo para añadir atributos SRI a recursos externos
     *
     * @since    1.0.0
     * @param    string    $content    El contenido HTML completo de la página
     * @return   string    El contenido HTML modificado con atributos SRI
     */
    public function process_final_output($content) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        return $this->scanner->process_entire_output($content, $current_url);
    }

    /**
     * Carga el dominio de texto para internacionalización
     *
     * Permite la traducción de cadenas de texto del plugin
     *
     * @since    1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-sri-security', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Añade atributos SRI a etiquetas de script
     *
     * Procesa las etiquetas <script> para añadir el atributo integrity
     *
     * @since    1.0.0
     * @param    string    $tag      La etiqueta HTML completa
     * @param    string    $handle   El identificador del script en WordPress
     * @param    string    $src      La URL del recurso
     * @return   string    La etiqueta modificada con el atributo integrity
     */
    public function add_sri_to_scripts($tag, $handle, $src) {
        return $this->scanner->process_resource($tag, $src, 'script');
    }
    
    /**
     * Añade atributos SRI a etiquetas de estilo
     *
     * Procesa las etiquetas <link> de CSS para añadir el atributo integrity
     *
     * @since    1.0.0
     * @param    string    $tag      La etiqueta HTML completa
     * @param    string    $handle   El identificador del estilo en WordPress
     * @param    string    $src      La URL del recurso
     * @return   string    La etiqueta modificada con el atributo integrity
     */
    public function add_sri_to_styles($tag, $handle, $src) {
        return $this->scanner->process_resource($tag, $src, 'style');
    }

    /**
     * Filtra recursos en el contenido
     *
     * Busca y procesa recursos externos dentro del contenido HTML
     *
     * @since    1.0.0
     * @param    string    $content    El contenido HTML a procesar
     * @return   string    El contenido modificado con atributos SRI
     */
    public function filter_content_resources($content) {
        if (!is_admin()) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
            return $this->scanner->scan_content_resources($content, $current_url);
        }
        return $content;
    }
    
    /**
     * Escanea recursos en el pie de página
     *
     * Procesa los recursos cargados en el footer de la página
     *
     * @since    1.0.0
     */
    public function scan_footer_resources() {
        if (!is_admin()) {
            $dummy_processed = array('scripts' => 0, 'styles' => 0, 'failed' => 0);
            $this->scanner->scan_content_resources(ob_get_contents(), home_url($_SERVER['REQUEST_URI']));
        }
    }

    /**
     * Captura scripts cargados dinámicamente
     *
     * Procesa scripts que son añadidos dinámicamente por WordPress
     *
     * @since    1.0.0
     */
    public function capture_dynamic_scripts() {
        global $wp_scripts;
        $current_url = home_url($_SERVER['REQUEST_URI']);
        
        foreach ($wp_scripts->done as $handle) {
            $script = $wp_scripts->registered[$handle];
            if (!empty($script->src)) {
                $this->scanner->process_resource('', $script->src, 'script', $current_url);
            }
        }
    }

    /**
     * Procesa específicamente scripts de Google Tag Manager
     *
     * Añade atributos SRI a los scripts de GTM que pueden estar presentes en la página
     *
     * @since    1.0.0
     */
    public function process_gtm_script() {
        $gtm_src = 'https://www.googletagmanager.com/gtag/js';
        if (strpos(ob_get_contents(), $gtm_src) !== false) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $this->scanner->process_resource('', $gtm_src, 'script', $current_url);
        }
    }
}