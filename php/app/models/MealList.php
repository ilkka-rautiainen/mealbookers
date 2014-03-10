<?php

class MealList {
    
    private $meals;

    public function __construct()
    {
        $this->meals = array();
    }

    public function addMeal(Meal $meal)
    {
        $day = ((int)date("N", strtotime($meal->day))) - 1;
        if (!isset($this->meals[$day]))
            $this->meals[$day] = array();
        $this->meals[$day][] = $meal;
    }

    public function getAsArray()
    {
        $result = array();
        foreach ($this->meals as $day => $dayMeals) {
            $result[$day] = array();
            foreach ($dayMeals as $meal) {
                $result[$day][] = $meal->getAsArray();
            }
        }
        return $result;
    }
}