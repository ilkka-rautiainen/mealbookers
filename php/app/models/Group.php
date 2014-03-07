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
            throw new Exception("Unable to find group with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new Exception("Error fetching group: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->creator_id = $row['creator_id'];
        $this->members = array();
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
     * Creates intials for the viewer user's point of view and returns the member array
     */
    public function getMembersAsArray(User $viewer)
    {
        $this->fetchMembers($viewer);
        $members = array();
        foreach ($this->members as $member) {
            $member->createInitialsForGroupView($viewer);
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
     * Returns the group with its members adjusted for the viewer user's point of view
     */
    public function getAsArray(User $viewer)
    {
        Logger::debug(__METHOD__ . " for viewer {$viewer->id}");
        $creator = new User();
        $creator->fetch($this->creator_id);

        return array(
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $creator->getAsArray(),
            'members' => $this->getMembersAsArray($viewer),
        );
    }
}