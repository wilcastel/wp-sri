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
    
    private function init_hooks() {
         // Filtros de alta prioridad
         add_filter('script_loader_tag', array($this, 'add_sri_to_scripts'), PHP_INT_MAX, 3);
         add_filter('style_loader_tag', array($this, 'add_sri_to_styles'), PHP_INT_MAX, 3);
         
         // Capturar todo el output final
         add_action('template_redirect', array($this, 'start_output_buffering'), 1);
    }

    public function start_output_buffering() {
        if (!is_admin()) {
            ob_start(array($this, 'process_final_output'));
        }
    }

    public function process_final_output($content) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
        return $this->scanner->process_entire_output($content, $current_url);
    }

    public function load_textdomain() {
        load_plugin_textdomain('wp-sri-security', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_sri_to_scripts($tag, $handle, $src) {
        return $this->scanner->process_resource($tag, $src, 'script');
    }
    
    public function add_sri_to_styles($tag, $handle, $src) {
        return $this->scanner->process_resource($tag, $src, 'style');
    }

    public function filter_content_resources($content) {
        if (!is_admin()) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
            return $this->scanner->scan_content_resources($content, $current_url);
        }
        return $content;
    }
    
    public function scan_footer_resources() {
        if (!is_admin()) {
            $dummy_processed = array('scripts' => 0, 'styles' => 0, 'failed' => 0);
            $this->scanner->scan_content_resources(ob_get_contents(), home_url($_SERVER['REQUEST_URI']));
        }
    }

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

    public function process_gtm_script() {
        $gtm_src = 'https://www.googletagmanager.com/gtag/js';
        if (strpos(ob_get_contents(), $gtm_src) !== false) {
            $current_url = home_url($_SERVER['REQUEST_URI']);
            $this->scanner->process_resource('', $gtm_src, 'script', $current_url);
        }
    }
}