<?php

class Conf
{
    private static $instance = null;
    
    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {
        require __DIR__ . '/config.php';
    }
    
    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Conf();
        
        return self::$instance;
    }

    /**
     * @param  $key  Example: mail.smpt_username
     */
    public function get($key) {
        global $config;

        $parts = explode(".", $key);
        $current = $config;
        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                throw new Exception("Unable to find config element: $key");
            }
            $current = $current[$part];
        }
        return $current;
    }
}