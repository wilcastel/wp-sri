<?php
class WP_SRI_Database {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sri_resources';
    }
    
    /**
     * Crea la tabla en la base de datos
     *
     * Se ejecuta durante la activación del plugin para crear
     * la estructura de la tabla necesaria para almacenar los recursos
     *
     * @since    1.0.0
     */
    public function activate() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
          id bigint(20) unsigned NOT NULL auto_increment,
          url text NOT NULL,
          type varchar(10) NOT NULL,
          hash varchar(255) DEFAULT NULL,
          hash_algorithm varchar(20) DEFAULT NULL,
          last_checked datetime DEFAULT NULL,
          found_on text DEFAULT NULL,
          PRIMARY KEY  (id),
          UNIQUE KEY url_type (url(255), type),
          KEY found_on (found_on(100)),
          KEY last_checked (last_checked)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Obtiene un recurso específico de la base de datos
     *
     * Recupera un recurso basado en su URL y tipo
     *
     * @since    1.0.0
     * @param    string    $url     URL del recurso
     * @param    string    $type    Tipo de recurso (script o style)
     * @return   object|null        Objeto con los datos del recurso o null si no existe
     */
    public function get_resource($url, $type) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE url = %s AND type = %s",
            $url, $type
        ));
    }
    
    /**
     * Actualiza un recurso existente en la base de datos
     *
     * Modifica los datos de un recurso basado en criterios específicos
     *
     * @since    1.0.0
     * @param    array    $data     Datos a actualizar (columna => valor)
     * @param    array    $where    Criterios de búsqueda (columna => valor)
     * @return   int|false          Número de filas actualizadas o false en caso de error
     */
    public function update_resource($data, $where) {
        global $wpdb;
        return $wpdb->update($this->table_name, $data, $where);
    }
    
    /**
     * Inserta un nuevo recurso en la base de datos
     *
     * Añade un nuevo registro de recurso con sus datos correspondientes
     *
     * @since    1.0.0
     * @param    array    $data    Datos del recurso a insertar (columna => valor)
     * @return   int|false         ID del nuevo registro o false en caso de error
     */
    public function insert_resource($data) {
        global $wpdb;
        return $wpdb->insert($this->table_name, $data);
    }
    
    /**
     * Elimina un recurso de la base de datos
     *
     * Borra un recurso basado en su ID
     *
     * @since    1.0.0
     * @param    int       $id    ID del recurso a eliminar
     * @return   int|false        Número de filas eliminadas o false en caso de error
     */
    public function delete_resource($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id));
    }
    
    /**
     * Obtiene todos los recursos almacenados
     *
     * Recupera una lista de recursos ordenados por fecha de última comprobación
     *
     * @since    1.0.0
     * @param    int       $limit    Número máximo de recursos a recuperar
     * @return   array               Array de objetos con los datos de los recursos
     */
    public function get_all_resources($limit = 100) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table_name} ORDER BY last_checked DESC LIMIT %d", $limit)
        );
    }

    /*
     * Obtiene un recurso específico de la base de datos
     * @since    1.0.0
     * @param    int       $id    ID del recurso
     * @return   object|null        Objeto con los datos del recurso o null si no existe
     */

    public function get_resource_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    /*
     * Obtiene el número total de recursos almacenados
     * @since    1.0.0
     * @return   int                Número total de recursos
     */
    public function get_resources_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }
}