<?php

Flight::route('GET /restaurants', array('RestaurantsAPI', 'get'));
Flight::route('POST /restaurants/@restaurantId/suggestions', array('RestaurantsAPI', 'postSuggestions'));

class RestaurantsAPI
{
	/**
	 * Get list of restaurants to the main menu UI
	 */
	function get()
	{
        $lang = $_GET['lang'];
        Logger::debug(__METHOD__ . " GET /restaurants?lang=$lang called");

        if (!in_array($lang, array('fi', 'en')))
            $lang = 'en';

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList($lang);
            $restaurant->fetchSuggestionList();
            $result[] = $restaurant->getAsArray();
        }
        print json_encode($result);
	}

    /**
     * Make or update a suggestion
     * @todo  Implement with real user id + auth
     */
    function postSuggestions($restaurantId) {
        $suggestion = getPostData();

        $restaurantId = (int)$restaurantId;
        if (!DB::inst()->getOne("SELECT id FROM restaurants WHERE id = $restaurantId LIMIT 1"))
            sendHttpError(404, "Restaurant with id $restaurantId not found");

        $day = (int)$suggestion['day'];

        // Validate time
        $time = $suggestion['time'];
        if (!preg_match("/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $time))
            sendHttpError(401, "Suggestion field 'time' was not in format hh:mm");

        // Insert the suggestion
        $dayStamp = strtotime("last monday", strtotime("tomorrow")) + $day * 86400;
        $datetime = date("Y-m-d", $dayStamp) . " $time:00";
        DB::inst()->startTransaction();
        DB::inst()->query("INSERT INTO suggestions (
            creator_id,
            datetime,
            restaurant_id
        ) VALUES (
            1,
            '$datetime',
            $restaurantId
        )");
        $suggestion_id = DB::inst()->getInsertId();

        // Insert the users to the suggestion
        if (is_array($suggestion['members'])) {
            $members = array_keys($suggestion['members']);
            foreach ($members as $member) {
                $member = (int)$member;
                if (!$member)
                    sendHttpError(401, "Suggestion field 'members' contained invalid member ids");

                DB::inst()->query("INSERT INTO suggestions_users (suggestion_id, user_id)
                    VALUES ($suggestion_id, $member)");
            }
        }
        DB::inst()->commitTransaction();

        $response = array(
            'status' => 'ok',
            'id' => $suggestion_id,
        );

        print json_encode($response);
    }
}