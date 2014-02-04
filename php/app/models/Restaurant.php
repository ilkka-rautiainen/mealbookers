<?php

/**
 * Restaurant class
 */
class Restaurant {
    public $id;
    public $name;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM restaurants WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find restaurant with id $id");
        $fields = DB::inst()->fetchAssoc($result);
        $this->id = $fields['id'];
        $this->name = $fields['name'];
        if (!$this->id)
            throw new Exception("Error fetching restaurant: id is null");
    }
}