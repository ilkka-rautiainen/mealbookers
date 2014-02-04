<?php

class Meal {
    public $id;
    public $name;
    public $language;
    public $day;
    public $attributes;
    public $section;
    public $restaurant;

    public function save()
    {
        DB::inst()->query("INSERT INTO meals (
                name,
                language,
                day,
                restaurant_id
            ) VALUES (
                '" . DB::inst()->quote($this->name) . "',
                '{$this->language}',
                '" . date("Y-m-d", $this->day) . "',
                {$this->restaurant->id}
            )");
    }
}