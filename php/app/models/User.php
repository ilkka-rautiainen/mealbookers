<?php

class User
{
    
    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $language;
    public $active;
    public $joined;
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
        $this->joined = $row['joined'];
        $this->initials = '';
        $this->groups = array();
        $this->groupmates = array();
    }

    /**
     * @todo check here if this is current user and if it is -> remove login
     */
    public function delete()
    {
        DB::inst()->query("DELETE FROM users WHERE id = {$this->id}");
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
            'initials' => $this->initials,
        );
    }

    /**
     * This function makes a context for the user's groupmates and suggestion outside members
     * initials.
     * 
     * @param  array  $outside_members  Members who are NOT in the user's groups but who are to be considered as well in the context
     */
    public function getInitialsContext($outside_members = array())
    {
        Logger::debug(__METHOD__ . " user {$this->id} and outside_members amount " . count($outside_members));
        $groupmates = $this->getGroupmates();

        $names = array();
        foreach ($groupmates as $member) {
            if (!isset($names[$member->first_name])) {
                $names[$member->first_name] = array();
            }
            $names[$member->first_name][] = array(
                'id' => $member->id,
                'last_name' => $member->last_name,
                'joined' => $member->joined,
                'outside_member' => false,
            );
        }
        foreach ($outside_members as $member) {
            if (!isset($names[$member->first_name])) {
                $names[$member->first_name] = array();
            }
            $names[$member->first_name][] = array(
                'id' => $member->id,
                'last_name' => $member->last_name,
                'joined' => $member->joined,
                'outside_member' => true,
            );
        }

        return $names;
    }

    /**
     * Creates initials in a context returned by User::getInitialsContext
     */
    public function createInitialsInContext($intials_context)
    {
        Logger::debug(__METHOD__ . " user {$this->id}");
        if (!isset($intials_context[$this->first_name])) {
            Logger::error(__METHOD__ . " intials context didn't contain user's {$this->id}"
                . " first name {$this->first_name}, using first name as initials."
                . " Context: " . print_r($intials_context, true));
            $this->initials = $this->first_name;
            return;
        }

        // Get context for only guys with the same first name
        $members_with_same_first_name = $intials_context[$this->first_name];

        if (count($members_with_same_first_name) == 1) {
            $this->initials = $this->first_name;
            return;
        }

        $max_letters = Conf::inst()->get('initialsMaxLettersFromLastName');

        // Array walk callback, takes $n first letters of the last_name
        $getFirstLetters = function(&$item, $key, $n)
        {
            $item['last_name'] = mb_substr($item['last_name'], 0, $n);
        };
        
        // Helper function: returns only names as array from last_name_contexts array
        $getLastNames = function($job_array)
        {
            $last_names = array();
            foreach ($job_array as $partial_context) {
                $last_names[] = $partial_context['last_name'];
            }
            return $last_names;
        };

        $first_different_letter = 0;
        for ($i = 1; $i <= $max_letters; $i++) {
            // if (mb_substr($this->last_name, 0, )
            $job_array = $members_with_same_first_name;
            array_walk($job_array, $getFirstLetters, $i);
            if (count(array_unique($getLastNames($job_array))) == count($getLastNames($job_array))) {
                $first_different_letter = $i;
                break;
            }
        }

        // One of 1..n letters was different (where n is initialsMaxLettersFromLastName in config)
        if ($first_different_letter >= 1) {
            $this->initials = $this->first_name . " "
                . mb_substr($this->last_name, 0, $first_different_letter)
                . ((mb_strlen($this->last_name) == $first_different_letter) ? "" : ".");
        }
        // n+ letter was different, use only a number
        else {
            // Calculate the number here, the oldest user has the lowest number
            $joined_values = $outside_member_values = array();
            foreach ($members_with_same_first_name as $member) {
                $joined_values[] = $member['joined'];
                $outside_member_values[] = $member['outside_member'];
            }
            array_multisort(
                $outside_member_values, SORT_ASC, // First group members
                $joined_values, SORT_ASC, // Secondary: First those who joined first
                $members_with_same_first_name // Sort this array
            );
            $number = -1;
            for ($i = 0; $i < count($members_with_same_first_name); $i++) {
                if ($members_with_same_first_name[$i]['id'] == $this->id)
                {
                    $number = $i;
                    break;
                }
            }
            if ($number == -1) {
                Logger::error(__METHOD__ . " no number found for user {$this->id}"
                    . " as the members_with_same_first_name didn't contain him."
                    . " members_with_same_first_name: " . print_r($members_with_same_first_name, true));
                $this->initials = $this->first_name;
                return;
            }
            $this->initials = $this->first_name . " " . ($number + 1);
        }
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
     * Fetches user's all groupmates in all his groups as User objects
     */
    private function fetchGroupmates()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");

        if (count($this->groupmates)) {
            Logger::debug(__METHOD__ . " already fetched");
        }

        $this->fetchGroups();

        $groupmates = $groupmate_ids = array();
        foreach ($this->groups as $group) {
            foreach ($group->getMembers($this) as $member) {
                if (in_array($member->id, $groupmate_ids)) {
                    continue;
                }
                $groupmates[] = $member;
                $groupmate_ids[] = $member->id;
            }
        }

        $this->groupmates = $groupmates;
    }

    public function getGroupmates()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");
        $this->fetchGroupmates();
        return $this->groupmates;
    }

    /**
     * Returns user's groups as array (for being passed as JSON to frontend)
     * @return array of groups
     */
    public function getGroupsAsArray()
    {
        Logger::debug(__METHOD__ . " user {$this->id}");

        $this->fetchGroups();

        // Get current user's initials context
        $intials_context = $this->getInitialsContext();

        $groups = array();
        foreach ($this->groups as $group) {
            $groups[] = $group->getAsArray($this, $intials_context);
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