<?php

Flight::route('GET /restaurants', array('RestaurantsAPI', 'getRestaurants'));
Flight::route('GET /restaurants/suggestions', array('RestaurantsAPI', 'getSuggestions'));
Flight::route('POST /restaurants/@restaurantId/suggestions', array('RestaurantsAPI', 'createSuggestion'));
Flight::route('POST /restaurants/@restaurantId/suggestions/@suggestionId', array('RestaurantsAPI', 'manageSuggestionFromSite'));
Flight::route('POST /suggestion/@token', array('RestaurantsAPI', 'acceptSuggestionFromEmail'));

class RestaurantsAPI
{
	/**
	 * Get list of restaurants to the main menu UI
	 */
	function getRestaurants()
	{
        global $current_user;

        if (!isset($_GET['lang'])) {
            Application::inst()->exitWithHttpCode(400, "lang is missing");
        }
        $lang = substr($_GET['lang'], 0, 2);
        Logger::info(__METHOD__ . " GET /restaurants?lang=$lang called");

        if (!in_array($lang, array('fi', 'en')))
            $lang = Conf::inst()->get('restaurantsDefaultLanguage');

        $restaurants = RestaurantFactory::inst()->getAllRestaurants();
        $result = array();
        $order = 0;
        foreach ($restaurants as $restaurant) {
            $restaurant->fetchMealList($lang);
            $restaurant->fetchSuggestionList($current_user);
            $array = $restaurant->getAsArray();
            $array['order'] = $order;
            $result[] = $array;
            $order++;
        }
        print json_encode($result);
	}

    function getSuggestions()
    {
        global $current_user;
        Logger::debug(__METHOD__ . " GET /restaurants/suggestions called");
        Application::inst()->checkAuthentication();

        $suggestions = RestaurantFactory::inst()->getSuggestions($current_user);
        print json_encode($suggestions);
    }

