<?php
class WP_SRI_Resource {
    public $id;
    public $url;
    public $type;
    public $hash;
    public $hash_algorithm;
    public $last_checked;
    public $found_on;
    
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function to_array() {
        return get_object_vars($this);
    }
    
    public function is_valid() {
        return !empty($this->url) && !empty($this->type) && !empty($this->hash);
    }
    
    public function needs_update($new_algorithm) {
        return $this->hash_algorithm !== $new_algorithm || 
               strtotime($this->last_checked) < strtotime('-7 days');
    }
}