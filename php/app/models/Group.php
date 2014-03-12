<?php

class Group {
    
    public $id;
    public $name;
    public $creator_id;
    private $members;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM groups WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new NotFoundException("Unable to find group with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new NotFoundException("Error fetching group: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->creator_id = $row['creator_id'];
        $this->members = array();
    }

    public function hasMembers()
    {
        DB::inst()->query("SELECT group_id FROM group_memberships WHERE group_id = {$this->id} LIMIT 1");

        return (DB::inst()->getRowCount() > 0);
    }

    public function delete()
    {
        DB::inst()->query("DELETE FROM groups WHERE id = {$this->id}");
    }

    /**
     * Fetches members in the group except the viewer if it's given.
     * NOTE: You can't fetch member's for a different viewer later on.
     */
    private function fetchMembers(User $viewer = null)
    {
        Logger::debug(__METHOD__ . " group {$this->id}");
        if (count($this->members)) {
            Logger::debug(__METHOD__ . " already fetched");
            return;
        }

        $viewer_clause = "";
        if (!is_null($viewer)) {
            $viewer_clause = " AND user_id != {$viewer->id} ";
        }

        $members = array();
        $result = DB::inst()->query("SELECT user_id FROM group_memberships
            WHERE group_id = {$this->id}" . $viewer_clause);
        while ($member_id = DB::inst()->fetchFirstField($result)) {
            $member = new User();
            $member->fetch($member_id);
            $members[] = $member;
        }

        $this->members = $members;
    }

    /**
     * Returns array of members with initials created in the given context
     */
    public function getMembersAsArray(User $viewer, $initials_context)
    {
        $this->fetchMembers($viewer);
        $members = array();
        foreach ($this->members as $member) {
            $member->createInitialsInContext($initials_context);
            $members[] = $member->getAsArray();
        }
        return $members;
    }

    public function getMembers(User $viewer = null)
    {
        $this->fetchMembers($viewer);
        return $this->members;
    }

    /**
     * Returns the group with its members initials in the given context
     */
    public function getAsArray(User $viewer, $initials_context)
    {
        Logger::debug(__METHOD__ . " for viewer {$viewer->id}");

        if ($this->creator_id) {
            $creator = new User();
            $creator->fetch($this->creator_id);
            $creator_array = $creator->getAsArray();
        }
        else {
            $creator_array = null;
        }

        return array(
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $creator_array,
            'members' => $this->getMembersAsArray($viewer, $initials_context),
        );
    }
}