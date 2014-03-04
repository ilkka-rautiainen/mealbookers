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
            'plus_ones' => $this->getPlusOnesAmount(),
        );
    }

    /**
     * @todo implement with current user
     */
    public function getPlusOnesAmount()
    {
        DB::inst()->query("SELECT DISTINCT id FROM plus_ones
            INNER JOIN group_memberships gm ON gm.user_id = plus_ones.user_id
            WHERE plus_ones.meal_id = {$this->id} AND gm.group_id IN (1, 2)");
        return (int)DB::inst()->getRowCount();
    }
}