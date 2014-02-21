<?php

Flight::route('GET /restaurants(/@lang)', array('RestaurantsAPI', 'get'));
Flight::route('POST /restaurants/@restaurantId/suggestions', array('RestaurantsAPI', 'postSuggestions'));

class RestaurantsAPI
{
	/**
	 * Get list of restaurants to the main menu UI
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
        print json_encode($result);
	}

    /**
     * Make or update a suggestion
     */
    function postSuggestions($restaurantId) {
        $data = getPostData();

        print json_encode($data);
        // print json_encode("ok");
    }
}