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

    public function get($key, $user = null, $lang = null) {
        global $language, $current_user;

        if (is_null($lang)) {
            if (is_null($user)) {
                $user = &$current_user;
            }
            $lang = $user->language;
        }

        if (isset($language[$lang][$key])) {
            return $language[$lang][$key];
        }
        else if (isset($language[$lang]['backend_only'][$key])) {
            return $language[$lang]['backend_only'][$key];
        }
        else {
            throw new Exception("No such language label found: $key, lang: $lang");
        }
    }
}