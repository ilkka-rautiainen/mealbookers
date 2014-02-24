<?php

class User
{
    
    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $active;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM users WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find user with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new Exception("Error fetching user: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->email_address = $row['email_address'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->active = $row['active'];
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'initials' => $this->getInitials(),
        );
    }

    public function getInitials() {
        return substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1);
    }
}