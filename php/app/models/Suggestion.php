<?php

class Suggestion {
    
    public $id;
    public $datetime;
    public $creator_id;
    public $restaurant_id;
    private $members;

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

    /**
     * Delete suggestion and notify all members about it
     */
    private function deleteAndNotify(User $canceler)
    {
        $this->notifyDeletionToAll($canceler);
        DB::inst()->query("DELETE FROM suggestions WHERE id = {$this->id}");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->datetime = $row['datetime'];
        $this->creator_id = $row['creator_id'];
        $this->restaurant_id = $row['restaurant_id'];
        $this->members = array();
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

    public function getOutsideMembers(User $viewer)
    {
        $outside_members = array();
        $result = DB::inst()->query("SELECT users.* FROM users
            INNER JOIN suggestions_users ON users.id = suggestions_users.user_id
            INNER JOIN group_memberships ON group_memberships.user_id = users.id
            WHERE suggestions_users.suggestion_id = {$this->id} AND suggestions_users.accepted = 1 AND
            group_memberships.group_id NOT IN (
                SELECT group_id FROM group_memberships WHERE user_id = {$viewer->id}
            )
            ORDER BY suggestions_users.accepted_timestamp ASC");

        while ($outside_member = DB::inst()->fetchAssoc($result)) {
            if (!isset($outside_members[$outside_member['first_name']])) {
                $outside_members[$outside_member['first_name']] = array();
            }
            $outside_members[$outside_member['first_name']][] = $outside_member['last_name'];
        }

        return $outside_members;
    }

    public function fetchMembers(User $viewer)
    {
        $members = array();
        // Get the user's that are invited to the suggestion outside of the viewer's groups
        $outside_members = $this->getOutsideMembers($viewer);

        $result = DB::inst()->query("SELECT users.* FROM users
            INNER JOIN suggestions_users ON users.id = suggestions_users.user_id
            WHERE suggestions_users.suggestion_id = {$this->id} AND suggestions_users.accepted = 1
            ORDER BY suggestions_users.accepted_timestamp ASC");
        while ($row = DB::inst()->fetchAssoc($result)) {
            $member = new User();
            $member->populateFromRow($row);
            $member->createInitialsForSuggestion($viewer, $outside_members);
            $members[] = $member->getAsArray();
        }
        $this->members = $members;
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
            'members' => $this->members,
            'accepted' => $this->hasUserAccepted($current_user),
            'manageable' => $this->isManageable(),
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

    public function isManageable($withBackendThreshold = false)
    {
        $backendThreshold = 0;
        if ($withBackendThreshold) {
            $backendThreshold = Conf::inst()->get('limits.backend_threshold');
        }

        return (strtotime($this->datetime)
            > time() - Conf::inst()->get('limits.suggestion_cancelable_time') - $backendThreshold);
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
                {$this->id},
                {$member->id},
                '$hash',
                " . ($accepted ? '1' : '0') . ",
                " . ($accepted ? time() : 'NULL') . "
            )");
        return $hash;
    }

    /**
     * Sends a notification to all members when the suggestion is deleted
     */
    private function notifyDeletionToAll(User $canceler)
    {
        $restaurant = new Restaurant();
        $restaurant->fetch($this->restaurant_id);
        $result = DB::inst()->query("SELECT user_id FROM suggestions_users
            WHERE suggestion_id = {$this->id} AND accepted = 0 AND user_id != {$canceler->id}");
        while ($member_id = DB::inst()->fetchFirstField($result)) {
            $member = new User();
            $member->fetch($member_id);
            $member->sendSuggestionDeletionNotification($this, $canceler, $restaurant);
        }
    }

    /**
     * Activate suggestion membership
     */
    public function accept(SuggestionUser $suggestion_user)
    {
        Logger::info(__METHOD__ . " accepting suggestion with suggestions_user {$suggestion_user->id}");
        
        $accepted_suggestions_before = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
            suggestion_id = {$this->id} AND accepted = 1");

        // Get the alone one's id
        if ($accepted_suggestions_before == 1) {
            $alone_member_id = DB::inst()->getOne("SELECT user_id FROM suggestions_users
                WHERE suggestion_id = {$this->id} AND accepted = 1 LIMIT 1");
        }

        // Accept it
        $suggestion_user->accept();

        $accepted_suggestions_after = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
                suggestion_id = {$this->id} AND accepted = 1");

        // Notify the alone one not to have to be alone anymore
        if ($accepted_suggestions_before == 1 && $accepted_suggestions_after > 1) {
            $is_creator = ($alone_member_id == $this->creator_id);

            $accepter = new User();
            $accepter->fetch($suggestion_user->user_id);

            $alone_member = new User();
            $alone_member->fetch($alone_member_id);
            $alone_member->notifyAcceptedSuggestion($this, $accepter, $is_creator);
        }
    }

    /**
     * Inactivate suggestion membership
     * @return  true if suggestion was deleted due to no users in it, otherwise false
     */
    public function cancel(SuggestionUser $suggestion_user)
    {
        Logger::info(__METHOD__ . " canceling suggestion with suggestions_user {$suggestion_user->id}");

        $accepted_suggestions_before = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
            suggestion_id = {$this->id} AND accepted = 1");

        // Cancel it
        $suggestion_user->cancel();

        $accepted_suggestions_after = DB::inst()->getOne("SELECT COUNT(id) FROM suggestions_users WHERE
                suggestion_id = {$this->id} AND accepted = 1");

        // No users there anymore
        if ($accepted_suggestions_after == 0) {
            $canceler = new User();
            $canceler->fetch($suggestion_user->user_id);
            $this->deleteAndNotify($canceler);
            return true;
        }

        // Notify the alone one of being left alone
        if ($accepted_suggestions_before >= 1 && $accepted_suggestions_after == 1) {
            $canceler = new User();
            $canceler->fetch($suggestion_user->user_id);
            
            $last_member_id = DB::inst()->getOne("SELECT user_id FROM suggestions_users
                WHERE suggestion_id = {$this->id} AND accepted = 1");
            $last_member = new User();
            $last_member->fetch($last_member_id);
            $last_member->notifyBeenLeftAlone($this, $canceler);
        }
    }
}