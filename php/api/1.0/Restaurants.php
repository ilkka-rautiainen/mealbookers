<?php
use Luracast\Restler\RestException;

class Restaurants
{
	/**
	 * Listens to /restaurants
	 */
	function get($lang = 'en')
	{
		Logger::debug(__METHOD__ . " GET /restaurants?lang=$lang called");

        if (!in_array($lang, array('fi', 'en')))
            $lang = 'en';

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList($lang);
            $result[] = $restaurant->getAsArray();
        }
        return $result;
	}
}