    /**
     * Make or update a suggestion
     */
    function createSuggestion($restaurantId)
    {
        global $current_user;
        Logger::info(__METHOD__ . " POST /restaurants/$restaurantId/suggestions called");
        Application::inst()->checkAuthentication();

        DB::inst()->startTransaction();
        $post_suggestion = Application::inst()->getPostData();

        $restaurantId = (int)$restaurantId;
        if (!DB::inst()->getOne("SELECT id FROM restaurants WHERE id = $restaurantId LIMIT 1"))
            throw new HttpException(404, 'restaurant_not_found', 'danger');

        $day = (int)$post_suggestion['day'];

        // Validate time
        $time = $post_suggestion['time'];
        if (!preg_match("/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $time))
            throw new HttpException(409, 'invalid_time');

        // Insert the suggestion
        $dayStamp = strtotime("last monday", strtotime("tomorrow")) + $day * 86400;
        $datetime = date("Y-m-d", $dayStamp) . " $time:00";
        if (strtotime($datetime) + Conf::inst()->get('limits.suggestion_create_in_past_time')
            + Conf::inst()->get('limits.backend_threshold') < time())
        {
            throw new HttpException(409, 'too_early');
        }

        DB::inst()->query("INSERT INTO suggestions (
            creator_id,
            datetime,
            restaurant_id
        ) VALUES (
            {$current_user->id},
            '$datetime',
            $restaurantId
        )");
        $suggestion_id = DB::inst()->getInsertId();

        $suggestion = new Suggestion();
        $suggestion->fetch($suggestion_id);

        // Make the creator a member in the suggestion
        $suggestion->insertMember($current_user, true);

        // Insert the invited members to the suggestion
        $failed_to_send_invitation_email = array();
        if (is_array($post_suggestion['members'])) {
            $members = array_keys($post_suggestion['members']);
            foreach ($members as $member_id) {
                $member_id = (int)$member_id;
                if (!$member_id) {
                    Application::inst()->exitWithHttpCode(400, "Suggestion field 'members' contained invalid member ids");
                }
                else if (!DB::inst()->getOne("SELECT COUNT(user_id) FROM group_memberships WHERE group_id IN (
                        SELECT group_id FROM group_memberships WHERE user_id = {$current_user->id}
                    ) AND user_id = $member_id LIMIT 1"))
                {
                    Application::inst()->exitWithHttpCode(400, "Can't add user $member_id to suggestion: he's not member in your groups");
                }
                $member = new User();
                $member->fetch($member_id);
                $hash = $suggestion->insertMember($member, false);

                // Send suggestion email
                if (!$member->sendSuggestion($suggestion, $hash)) {
                    $failed_to_send_invitation_email[] = $member->getName();
                }
            }
        }

        EventLog::inst()->add('suggestion', $suggestion_id);

        $response = array(
            'status' => 'ok',
            'id' => $suggestion->id,
        );
        if (count($failed_to_send_invitation_email)) {
            $response['failed_to_send_invitation_email'] = $failed_to_send_invitation_email;
        }

        DB::inst()->commitTransaction();

        print json_encode($response);
    }

    /**
     * Accept a suggestion
     */
    function acceptSuggestionFromEmail($token)
    {
        global $current_user;
        Logger::info(__METHOD__ . " POST /suggestion/$token called");

        try {
            if ($current_user->role == 'guest') {
                throw new ApiException('not_logged_in');
            }
            Application::inst()->checkAuthentication();

            try {
                $suggestion_user_id = Application::inst()->getTokenId($token, false);
            }
            catch (NotFoundException $e) {
                throw new ApiException('not_found');
            }

            if (!DB::inst()->getOne("SELECT id FROM suggestions_users
                WHERE id = $suggestion_user_id LIMIT 1"))
            {
                Application::inst()->deleteToken();
                throw new ApiException('deleted');
            }

            $suggestion_user = new SuggestionUser();
            $suggestion_user->fetch($suggestion_user_id);
            $suggestion = new Suggestion();
            $suggestion->fetch($suggestion_user->suggestion_id);

            if ($suggestion_user->user_id != $current_user->id) {
                throw new ApiException('wrong_user');
            }

            // Not manageable anymore
            if (!$suggestion->isManageable(false)) {
                Application::inst()->deleteToken();
                throw new ApiException('too_old', array(
                    'weekDay' => $suggestion->getWeekDay() + 1,
                ));
            }

            $suggestion->accept($suggestion_user);

            Application::inst()->deleteToken($token);
            print json_encode(array(
                'status' => 'ok',
                'weekDay' => $suggestion->getWeekDay() + 1,
                'time' => $suggestion->getTime(),
                'restaurant' => DB::inst()->getOne("SELECT name FROM restaurants WHERE id = {$suggestion->restaurant_id}"),
            ));
        }
        catch (ApiException $e) {
            return print json_encode(array_merge(array(
                'status' => $e->getMessage(),
            ), $e->getData()));
        }
    }

    function manageSuggestionFromSite($restaurantId, $suggestionId)
    {
        global $current_user;
        Logger::info(__METHOD__ . " POST /restaurants/$restaurantId/suggestions/$suggestionId called");
        Application::inst()->checkAuthentication();

        $postData = Application::inst()->getPostData();
        $suggestionId = (int) $suggestionId;
        $restaurantId = (int) $restaurantId;

        $action = $postData['action'];
        if (!in_array($action, array(
            'accept',
            'cancel',
        ))) {
            throw new HttpException(400, 'invalid_action');
        }


        if (!DB::inst()->getOne("SELECT creator_id FROM suggestions
                WHERE id = $suggestionId LIMIT 1")
            || (!$suggestions_users_id = DB::inst()->getOne("SELECT id FROM suggestions_users
                WHERE user_id = {$current_user->id} AND suggestion_id = $suggestionId")))
        {
            throw new HttpException(404, 'suggestion_not_found');
        }

        $suggestion = new Suggestion();
        $suggestion->fetch($suggestionId);

        // Not manageable anymore
        if (!$suggestion->isManageable(true)) {
            throw new HttpException(409, 'not_manageable_anymore');
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