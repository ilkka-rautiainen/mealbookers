<?php

class Meal {
    
    public $id;
    public $name;
    public $language;
    public $day;
    public $section;
    public $restaurant;

    public function populate($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->language = $row['language'];
        $this->day = strtotime($row['day']);
        $this->section = $row['section'];
    }

    public function save()
    {
        DB::inst()->query("INSERT INTO meals (
                name,
                language,
                section,
                day,
                restaurant_id
            ) VALUES (
                '" . DB::inst()->quote($this->name) . "',
                '{$this->language}',
                " . (($this->section) ? "'{$this->section}'" : "NULL" ) . ",
                '" . date("Y-m-d", $this->day) . "',
                {$this->restaurant->id}
            )");
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'language' => $this->language,
            'day' => $this->day,
            'section' => $this->section,
        );
    }
}