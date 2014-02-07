<?php
use Luracast\Restler\RestException;

class Restaurants
{
	/**
	 * Listens to /restaurants
	 */
	function get($lang = 'fi')
	{
		Logger::debug(__METHOD__ . " GET /restaurants?lang=$lang called");

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList($lang);
            $result[] = $restaurant->getAsArray();
        }
        return $result;
	}
}