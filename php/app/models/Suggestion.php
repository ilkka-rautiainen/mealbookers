<?php

class Suggestion {
    
    public $id;
    public $datetime;
    public $creator_id;
    public $restaurant_id;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM suggestions WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find suggestion with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new Exception("Error fetching suggestion: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->datetime = $row['datetime'];
        $this->creator_id = $row['creator_id'];
        $this->restaurant_id = $row['restaurant_id'];
    }

    public function getDate()
    {
        return date("j.n.Y", strtotime($this->datetime));
    }

    public function getTime()
    {
        return date("H:i", strtotime($this->datetime));
    }

    private function getMembers()
    {
        $members = array();
        $result = DB::inst()->query("SELECT users.* FROM users
            INNER JOIN suggestions_users ON users.id = suggestions_users.user_id
            WHERE suggestions_users.suggestion_id = {$this->id} AND suggestions_users.accepted = 1");
        while ($row = DB::inst()->fetchAssoc($result)) {
            $member = new User();
            $member->populateFromRow($row);
            $members[] = $member->getAsArray();
        }
        return $members;
    }

    public function getAsArray()
    {
        $creator = new User();
        $creator->fetch($this->creator_id);
        return array(
            'id' => $this->id,
            'time' => $this->getTime(),
            'creator' => $creator->getAsArray(),
            'members' => $this->getMembers()
        );
    }
}