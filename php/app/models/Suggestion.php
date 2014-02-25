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

    /**
     * Day of week 0..6 mon..sun
     */
    public function getWeekDay()
    {
        return ((int)date("N", strtotime($this->datetime))) - 1;
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

    /**
     * @todo  implement with real user and auth
     */
    public function accept($suggestions_users_id)
    {
        Logger::info(__METHOD__ . " accepting suggestion with suggestions_users_id $suggestions_users_id");
        $accepted_suggestions_before = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
            suggestion_id = {$this->id} AND accepted = 1");
        DB::inst()->query("UPDATE suggestions_users SET accepted = 1 WHERE id = $suggestions_users_id LIMIT 1");

        $accepted_suggestions_after = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
                suggestion_id = {$this->id} AND accepted = 1");
        // The first acceptance for the suggestion
        Logger::debug(__METHOD__ . " accepted before: $accepted_suggestions_before, after: $accepted_suggestions_after");
        if ($accepted_suggestions_before == 1 && $accepted_suggestions_after > 1) {
            $current_user = new User();
            $current_user->fetch(1);
            $current_user->notifyAcceptedSuggestion($this);
        }
    }
}