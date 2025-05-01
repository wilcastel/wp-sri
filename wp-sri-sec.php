<?php
/**
 * Plugin Name: WP SRI Security
 * Plugin URI: https://github.com/wilcastel/wp-sri
 * Description: Implementa atributos SRI (Subresource Integrity) automáticamente para recursos cargados externamente en WordPress.
 * Version: 0.0.2
 * Author: wilcastell
 * Author URI: https://wilcastell.com
 * License: GPL-2.0+
 * Text Domain: wp-sri-security
 */

defined('ABSPATH') or die('Acceso directo no permitido');

// Definir constantes del plugin
define('WP_SRI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_SRI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_SRI_VERSION', '0.0.1');

// Cargar archivos necesarios
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-core.php';
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-admin.php';
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-database.php';
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-scanner.php';
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-cache.php';
require_once WP_SRI_PLUGIN_DIR . 'includes/class-sri-resource.php';

// Registrar hook de activación
register_activation_hook(__FILE__, 'wp_sri_security_activate');

function wp_sri_security_activate() {
    $database = new WP_SRI_Database();
    $database->activate();
    
    // Añadir opciones por defecto si no existen
    $default_options = array(
        'enable_scripts' => 1,
        'enable_styles' => 1,
        'hash_algorithm' => 'sha384',
        'exclude_domains' => array()
    );
    
    if (!get_option('wp_sri_security_options')) {
        add_option('wp_sri_security_options', $default_options);
    }
}

// Inicializar el plugin
function wp_sri_security_init() {
    $wp_sri_security = new WP_SRI_Core();
}
add_action('plugins_loaded', 'wp_sri_security_init');