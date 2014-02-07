<?php
use Luracast\Restler\RestException;

class Restaurants
{
	/**
	 * Listens to /restaurants
	 */
	function get()
	{
		Logger::debug(__METHOD__ . " GET /restaurants called");

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList('fi');
            $result[] = $restaurant->getAsArray();
        }
        return $result;
	}
}