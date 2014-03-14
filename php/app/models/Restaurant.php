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
            throw new NotFoundException("Unable to find restaurant with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new NotFoundException("Error fetching restaurant: id is null");
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
            'openingHours' => $this->getOpeningHoursAsArray(),
        );
    }

    public function fetchMealList($lang)
    {
        $mealList = new MealList();
        
        $result = DB::inst()->query("SELECT * FROM meals
            WHERE day >= '" . Application::inst()->getDateForDay('today') . "' AND
                day <= '" . Application::inst()->getDateForDay('this_week_sunday') . "' AND
                restaurant_id = {$this->id} AND 
                language = '" . DB::inst()->quote($lang) . "'
            ORDER BY day ASC
            ");

        // Fetch in the another language if not present in current
        if (!DB::inst()->getRowCount())
            $result = DB::inst()->query("SELECT * FROM meals
                WHERE day >= '" . Application::inst()->getDateForDay('today') . "' AND
                    day <= '" . Application::inst()->getDateForDay('this_week_sunday') . "' AND
                    restaurant_id = {$this->id} AND 
                    language = '" . (($lang == 'en') ? 'fi' : 'en') . "'
                ORDER BY day ASC
                ");

        while ($row = DB::inst()->fetchAssoc($result)) {
            $meal = new Meal();
            $meal->populateFromRow($row);
            $mealList->addMeal($meal);
        }
        $this->mealList = $mealList;
    }


    /**
     * Fetches the given user's suggestions in the restaurant
     */
    public function fetchSuggestionList(User $viewer)
    {
        $suggestionList = new SuggestionList();

        // Fetch all suggestions that are suggested to the given user
        $result = DB::inst()->query("SELECT suggestions.* FROM suggestions
            INNER JOIN suggestions_users ON suggestions_users.suggestion_id = suggestions.id
            WHERE DATE(suggestions.datetime) >= '" . Application::inst()->getDateForDay('today') . "' AND
                DATE(suggestions.datetime) <= '" . Application::inst()->getDateForDay('this_week_sunday') . "' AND
                suggestions.restaurant_id = {$this->id} AND
                suggestions_users.user_id = {$viewer->id}
            GROUP BY suggestions.id
            ORDER BY suggestions.datetime ASC");

        while ($row = DB::inst()->fetchAssoc($result)) {
            $suggestion = new Suggestion();
            $suggestion->populateFromRow($row);
            $suggestion->fetchAcceptedMembers($viewer);
            $suggestionList->addSuggestion($suggestion);
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

    private function getOpeningHoursAsArray()
    {
        $today = Application::inst()->getWeekdayNumber();
        $result = DB::inst()->query("SELECT * FROM restaurant_opening_hours
            WHERE restaurant_id = {$this->id} AND end_weekday >= $today");

        $openingHours = array();
        for ($i = $today; $i <= 6; $i++) {
            $openingHours[$i + 1] = array(
                'all' => array(),
                'closed' => false,
            );
        }

        while ($row = DB::inst()->fetchAssoc($result)) {
            for ($i = max($today, $row['start_weekday']); $i <= $row['end_weekday']; $i++) {
                $openingHours[$i + 1]['all'][] = 
                    Lang::inst()->get('opening_hour_type_' . $row['type'])
                    . ' ' . substr($row['start_time'], 0, 5)
                    . ' - ' . substr($row['end_time'], 0, 5);
                if ($row['type'] == 'normal' && $row['start_time'] == '00:00:00'
                    && $row['end_time'] == '00:00:00') {
                    $openingHours[$i + 1]['closed'] = true;
                }
                if ($row['type'] == 'lunch') {
                    $openingHours[$i + 1]['lunch'] = end($openingHours[$i + 1]['all']);
                }
            }
        }

        for ($i = $today; $i <= 6; $i++) {
            $openingHours[$i + 1]['all'] = implode("\r\n", $openingHours[$i + 1]['all']);
        }

        return $openingHours;
    }
}