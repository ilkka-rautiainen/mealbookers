<?php

class SubwayImport extends Import implements iImport
{
    protected $restaurant_id = 15;

    protected $langs = array(
        'fi' => array(
            'weekdays' => array(
                'Maanantai',
                'Tiistai',
                'Keskiviikko',
                'Torstai',
                'Perjantai',
                'Lauantai',
                'Sunnuntai',
            ),
            'sections' => array(
                'A la Carte\:?' => 'alacarte',
            ),
        ),
        'en' => array(
            'weekdays' => array(
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ),
            'sections' => array(
                'A la Carte\:?' => 'alacarte',
            ),
        ),
    );

    private $current_language = 'all';

    /**
     * Import and Save opening hours
     */
    protected function saveOpeningHours()
    {
        return;
        $source = $this->fetchURL("https://www.teknologforeningen.fi/menu.html?lang=fi");
        phpQuery::newDocument($source);

        $alacarte_element = pq('#page > p:last');
        if (!$alacarte_element)
            throw new ImportException("No alacarte element found", $this->restaurant->name, 'opening_hours');
        $opening_hour_element = pq($alacarte_element)->prev();
        if (!$opening_hour_element)
            throw new ImportException("No opening hour element found", $this->restaurant->name, 'opening_hours');

        $fetch_alacarte = false;
        if (pq($alacarte_element)->html() == 'À la carten slutar serveras en halv timme före stängningstid.')
            $fetch_alacarte = true;

        $html = pq($opening_hour_element)->html();
        if (!preg_match("/Måndag[\s]*\-[\s]*Torsdag\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])\<br\>"
            . "Fredag\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])/", $html, $matches)) {
            Logger::debug(__METHOD__ . " lines didn't match regex");

        }
        else {
            Logger::debug(__METHOD__ . " lines matched");
            DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $matches[3] . "', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $matches[3] . "', 'lunch'
                )");
            if ($fetch_alacarte) {
                DB::inst()->query("INSERT INTO restaurant_opening_hours (
                        restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                    ) VALUES (
                        {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $this->decreaseWithHalfHour($matches[3]) . "', 'alacarte'
                    )");
            }
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $matches[7] . "', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $matches[7] . "', 'lunch'
                )");
            if ($fetch_alacarte) {
                DB::inst()->query("INSERT INTO restaurant_opening_hours (
                        restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                    ) VALUES (
                        {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $this->decreaseWithHalfHour($matches[7]) . "', 'alacarte'
                    )");
            }
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 5, 6, '00:00:00', '00:00:00', 'normal'
                )");
            Logger::debug(__METHOD__ . " opening hours saved successfully");
        }
    }

    /**
     * Runs the import
     */
    public function run($save_opening_hours = false)
    {
        Logger::note(__METHOD__ . " start");
        require_once __DIR__ . '/../lib/phpQuery.php';

        if (!$this->is_import_needed) {
            Logger::info(__METHOD__ . " import not needed, skipping");
            return;
        }

        // Save opening hours
        if ($save_opening_hours) {
            $this->saveOpeningHours();
        }

        $meals = array(
            'Kananrinta',
            'Spicy Italian',
            'Americal Steakhouse Melt',
            'Kalkkuna',
            'Vegepihvi',
            'Kinkku',
            'Kana Fajita',
        );

        $mealsEn = array(
            'Chicken Breast',
            'Spicy Italian',
            'Americal Steakhouse Melt',
            'Turkey',
            'Veggie Patty',
            'Ham',
            'Chicken Fajita',
        );

        // Save the meals
        foreach ($meals as $day => $mealName) {
            $this->startDay($day);
            $meal = new Meal();
            $meal->language = 'fi';
            $meal->name = 'Päivän subi: ' . $mealName;
            $this->addMeal($meal);
            $meal = new Meal();
            $meal->language = 'en';
            $meal->name = 'Sub of the day: ' . $mealsEn[$day];
            $this->addMeal($meal);
            $this->endDayAndSave();
        }
    }
}