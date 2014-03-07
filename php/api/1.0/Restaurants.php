<?php

Flight::route('GET /restaurants', array('RestaurantsAPI', 'getRestaurants'));
Flight::route('POST /restaurants/@restaurantId/suggestions', array('RestaurantsAPI', 'createSuggestion'));
Flight::route('POST /restaurants/@restaurantId/suggestions/@suggestionId', array('RestaurantsAPI', 'manageSuggestionFromSite'));
Flight::route('POST /suggestion', array('RestaurantsAPI', 'acceptSuggestionFromEmail'));

class RestaurantsAPI
{
	/**
	 * Get list of restaurants to the main menu UI
     * @todo  implement for real user and auth
	 */
	function getRestaurants()
	{
        if (!isset($_GET['lang'])) {
            sendHttpError(401, "lang is missing");
        }
        $lang = substr($_GET['lang'], 0, 2);
        Logger::info(__METHOD__ . " GET /restaurants?lang=$lang called");

        // Mockup current_user
        $current_user = new User();
        $current_user->fetch(1);

        if (!in_array($lang, array('fi', 'en')))
            $lang = Conf::inst()->get('restaurantsDefaultLang');

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList($lang);
            $restaurant->fetchSuggestionList($current_user);
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
        Application::inst()->checkAuthentication();
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
        if (strtotime($datetime) + Conf::inst()->get('limits.suggestion_create_in_past_time')
            + Conf::inst()->get('limits.backend_threshold') < time())
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

        // Mockup current user
        $current_user = new User();
        $current_user->fetch(1);

        // Make the creator a member in the suggestion
        $suggestion->insertMember($current_user, true);

        // Insert the invited members to the suggestion
        $failed_to_send_invitation_email = array();
        if (is_array($post_suggestion['members'])) {
            $members = array_keys($post_suggestion['members']);
            foreach ($members as $member_id) {
                $member_id = (int)$member_id;
                if (!$member_id) {
                    sendHttpError(401, "Suggestion field 'members' contained invalid member ids");
                }
                else if (!DB::inst()->getOne("SELECT COUNT(user_id) FROM group_memberships WHERE group_id IN (
                        SELECT group_id FROM group_memberships WHERE user_id = {$current_user->id}
                    ) AND user_id = $member_id LIMIT 1"))
                {
                    sendHttpError(401, "Can't add user $member_id to suggestion: he's not member in your groups");
                }
                $member = new User();
                $member->fetch($member_id);
                $hash = $suggestion->insertMember($member, false);

                // Send suggestion email
                if (!$member->sendSuggestionInviteEmail($suggestion, $hash)) {
                    $failed_to_send_invitation_email[] = $member->getName();
                }
            }
        }

        if (count($failed_to_send_invitation_email)) {
            $response = array(
                'status' => 'ok',
                'failed_to_send_invitation_email' => $failed_to_send_invitation_email,
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
    function acceptSuggestionFromEmail()
    {
        Logger::info(__METHOD__ . " POST /suggestion called");
        $hash = $_GET['hash'];
        if (strlen($hash) != 32)
            sendHttpError(401, "Invalid hash");

        if (!$suggestion_user_id = DB::inst()->getOne("SELECT id FROM suggestions_users WHERE hash = '"
            . DB::inst()->quote($hash) . "' LIMIT 1"))
        {
            return print json_encode(array(
                'status' => 'deleted',
            ));
        }

        $suggestion_user = new SuggestionUser();
        $suggestion_user->fetch($suggestion_user_id);

        $suggestion = new Suggestion();
        $suggestion->fetch($suggestion_user->suggestion_id);
        try {
            $suggestion->accept($suggestion_user);
        }
        catch (TooOldException $e) {
            return print json_encode(array(
                'status' => 'too_old',
                'weekDay' => $suggestion->getWeekDay(),
            ));
        }
        print json_encode(array(
            'status' => 'ok',
            'weekDay' => $suggestion->getWeekDay(),
            'time' => $suggestion->getTime(),
            'restaurant' => DB::inst()->getOne("SELECT name FROM restaurants WHERE id = {$suggestion->restaurant_id}"),
        ));
    }

    /**
     * @todo  implement with real user + auth
     */
    function manageSuggestionFromSite($restaurantId, $suggestionId)
    {
        Logger::info(__METHOD__ . " POST /restaurants/$restaurantId/suggestions/$suggestionId called");
        
        $postData = getPostData();
        $suggestionId = (int) $suggestionId;
        $restaurantId = (int) $restaurantId;

        // Mockup current user
        $current_user = new User();
        $current_user->fetch(1);

        $action = $postData['action'];
        if (!in_array($action, array(
            'accept',
            'cancel',
        ))) {
            sendHttpError(401, "Invalid action: '$action'");
        }


        if ((!$creator_id = DB::inst()->getOne("SELECT creator_id FROM suggestions
                WHERE id = $suggestionId LIMIT 1"))
            || (!$suggestions_users_id = DB::inst()->getOne("SELECT id FROM suggestions_users
                WHERE user_id = {$current_user->id} AND suggestion_id = $suggestionId")))
        {
            sendHttpError(404, "Suggestion with id $suggestionId not found or you're not invited to it");
        }

        $suggestion = new Suggestion();
        $suggestion->fetch($suggestionId);

        // Not manageable anymore
        if (!$suggestion->isManageable(true)) {
            return print(json_encode(array(
                'status' => 'not_manageable'
            )));
        }

        $hasSuggestionBeenDeleted = false;

        $suggestion_user = new SuggestionUser();
        $suggestion_user->fetch($suggestions_users_id);

        if ($action == 'accept') {
            $suggestion->accept($suggestion_user);
        }
        else {
            $hasSuggestionBeenDeleted = $suggestion->cancel($suggestion_user);
        }
        
        if ($hasSuggestionBeenDeleted) {
            print(json_encode(array(
                'status' => 'ok',
                'suggestion' => null,
                'suggestionDeleted' => true
            )));
        }
        else {
            $suggestion->fetch($suggestionId);
            $suggestion->fetchAcceptedMembers($current_user);

            print(json_encode(array(
                'status' => 'ok',
                'suggestion' => $suggestion->getAsArray(),
                'suggestionDeleted' => false
            )));
        }
    }
}