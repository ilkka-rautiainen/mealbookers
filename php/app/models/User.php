<?php

class User
{
    
    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $language;
    public $active;
    private $initials;
    private $groups;
    private $groupmates;

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
        $this->language = $row['language'];
        $this->active = $row['active'];
        $this->initials = '';
        $this->groups = array();
        $this->groupmates = array();
    }

    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->getName(),
            'initials' => $this->first_name,
        );
    }

    /**
     * Creates unique initials for the suggestion from the given user's point of view.
     * 
     * This function gets all users in viewer's groups plus those in the suggestion,
     * that are not in the viewer's groups and makes unique initials in that context.
     * 
     * @param  User   $viewer           The user of whichs point of view initials are made
     * @param  array  $outside_members  Array of User objects who are not in viewer's groups
     */
    public function createInitialsForSuggestion(User $viewer, $outside_members)
    {

    }

    /**
     * Creates initials for the suggestion from the given viewer's point of view
     */
    public function createInitialsForGroupView(User $viewer)
    {

    }

    /**
     * Fetches the user's groups and group members.
     * @return array Groups as an array, members as User objects
     */
    private function fetchGroups()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");
        if (count($this->groups)) {
            Logger::debug(__METHOD__ . " already fetched");
            return;
        }

        $groups = array();
        $groups_result = DB::inst()->query("SELECT group_id FROM group_memberships WHERE user_id = {$this->id}");
        while ($group_id = DB::inst()->fetchFirstField($groups_result)) {
            $group = new Group();
            $group->fetch($group_id);
            $groups[] = $group;
        }

        $this->groups = $groups;
    }

    /**
     * Fetches user's all groupmates in all his groups
     */
    private function fetchGroupMates()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");

        if (count($this->groupmates)) {
            Logger::debug(__METHOD__ . " already fetched");
        }

        $this->fetchGroups();

        $groupmates = array();
        foreach ($this->groups as $group) {
            foreach ($group->getMembers($this) as $member) {
                $groupmates[] = $member;
            }
        }

        $this->groupmates = $groupmates;
    }

    /**
     * Returns user's groups as array (for being passed as JSON to frontend)
     * @return array of groups
     * @todo implement the intials for groupview here
     */
    public function getGroupsAsArray()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");

        $this->fetchGroups();

        $groups = array();
        foreach ($this->groups as $group) {
            $groups[] = $group->getAsArray($this);
        }

        return $groups;
    }

    public function sendSuggestionInviteEmail(Suggestion $suggestion, $hash)
    {
        Logger::info(__METHOD__ . " inviting user {$this->id} to suggestion {$suggestion->id} with hash $hash");
        $creator = new User();
        $creator->fetch($suggestion->creator_id);
        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        $subject = str_replace(
            '{suggester}',
            $creator->getName(),
            Lang::inst()->get('mailer_subject_suggestion', $this)
        );
        $body = str_replace(
            array(
                '{suggester}',
                '{suggestion_date}',
                '{restaurant}',
                '{menu}',
                '{suggestion_time}',
                '{server_hostname}',
                '{hash}',
            ),
            array(
                $creator->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $restaurant->getMenuForEmail($suggestion, $this),
                $suggestion->getTime(),
                $_SERVER['HTTP_HOST'],
                $hash,
            ),
            Lang::inst()->get('mailer_body_suggestion', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }
    
    public function notifyAcceptedSuggestion(Suggestion $suggestion, User $accepter, $is_creator)
    {
        Logger::info(__METHOD__ . " notifying user {$this->id} for having a suggestion"
            . " {$suggestion->id} accepted");

        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        $version_postfix = ($is_creator) ? 'creator' : 'other';

        $subject = str_replace(
            '{accepter}',
            $accepter->getName(),
            Lang::inst()->get('mailer_subject_suggestion_accepted_' . $version_postfix, $this)
        );
        $body = str_replace(
            array(
                '{accepter}',
                '{suggestion_date}',
                '{restaurant}',
                '{suggestion_time}',
                '{server_hostname}',
                '{day}',
            ),
            array(
                $accepter->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                $_SERVER['HTTP_HOST'],
                $suggestion->getWeekDay() + 1,
            ),
            Lang::inst()->get('mailer_body_suggestion_accepted_' . $version_postfix, $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }
    
    public function notifyBeenLeftAlone(Suggestion $suggestion, User $canceler)
    {
        Logger::info(__METHOD__ . " notifying user {$this->id} for having"
            . " been left alone for suggestion {$suggestion->id}");

        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        $subject = str_replace(
            '{canceler}',
            $canceler->getName(),
            Lang::inst()->get('mailer_subject_suggestion_left_alone', $this)
        );
        $body = str_replace(
            array(
                '{canceler}',
                '{suggestion_date}',
                '{restaurant}',
                '{suggestion_time}',
                '{server_hostname}',
                '{day}',
            ),
            array(
                $canceler->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                $_SERVER['HTTP_HOST'],
                $suggestion->getWeekDay() + 1,
            ),
            Lang::inst()->get('mailer_body_suggestion_left_alone', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }
    
    public function sendSuggestionDeletionNotification(Suggestion $suggestion, User $canceler, Restaurant $restaurant)
    {
        Logger::info(__METHOD__ . " notifying user {$this->id} for deletion of"
            . " suggestion {$suggestion->id}");

        $subject = str_replace(
            '{canceler}',
            $canceler->getName(),
            Lang::inst()->get('mailer_subject_suggestion_deleted', $this)
        );
        $body = str_replace(
            array(
                '{canceler}',
                '{suggestion_date}',
                '{restaurant}',
                '{suggestion_time}',
                '{server_hostname}',
                '{day}',
            ),
            array(
                $canceler->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                $_SERVER['HTTP_HOST'],
                $suggestion->getWeekDay() + 1,
            ),
            Lang::inst()->get('mailer_body_suggestion_deleted', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }
}