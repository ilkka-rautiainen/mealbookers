<?php

abstract class Import implements iImport
{

    protected $restaurant;
    private $current_date;
    private $day_meals;
    protected $is_import_needed;

    public function init()
    {
        // Init the restaurant object
        $restaurant = new Restaurant();
        $restaurant->fetch($this->restaurant_id); // restaurant_id comes from sub class
        $this->restaurant = $restaurant;

        Logger::info(__METHOD__ . " initializing menu import for restaurant {$restaurant->name} id {$restaurant->id}");


        $this->is_import_needed = (DB::inst()->getOne("SELECT COUNT(id) FROM meals
            WHERE restaurant_id = {$this->restaurant->id} AND
                day >= '" . $this->getWeekStartDay() . "' AND
                day <= '" . $this->getWeekEndDay() . "'") == 0);
    }

    /**
     * Deletes the current week's meals
     */
    public function reset()
    {
        if (!$this->restaurant)
            throw new Exception("reset() called before init() call");

        DB::inst()->query("DELETE FROM meals
            WHERE restaurant_id = {$this->restaurant->id} AND
                day >= '" . $this->getWeekStartDay() . "' AND
                day <= '" . $this->getWeekEndDay() . "'");
        $this->is_import_needed = true;
    }

    public function run($save_opening_hours)
    {
        throw new ImportException("Not implemented", $this->restaurant->name, 'all');
    }

    protected function saveOpeningHours()
    {
        throw new ImportException("Not implemented", $this->restaurant->name, 'all');
    }

    /**
     * @param  $weekDayNumber 0..6
     */
    protected function startDay($weekDayNumber)
    {
        Logger::debug(__METHOD__ . " $weekDayNumber");
        if (!is_null($this->current_date))
            throw new Exception("Unable to start day, day already active");
        DB::inst()->startTransaction();
        $this->current_date = date("Y-m-d", strtotime("+" . $weekDayNumber . " days", strtotime($this->getWeekStartDay())));
        $this->day_meals = array();
    }

    /**
     * @param string $date Y-m-d
     */
    protected function startDayForDate($date)
    {
        Logger::debug(__METHOD__ . " $date");
        if (!is_null($this->current_date))
            throw new Exception("Unable to start day, day already active");
        DB::inst()->startTransaction();
        $this->current_date = $date;
        $this->day_meals = array();
    }

    protected function addMeal(Meal $meal)
    {
        if (is_null($this->current_date))
            throw new Exception("Unable to add meal, day not started");

        $meal->restaurant = $this->restaurant;
        $meal->day = $this->current_date;
        $meal->save();

        Logger::trace(__METHOD__ . " meal added: {$meal->name}");
    }

    protected function endDayAndSave()
    {
        Logger::debug(__METHOD__);
        $this->current_date = null;
        if (!is_array($this->day_meals))
            return;

        DB::inst()->commitTransaction();
    }

    protected function isDayActive()
    {
        return (!is_null($this->current_date));
    }

    protected function getWeekStartDay()
    {
        return date("Y-m-d" , strtotime("last monday", strtotime("tomorrow")));
    }

    protected function getWeekEndDay()
    {
        return date("Y-m-d" , strtotime("next sunday", strtotime("yesterday")));
    }

    protected function fetchURL($url)
    {
        $c = curl_init();
        curl_setopt_array($c, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => Conf::inst()->get('import.useragent'),
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        $result = curl_exec($c);

        if ($error = curl_error($c))
            throw new ImportException("CURL error " . curl_errno($c) . ": " . $error, $this->restaurant->name, 'unknown');

        curl_close($c);

        return $result;
    }

    protected function postImport()
    {
        EventLog::inst()->add('restaurant', $this->restaurant_id);
    }
}