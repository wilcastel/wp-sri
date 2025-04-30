<?php
class WP_SRI_Cache {
    private static $cache_group = 'wp_sri_hashes';
    private static $cache_expiration = WEEK_IN_SECONDS;

    public static function get($url, $type) {
        $key = self::generate_key($url, $type);
        return wp_cache_get($key, self::$cache_group);
    }

    public static function set($url, $type, $data) {
        $key = self::generate_key($url, $type);
        wp_cache_set($key, $data, self::$cache_group, self::$cache_expiration);
    }

    public static function delete($url, $type) {
        $key = self::generate_key($url, $type);
        wp_cache_delete($key, self::$cache_group);
    }

    private static function generate_key($url, $type) {
        return md5($type . '|' . $url);
    }
}