<?php

Flight::route('GET /restaurants', array('RestaurantsAPI', 'get'));
Flight::route('POST /restaurants/@restaurantId/suggestions', array('RestaurantsAPI', 'createSuggestion'));
Flight::route('POST /suggestion', array('RestaurantsAPI', 'acceptSuggestion'));

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
    function createSuggestion($restaurantId)
    {
        Logger::info(__METHOD__ . " POST /restaurants/$restaurantId/suggestions called");
        $post_suggestion = getPostData();

        $restaurantId = (int)$restaurantId;
        if (!DB::inst()->getOne("SELECT id FROM restaurants WHERE id = $restaurantId LIMIT 1"))
            sendHttpError(404, "Restaurant with id $restaurantId not found");

        $day = (int)$post_suggestion['day'];

        // Validate time
        $time = $post_suggestion['time'];
        if (!preg_match("/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $time))
            sendHttpError(401, "Suggestion field 'time' was not in format hh:mm");

        // Insert the suggestion
        $dayStamp = strtotime("last monday", strtotime("tomorrow")) + $day * 86400;
        $datetime = date("Y-m-d", $dayStamp) . " $time:00";
        if (strtotime($datetime) + 310 < time())
            sendHttpError(401, "Suggestion field 'time' was more than 5 min in the past.");

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
        $suggestion = new Suggestion();
        $suggestion->fetch(DB::inst()->getInsertId());

        // Insert the creator to the suggestion
        $hash = md5(microtime(true) . mt_rand() . "gwoipasoidfugoiauvas92762439)(/%\")(/%¤#¤)/#¤&\")(¤%");
        DB::inst()->query("INSERT INTO suggestions_users (
                suggestion_id,
                user_id,
                hash,
                accepted
            ) VALUES (
                {$suggestion->id},
                1,
                '$hash',
                1
            )");

        // Insert the invited members to the suggestion
        $failed_to_invite = array();
        if (is_array($post_suggestion['members'])) {
            $members = array_keys($post_suggestion['members']);
            foreach ($members as $member_id) {
                $member_id = (int)$member_id;
                if (!$member_id)
                    sendHttpError(401, "Suggestion field 'members' contained invalid member ids");
                $hash = md5(microtime(true) . mt_rand() . "gwoipasoidfugoiauvas92762439)(/%\")(/%¤#¤)/#¤&\")(¤%");
                DB::inst()->query("INSERT INTO suggestions_users (
                        suggestion_id,
                        user_id,
                        hash,
                        accepted
                    ) VALUES (
                        {$suggestion->id},
                        $member_id,
                        '$hash',
                        0
                    )");
                $member = new User();
                $member->fetch($member_id);
                if (!$member->inviteToSuggestion($suggestion, $hash)) {
                    $failed_to_invite[] = $member->getName();
                }
            }
        }

        if (count($failed_to_invite)) {
            $response = array(
                'status' => 'ok',
                'failed_to_invite' => $failed_to_invite,
                'id' => $suggestion->id,
            );
        }
        else {
            $response = array(
                'status' => 'ok',
                'id' => $suggestion->id,
            );
        }
        DB::inst()->commitTransaction();

        print json_encode($response);
    }

    /**
     * Accept a suggestion
     * @todo  Implement with real user id + auth
     */
    function acceptSuggestion()
    {
        Logger::info(__METHOD__ . " POST /suggestion called");
        $hash = $_GET['hash'];
        if (strlen($hash) != 32)
            sendHttpError(401, "Invalid hash");
        DB::inst()->query("UPDATE suggestions_users SET accepted = 1 WHERE hash = '"
            . DB::inst()->quote($hash) . "' LIMIT 1");
        print json_encode(array('status' => 'ok'));
    }
}