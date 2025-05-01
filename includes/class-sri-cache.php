<?php
class WP_SRI_Cache {
    private static $cache_group = 'wp_sri_hashes';
    
    /**
     * Tiempo de expiración de la caché
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $cache_expiration    Duración en segundos (una semana)
     */
    private static $cache_expiration = WEEK_IN_SECONDS;

    /**
     * Obtiene un valor de la caché
     *
     * Recupera un hash SRI almacenado en caché para una URL y tipo específicos
     *
     * @since    1.0.0
     * @param    string    $url     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     * @return   mixed     Datos almacenados en caché o false si no existe
     */
    public static function get($url, $type) {
        $key = self::generate_key($url, $type);
        return wp_cache_get($key, self::$cache_group);
    }

    /**
     * Almacena un valor en la caché
     *
     * Guarda un hash SRI en la caché para una URL y tipo específicos
     *
     * @since    1.0.0
     * @param    string    $url     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     * @param    mixed     $data    Datos a almacenar en caché
     */
    public static function set($url, $type, $data) {
        $key = self::generate_key($url, $type);
        wp_cache_set($key, $data, self::$cache_group, self::$cache_expiration);
    }

    /**
     * Elimina un valor de la caché
     *
     * Borra un hash SRI almacenado en caché para una URL y tipo específicos
     *
     * @since    1.0.0
     * @param    string    $url     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     */
    public static function delete($url, $type) {
        $key = self::generate_key($url, $type);
        wp_cache_delete($key, self::$cache_group);
    }

    /**
     * Genera una clave única para la caché
     *
     * Crea un identificador único basado en la URL y el tipo de recurso
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $url     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     * @return   string    Clave única para identificar el recurso en caché
     */
    private static function generate_key($url, $type) {
        return md5($type . '|' . $url);
    }
}