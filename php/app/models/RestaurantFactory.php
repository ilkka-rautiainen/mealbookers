<?php

class RestaurantFactory
{
    private static $instance = null;
    
    /**
     * Singleton pattern: private constructor
     */
    private function __construct() { }
    
    /**
     * Singleton pattern: Get instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new RestaurantFactory();
        
        return self::$instance;
    }
    
    public function getAllRestaurants()
    {
        $restaurants = array();
        $result = DB::inst()->query("SELECT * FROM restaurants");
        while ($row = DB::inst()->fetchAssoc($result)) {
            $restaurant = new Restaurant();
            $restaurant->populate($row);
            $restaurants[] = $restaurant;
        }
        return $restaurants;
    }
}