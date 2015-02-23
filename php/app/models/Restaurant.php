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

    public function getAsArrayBase()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'link' => $this->link,
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
        $this->suggestionList = new SuggestionList();

        if ($viewer->role == 'guest')
            return;

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
            $this->suggestionList->addSuggestion($suggestion);
        }
    }

    public function getMenuForEmail(Suggestion $suggestion, User $viewer)
    {
        $result = DB::inst()->query("SELECT * FROM meals WHERE day = DATE('{$suggestion->datetime}') AND
            restaurant_id = {$this->id} AND language = '{$viewer->language}'");
        if (!DB::inst()->getRowCount()) {
            $result = DB::inst()->query("SELECT * FROM meals WHERE day = DATE('{$suggestion->datetime}') AND
                restaurant_id = {$this->id} AND language = '" . Conf::inst()->get('mealDefaultLanguage') . "'");
        }

        $meals = array();
        while ($meal = DB::inst()->fetchAssoc($result)) {
            $meals[] = $meal['name'];
        }

        if (count($meals)) {
            return Lang::inst()->get('mailer_body_suggestion_menu_begin', $viewer) . implode("<br />", str_replace(
                array(
                    '<span class="attribute-group">',
                    '<span class="attribute">',
                    '<span class="line-break">',
                ),
                array(
                    '<span style="padding-left:5px;padding-right:5px;display:inline-block;">',
                    '<span style="font-size:10px;text-transform:uppercase;overflow:hidden;color:#c0c0c0;padding:2px 0px;font-style:italic;">',
                    '<span style="margin:0;padding-left:10px;">',
                ),
                $meals
            ));
        }
        else {
            return "";
        }
    }

    private function getOpeningHoursAsArray()
    {
        $today = Application::inst()->getWeekdayNumber();
        $result = DB::inst()->query("SELECT * FROM restaurant_opening_hours
            WHERE restaurant_id = {$this->id} AND end_weekday >= $today");

        $openingHours = array();
        for ($i = $today; $i <= 6; $i++) {
            $openingHours[$i] = array(
                'all' => array(),
                'others' => array(),
                'closed' => false,
            );
        }

        while ($row = DB::inst()->fetchAssoc($result)) {
            for ($i = max($today, $row['start_weekday']); $i <= $row['end_weekday']; $i++) {
                // Closed
                if ($row['type'] == 'normal' && $row['start_time'] == '00:00:00'
                    && $row['end_time'] == '00:00:00')
                {
                    $openingHours[$i]['closed'] = true;
                    continue;
                }

                // Normal
                $openingHour = array(
                    'type' => $row['type'],
                    'start' => substr($row['start_time'], 0, 5),
                    'end' => substr($row['end_time'], 0, 5),
                );

                $openingHours[$i]['all'][] = $openingHour;

                // Lunch
                if ($row['type'] == 'lunch') {
                    $openingHours[$i]['lunch'] = $openingHour;
                }
                // Other
                else {
                    $openingHours[$i]['others'][] = $openingHour;
                }
            }
        }

        $sort = function($a, $b) {
            if ($a['start'] > $b['start'])
                return 1;
            else if ($b['start'] > $a['start'])
                return -1;
            else if ($a['end'] > $b['end'])
                return -1;
            else if ($b['end'] > $a['end'])
                return 1;
            else
                return 0;
        };

        for ($i = 6; $i >= $today; $i--) {
            usort($openingHours[$i]['all'], $sort);
            usort($openingHours[$i]['others'], $sort);
            $openingHours[$i + 1] = $openingHours[$i];
            unset($openingHours[$i]);
        }

        return $openingHours;
    }
}