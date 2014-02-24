<?php

class Suggestion {
    
    public $id;
    public $datetime;
    public $creator_id;

    public function populate($row)
    {
        $this->id = $row['id'];
        $this->datetime = $row['datetime'];
        $this->creator_id = $row['creator_id'];
    }

    private function getTime()
    {
        return date("H:i", strtotime($this->datetime));
    }

    private function getMembers()
    {
        $members = array();
        $result = DB::inst()->query("SELECT users.* FROM users
            INNER JOIN suggestions_users ON users.id = suggestions_users.user_id
            WHERE suggestions_users.suggestion_id = {$this->id}");
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