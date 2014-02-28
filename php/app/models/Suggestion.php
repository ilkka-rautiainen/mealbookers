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

    /**
     * @todo call has user joined with current user
     */
    public function getAsArray()
    {
        // Mockup current user
        $current_user = new User();
        $current_user->fetch(1);

        $creator = new User();
        $creator->fetch($this->creator_id);
        return array(
            'id' => $this->id,
            'time' => $this->getTime(),
            'creator' => $creator->getAsArray(),
            'members' => $this->getMembers(),
            'accepted' => $this->hasUserAccepted($current_user)
        );
    }

    private function hasUserAccepted(User $user)
    {
        $accepted = DB::inst()->getOne("SELECT accepted FROM suggestions_users 
        WHERE user_id = {$user->id} AND suggestion_id = {$this->id} LIMIT 1");

        if (!$accepted) {
            return false;
        }
        else {
            return true;
        }
    }

    public function insertMember(User $member, $accepted)
    {
        Logger::info(__METHOD__ . " inserting member {$member->id} to suggestion {$this->id}");
        $hash = md5(microtime(true) . mt_rand() . "gwoipasoidfugoiauvas92762439)(/%\")(/%¤#¤)/#¤&\")(¤%");
        DB::inst()->query("INSERT INTO suggestions_users (
                suggestion_id,
                user_id,
                hash,
                accepted,
                accepted_timestamp
            ) VALUES (
                {$suggestion->id},
                {$user->id},
                '$hash',
                " . ($accepted ? '1' : '0') . ",
                " . ($accepted ? time() : 'NULL') . "
            )");
    }

    /**
     * Activate suggestion membership
     */
    public function accept($suggestions_users_id)
    {
        Logger::info(__METHOD__ . " accepting suggestion with suggestions_users_id $suggestions_users_id");
        return $this->acceptOrCancel($suggestions_users_id, true);
    }

    /**
     * Inactivate suggestion membership
     */
    public function cancel($suggestions_users_id)
    {
        Logger::info(__METHOD__ . " canceling suggestion with suggestions_users_id $suggestions_users_id");
        return $this->acceptOrCancel($suggestions_users_id, false);
    }

    /**
     * Activate or inactivate suggestion membership
     */
    private function acceptOrCancel($suggestions_users_id, $accept = true)
    {
        $accepted_suggestions_before = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
            suggestion_id = {$this->id} AND accepted = 1");

        // Accept membership
        if ($accept) {
            // New joiner
            if (!DB::inst()->getOne("SELECT accepted_timestamp FROM suggestions_users
                WHERE id = $suggestions_users_id"))
            {
                DB::inst()->query("UPDATE suggestions_users SET accepted = 1,
                    accepted_timestamp = " . time() . " WHERE id = $suggestions_users_id");
            }
            // Old joiner -> preserve timestamp of old join in order to get back the creator position
            else {
                DB::inst()->query("UPDATE suggestions_users SET accepted = 1
                    WHERE id = $suggestions_users_id");
            }
        }
        // Cancel membership
        else {
            DB::inst()->query("UPDATE suggestions_users SET accepted = 0
                WHERE id = $suggestions_users_id");
        }

        // Update the creator to be the first accepted user
        DB::inst()->query("UPDATE suggestions SET creator_id = (SELECT user_id FROM suggestions_users
            WHERE suggestion_id = {$this->id} AND accepted = 1 ORDER BY accepted_timestamp ASC LIMIT 1)
            WHERE id = {$this->id}");

        $accepted_suggestions_after = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
                suggestion_id = {$this->id} AND accepted = 1");

        // The first acceptance for the suggestion
        if ($accepted_suggestions_before == 1 && $accepted_suggestions_after > 1) {
            $current_user = new User();
            $current_user->fetch($this->creator_id);
            $current_user->notifyAcceptedSuggestion($this);
        }
    }
}