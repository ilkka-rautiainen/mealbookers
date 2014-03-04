<?php

class Lang
{
    private static $instance = null;
    
    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {
        require __DIR__ . '/language/include.php';
    }
    
    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Lang();
        
        return self::$instance;
    }

    /**
     * @todo Implement with real current user
     */
    public function get($key, $user = null) {
        global $language;

        if (is_null($user)) {
            $user = new User();
            $user->fetch(1);
        }
        $lang = $user->language;

        if (isset($language[$lang][$key])) {
            return $language[$lang][$key];
        }
        else if (isset($language[$lang]['backend_only'][$key])) {
            return $language[$lang]['backend_only'][$key];
        }
        else {
            throw new Exception("No such language label found: $key");
        }
    }
}