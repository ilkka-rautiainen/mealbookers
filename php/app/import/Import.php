<?php

abstract class Import
{
    
    protected $restaurant;
    protected $currentDay;
    protected $dayMeals;
    protected $isImportNeeded;

    /**
     * @var Section in a day's menu, like 'We recommend:'
     */
    protected $activeSection = null;

    public function init()
    {
        // Init the restaurant object
        $restaurant = new Restaurant();
        $restaurant->fetch($this->restaurantId); // restaurantId comes from sub class
        $this->restaurant = $restaurant;
        
        Logger::info(__METHOD__ . " initializing menu import for restaurant {$restaurant->name} id {$restaurant->id}");


        $this->isImportNeeded = (DB::inst()->getOne("SELECT COUNT(id) FROM meals
            WHERE restaurant_id = {$this->restaurant->id} AND
                day >= '" . $this->getWeekStartDay() . "' AND
                day <= '" . $this->getWeekEndDay() . "'") == 0);
    }

    public function run()
    {
        throw new ImportException("Not implemented");
    }

    protected function startDay($weekDayNumber)
    {
        DB::inst()->startTransaction();
        $this->currentDay = $weekDayNumber;
        $this->dayMeals = array();
    }

    protected function addMeal(Meal $meal)
    {
        if (is_null($this->currentDay))
            throw new Exception("Unable to add meal, day not started");
            
        $meal->restaurant = $this->restaurant;
        $meal->section = $this->activeSection;
        $meal->day = strtotime("+" . $this->currentDay . " days", strtotime($this->getWeekStartDay()));
        $meal->save();

        Logger::debug(__METHOD__ . " meal added: {$meal->name}");
    }

    protected function endDayAndSave()
    {
        if (!is_array($this->dayMeals))
            return;

        $this->endSection();
        $this->currentDay = null;
        DB::inst()->commitTransaction();
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
        global $config;

        $c = curl_init();
        curl_setopt_array($c, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $config['useragent'],
        ));
        $result = curl_exec($c);

        if ($error = curl_error($c))
            throw new ImportException("CURL error " . curl_errno($c) . ": " . $error);

        curl_close($c);

        return $result;
    }
}