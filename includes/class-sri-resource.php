<?php
class WP_SRI_Resource {
    public $id;
    public $url;
    public $type;
    public $hash;
    public $hash_algorithm;
    public $last_checked;
    public $found_on;
    
    /**
     * Constructor de la clase
     *
     * Inicializa las propiedades del recurso con los datos proporcionados
     *
     * @since    1.0.0
     * @param    array    $data    Array asociativo con los datos del recurso
     */
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Convierte el objeto a un array
     *
     * Útil para operaciones de base de datos y serialización
     *
     * @since    1.0.0
     * @return   array    Array asociativo con las propiedades del recurso
     */
    public function to_array() {
        return get_object_vars($this);
    }
    
    /**
     * Verifica si el recurso es válido
     *
     * Comprueba que el recurso tenga los datos mínimos necesarios
     *
     * @since    1.0.0
     * @return   bool    True si el recurso tiene URL, tipo y hash; false en caso contrario
     */
    public function is_valid() {
        return !empty($this->url) && !empty($this->type) && !empty($this->hash);
    }
    
    /**
     * Determina si el recurso necesita actualización
     *
     * Verifica si el algoritmo ha cambiado o si el recurso no se ha comprobado recientemente
     *
     * @since    1.0.0
     * @param    string    $new_algorithm    Algoritmo de hash actual configurado
     * @return   bool      True si el recurso necesita actualización; false en caso contrario
     */
    public function needs_update($new_algorithm) {
        return $this->hash_algorithm !== $new_algorithm || 
               strtotime($this->last_checked) < strtotime('-7 days');
    }
}