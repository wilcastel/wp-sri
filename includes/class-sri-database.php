<?php
class WP_SRI_Database {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sri_resources';
    }
    
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
    
    public function get_resource($url, $type) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE url = %s AND type = %s",
            $url, $type
        ));
    }
    
    public function update_resource($data, $where) {
        global $wpdb;
        return $wpdb->update($this->table_name, $data, $where);
    }
    
    public function insert_resource($data) {
        global $wpdb;
        return $wpdb->insert($this->table_name, $data);
    }
    
    public function delete_resource($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id));
    }
    
    public function get_all_resources($limit = 100) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table_name} ORDER BY last_checked DESC LIMIT %d", $limit)
        );
    }
}