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
        $this->populateFromRow($row);
        if (!$this->id)
            throw new Exception("Error fetching restaurant: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->link = $row['link'];
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

    /**
     * @todo optimize to only one query
     */
    public function fetchMealList($lang)
    {
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
                $meal->populateFromRow($row);
                $mealList->addMeal($i, $meal);
            }
        }
        $this->mealList = $mealList;
    }


    /**
     * Fetches the given user's suggestions in the restaurant
     * @todo optimize to only one query
     */
    public function fetchSuggestionList(User $viewer)
    {
        $startTime = strtotime("last monday", strtotime("tomorrow"));
        $suggestionList = new SuggestionList();
        for ($i=0; $i<7; $i++) {
            $time = strtotime("+$i days", $startTime);
            // Fetch all suggestions that are suggested to the given user
            $result = DB::inst()->query("SELECT suggestions.* FROM suggestions
                INNER JOIN suggestions_users ON suggestions_users.suggestion_id = suggestions.id
                WHERE DATE(suggestions.datetime) = '" . date("Y-m-d", $time) . "' AND
                    suggestions.restaurant_id = {$this->id} AND
                    suggestions_users.user_id = {$viewer->id}
                GROUP BY suggestions.id
                ORDER BY suggestions.datetime ASC");

            while ($row = DB::inst()->fetchAssoc($result)) {
                $suggestion = new Suggestion();
                $suggestion->populateFromRow($row);
                $suggestion->fetchAcceptedMembers($viewer);
                $suggestionList->addSuggestion($i, $suggestion);
            }
        }
        $this->suggestionList = $suggestionList;
    }

    public function getMenuForEmail(Suggestion $suggestion, User $user)
    {
        $result = DB::inst()->query("SELECT * FROM meals WHERE day = DATE('{$suggestion->datetime}') AND
            restaurant_id = {$this->id} AND language = '{$user->language}'");
        if (!DB::inst()->getRowCount()) {
            $result = DB::inst()->query("SELECT * FROM meals WHERE day = DATE('{$suggestion->datetime}') AND
                restaurant_id = {$this->id} AND language = '" . Conf::inst()->get('mealDefaultLang') . "'");
        }

        $meals = array();
        while ($meal = DB::inst()->fetchAssoc($result)) {
            $meals[] = $meal['name'];
        }

        return implode("<br />", $meals);
    }
}