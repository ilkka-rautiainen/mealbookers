<?php

abstract class Import
{
    
    protected $restaurant;
    private $current_day;
    private $day_meals;
    protected $is_import_needed;

    /**
     * @var Section in a day's menu, like 'A la carte:'
     */
    private $activeSection = null;

    public function init()
    {
        // Init the restaurant object
        $restaurant = new Restaurant();
        $restaurant->fetch($this->restaurantId); // restaurantId comes from sub class
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

    public function run()
    {
        throw new ImportException("Not implemented");
    }

    protected function startDay($weekDayNumber)
    {
        DB::inst()->startTransaction();
        $this->current_day = $weekDayNumber;
        $this->day_meals = array();
    }

    protected function addMeal(Meal $meal)
    {
        if (is_null($this->current_day))
            throw new Exception("Unable to add meal, day not started");
            
        $meal->restaurant = $this->restaurant;
        $meal->section = $this->activeSection;
        $meal->day = date("Y-m-d", strtotime("+" . $this->current_day . " days", strtotime($this->getWeekStartDay())));
        $meal->save();

        Logger::debug(__METHOD__ . " meal added: {$meal->name}");
    }

    protected function endDayAndSave()
    {
        if (!is_array($this->day_meals))
            return;

        $this->endSection();
        $this->current_day = null;
        DB::inst()->commitTransaction();
    }

    protected function isDayActive()
    {
        return (!is_null($this->current_day));
    }

    protected function startSection($name)
    {
        if (!is_null($this->activeSection))
            throw new Exception("Unable to start section $name, section {$this->activeSection} already exists");
            
        $this->activeSection = $name;
    }

    protected function endSection()
    {
        $this->activeSection = null;
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
        ));
        $result = curl_exec($c);

        if ($error = curl_error($c))
            throw new ImportException("CURL error " . curl_errno($c) . ": " . $error);

        curl_close($c);

        return $result;
    }
}