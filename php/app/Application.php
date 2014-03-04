<?php

class Application
{
    private static $instance = null;
    
    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {

    }
    
    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Application();
        
        return self::$instance;
    }

    /**
     * This is passed to frontend with current user
     */
    public function getFrontendConfiguration()
    {
        return array(
            'limits' => array(
                'suggestion_cancelable_time' => Conf::inst()->get('limits.suggestion_cancelable_time'),
                'suggestion_create_in_past_time' => Conf::inst()->get('limits.suggestion_create_in_past_time'),
            ),
        );
    }
}