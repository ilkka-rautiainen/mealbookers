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

    public function getAllRestaurants($viewer = null)
    {
        $restaurants = array();
        if (!is_null($viewer)) {
            $result = DB::inst()->query("SELECT restaurants.* FROM restaurants
                LEFT OUTER JOIN (
                    SELECT *
                    FROM users_restaurants_order
                    WHERE user_id = {$viewer->id}
                ) order_table
                ON order_table.restaurant_id = restaurants.id
                LEFT JOIN suggestions_users
                    ON suggestions_users.id = (SELECT id FROM suggestions_users WHERE
                        suggestions_users.created > UNIX_TIMESTAMP(CURDATE()) AND user_id = {$viewer->id}
                        AND restaurants.id = (SELECT restaurant_id FROM suggestions WHERE id = suggestions_users.suggestion_id)
                        ORDER BY suggestions_users.created DESC LIMIT 1)
                ORDER BY
                    suggestions_users.created DESc,
                    order_table.order_points DESC,
                    restaurants.name ASC
            ");
        }
        else {
            $result = DB::inst()->query("SELECT * FROM restaurants ORDER BY name ASC");
        }
        while ($row = DB::inst()->fetchAssoc($result)) {
            $restaurant = new Restaurant();
            $restaurant->populateFromRow($row);
            $restaurants[] = $restaurant;
        }
        return $restaurants;
    }

    public function getSuggestions(User $viewer)
    {
        // Fetch all suggestions that are suggested to the given user
        $result = DB::inst()->query("SELECT suggestions.* FROM suggestions
            INNER JOIN suggestions_users ON suggestions_users.suggestion_id = suggestions.id
            WHERE DATE(suggestions.datetime) >= '" . Application::inst()->getDateForDay('today') . "' AND
                DATE(suggestions.datetime) <= '" . Application::inst()->getDateForDay('this_week_sunday') . "' AND
                suggestions_users.user_id = {$viewer->id}
            GROUP BY suggestions.id
            ORDER BY
                suggestions.restaurant_id ASC,
                suggestions.datetime ASC");

        $current_restaurant_id = 0;
        $restaurants = array();
        $suggestion_list = new SuggestionList();

        while ($row = DB::inst()->fetchAssoc($result)) {
            // New restaurant started
            if ($row['restaurant_id'] != $current_restaurant_id) {
                // Save it to restaurants
                if ($suggestion_list->length()) {
                    $restaurants[$current_restaurant_id] = $suggestion_list->getAsArray();
                }
                // Start new list
                $suggestion_list = new SuggestionList();
                $current_restaurant_id = $row['restaurant_id'];
            }
            $suggestion = new Suggestion();
            $suggestion->populateFromRow($row);
            $suggestion->fetchAcceptedMembers($viewer);
            $suggestion_list->addSuggestion($suggestion);
        }

        if ($suggestion_list->length()) {
            $restaurants[$current_restaurant_id] = $suggestion_list->getAsArray();
        }

        return $restaurants;
    }
}