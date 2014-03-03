<?php

class Restaurant
{
    public $id;
    public $name;
    public $link;
    public $mealList;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM restaurants WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find restaurant with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->link = $row['link'];
        if (!$this->id)
            throw new Exception("Error fetching restaurant: id is null");
    }

    public function populate($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->link = $row['link'];
    }

    /**
     * @todo optimize to only one query
     */
    public function fetchMealList($lang)
    {
        global $config;

        $startTime = strtotime("last monday", strtotime("tomorrow"));
        $mealList = new MealList();
        for ($i=0; $i<7; $i++) {
            $time = strtotime("+$i days", $startTime);
            $result = DB::inst()->query("SELECT * FROM meals
                WHERE day = '" . date("Y-m-d", $time) . "' AND restaurant_id = {$this->id} AND
                language = '" . DB::inst()->quote($lang) . "'");

            // Fetch in the another language if not present in current
            if (!DB::inst()->getRowCount())
                $result = DB::inst()->query("SELECT * FROM meals
                    WHERE day = '" . date("Y-m-d", $time) . "' AND restaurant_id = {$this->id} AND
                    language = '" . (($lang == 'en') ? 'fi' : 'en') . "'");

            while ($row = DB::inst()->fetchAssoc($result)) {
                $meal = new Meal();
                $meal->populate($row);
                $mealList->addMeal($i, $meal);
            }
        }
        $this->mealList = $mealList;
    }


    /**
     * Fetches the given user's suggestions in the restaurant
     * @todo optimize to only one query
     */
    public function fetchSuggestionListForUser(User $user)
    {
        global $config;

        $startTime = strtotime("last monday", strtotime("tomorrow"));
        $suggestionList = new SuggestionList();
        for ($i=0; $i<7; $i++) {
            $time = strtotime("+$i days", $startTime);
            $result = DB::inst()->query("SELECT suggestions.* FROM suggestions
                INNER JOIN suggestions_users ON suggestions_users.suggestion_id = suggestions.id
                WHERE DATE(suggestions.datetime) = '" . date("Y-m-d", $time) . "' AND
                    suggestions.restaurant_id = {$this->id} AND
                    suggestions_users.user_id = {$user->id}
                GROUP BY suggestions.id
                ORDER BY suggestions.datetime ASC");

            while ($row = DB::inst()->fetchAssoc($result)) {
                $suggestion = new Suggestion();
                $suggestion->populateFromRow($row);
                $suggestionList->addSuggestion($i, $suggestion);
            }
        }
        $this->suggestionList = $suggestionList;
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'link' => $this->link,
            'mealList' => $this->mealList->getAsArray(),
            'suggestionList' => $this->suggestionList->getAsArray(),
        );
    }
}