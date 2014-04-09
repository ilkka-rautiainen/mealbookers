<?php

class SuggestionUser {
    
    public $id;
    public $suggestion_id;
    public $user_id;
    public $accepted;
    public $accepted_timestamp;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM suggestions_users WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new NotFoundException("Unable to find suggestion_user with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new NotFoundException("Error fetching suggestion_user: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->suggestion_id = $row['suggestion_id'];
        $this->user_id = $row['user_id'];
        $this->accepted = $row['accepted'];
        $this->accepted_timestamp = $row['accepted_timestamp'];
    }

    public function accept()
    {
        // New joiner
        if (!$this->accepted_timestamp)
        {
            $this->accepted_timestamp = time();
            $this->accepted = 1;
            DB::inst()->query("UPDATE suggestions_users SET accepted = {$this->accepted},
                accepted_timestamp = {$this->accepted_timestamp} WHERE id = {$this->id}");

        }
        // Old joiner -> preserve timestamp of old join in order to get back the previous position
        else {
            $this->accepted = 1;
            DB::inst()->query("UPDATE suggestions_users SET accepted = {$this->accepted}
                WHERE id = {$this->id}");
        }
        EventLog::inst()->add('suggestion', $this->suggestion_id);
    }

    public function cancel()
    {
        $this->accepted = 0;
        DB::inst()->query("UPDATE suggestions_users SET accepted = {$this->accepted}
            WHERE id = {$this->id}");
        EventLog::inst()->add('suggestion', $this->suggestion_id);
    }
}