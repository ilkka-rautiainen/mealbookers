<?php

class Restaurant
{
    public $id;
    public $name;
    public $mealList;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM restaurants WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find restaurant with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->id = $row['id'];
        $this->name = $row['name'];
        if (!$this->id)
            throw new Exception("Error fetching restaurant: id is null");
    }

    public function populate($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
    }

    public function fetchMealList($lang)
    {
        $startTime = strtotime("last monday", strtotime("tomorrow"));
        $mealList = new MealList();
        for ($i=0; $i<7; $i++) {
            $time = strtotime("+$i days", $startTime);
            $result = DB::inst()->query("SELECT * FROM meals
                WHERE day = '" . date("Y-m-d", $time) . "' AND restaurant_id = {$this->id} AND
                language = '$lang'");
            while ($row = DB::inst()->fetchAssoc($result)) {
                $meal = new Meal();
                $meal->populate($row);
                $mealList->addMeal($i, $meal);
            }
        }
        $this->mealList = $mealList;
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'mealList' => $this->mealList->getAsArray(),
        );
    }
}