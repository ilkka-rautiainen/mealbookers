<?php

class User
{

    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $language;
    public $role;
    public $joined;
    private $notify_suggestion_received;
    private $notify_suggestion_accepted;
    private $notify_suggestion_left_alone;
    private $notify_suggestion_deleted;
    private $notify_group_memberships;
    private $initials;
    private $groups;
    private $groupmates;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM users WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new NotFoundException("Unable to find user with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new NotFoundException("Error fetching user: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->email_address = $row['email_address'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->language = $row['language'];
        $this->role = $row['role'];
        $this->joined = $row['joined'];
        $this->notify_suggestion_received = $row['notify_suggestion_received'];
        $this->notify_suggestion_accepted = $row['notify_suggestion_accepted'];
        $this->notify_suggestion_left_alone = $row['notify_suggestion_left_alone'];
        $this->notify_suggestion_deleted = $row['notify_suggestion_deleted'];
        $this->notify_group_memberships = $row['notify_group_memberships'];
        $this->initials = '';
        $this->groups = array();
        $this->groupmates = array();
    }

    public function delete()
    {
        EventLog::inst()->add('user', $this->id);
        DB::inst()->query("DELETE FROM users WHERE id = {$this->id}");
    }

    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getNotificationSettingsAsArray()
    {
        return array(
            'suggestion' => array(
                'received' => (boolean)$this->notify_suggestion_received,
                'accepted' => (boolean)$this->notify_suggestion_accepted,
                'left_alone' => (boolean)$this->notify_suggestion_left_alone,
                'deleted' => (boolean)$this->notify_suggestion_deleted,
            ),
            'group' => array(
                'memberships' => (boolean)$this->notify_group_memberships,
            ),
        );
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
     * This checks if user has something changed since the given timstamp.
     * Used for minimizing data trafic to frontend live view.
     */
    public function hasUpdatesAfter($timestamp)
    {
        Logger::debug(__METHOD__ . " user {$this->id} after $timestamp");

        $timestamp = (int)$timestamp;

        // The first call
        if ($timestamp == 0)
            return true;

        // Older than a limit
        if ($timestamp < time() - Conf::inst()->get('limits.force_ui_refresh'))
            return true;

        // There's some event in the log
        DB::inst()->query("SELECT time FROM event_log WHERE
            time > $timestamp AND (user_id = {$this->id} OR user_id IS NULL) LIMIT 1");
        if (DB::inst()->getRowCount())
            return true;

        return false;
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
        array_push($groupmates, $this);

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

    public function isMemberOfGroup(Group $group)
    {
        DB::inst()->query("SELECT user_id FROM group_memberships WHERE
            user_id = {$this->id} AND group_id = {$group->id} LIMIT 1");

        return (DB::inst()->getRowCount() > 0);
    }

    public function joinGroup(Group $group)
    {
        Logger::info(__METHOD__ . " user {$this->id} to group {$group->id}");

        DB::inst()->query("INSERT INTO group_memberships (user_id, group_id, joined)
            VALUES ({$this->id}, {$group->id}, " . time() . ")");
        EventLog::inst()->add('group', $group->id);
    }

    public function leaveGroup(Group $group)
    {
        Logger::info(__METHOD__ . " user {$this->id} from group {$group->id}");

        EventLog::inst()->add('group', $group->id);
        DB::inst()->query("DELETE FROM group_memberships
            WHERE user_id = {$this->id} AND group_id = {$group->id}");
    }

    public function sendSuggestion(Suggestion $suggestion, $token)
    {
        Logger::info(__METHOD__ . " inviting user {$this->id} to suggestion {$suggestion->id} with token $token");
        if (!$this->notify_suggestion_received) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

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
                '{http_host}',
                '{token}',
            ),
            array(
                $creator->getName(),
                Application::inst()->formatWeekDay($suggestion->getDate("Y-m-d"), $this),
                $restaurant->name,
                $restaurant->getMenuForEmail($suggestion, $this),
                $suggestion->getTime(),
                Application::inst()->getHttpHost(),
                $token,
            ),
            Lang::inst()->get('mailer_body_suggestion', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function notifySuggestionAccepted(Suggestion $suggestion, User $accepter, $is_creator)
    {
        Logger::info(__METHOD__ . " notifying user {$this->id} for having a suggestion"
            . " {$suggestion->id} accepted");
        if (!$this->notify_suggestion_accepted) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

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
                '{http_host}',
                '{day}',
            ),
            array(
                $accepter->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                Application::inst()->getHttpHost(),
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
        if (!$this->notify_suggestion_left_alone) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

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
                '{http_host}',
                '{day}',
            ),
            array(
                $canceler->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                Application::inst()->getHttpHost(),
                $suggestion->getWeekDay() + 1,
            ),
            Lang::inst()->get('mailer_body_suggestion_left_alone', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function notifySuggestionDeleted(Suggestion $suggestion, User $canceler, Restaurant $restaurant)
    {
        Logger::info(__METHOD__ . " notifying user {$this->id} for deletion of"
            . " suggestion {$suggestion->id}");
        if (!$this->notify_suggestion_deleted) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

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
                '{http_host}',
                '{day}',
            ),
            array(
                $canceler->getName(),
                $suggestion->getDate(),
                $restaurant->name,
                $suggestion->getTime(),
                Application::inst()->getHttpHost(),
                $suggestion->getWeekDay() + 1,
            ),
            Lang::inst()->get('mailer_body_suggestion_deleted', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function inviteNewMember($email_address, Group $group)
    {
        Logger::debug(__METHOD__ . " inviting $email_address to group {$group->id}");

        $code = Application::inst()->generateInvitationCode();
        DB::inst()->startTransaction();
        DB::inst()->query("INSERT INTO invitations (
                email_address,
                group_id,
                inviter_id,
                code
            ) VALUES (
                '" . DB::inst()->quote($email_address) . "',
                {$group->id},
                {$this->id},
                '$code'
            )");

        $subject = str_replace(
            '{inviter}',
            $this->getName(),
            Lang::inst()->get('mailer_subject_invite', $this)
        );
        $body = str_replace(
            array(
                '{inviter}',
                '{group_name}',
                '{http_host}',
                '{code}',
            ),
            array(
                $this->getName(),
                $group->name,
                Application::inst()->getHttpHost(),
                $code,
            ),
            Lang::inst()->get('mailer_body_invite', $this)
        );

        $success = Mailer::inst()->sendToAddress($subject, $body, $email_address, $this);
        if (!$success) {
            DB::inst()->rollbackTransaction();
            return false;
        }
        else {
            DB::inst()->commitTransaction();
            return true;
        }
    }

    public function notifyGroupJoin(Group $group, User $inviter)
    {
        global $current_user, $admin;
        Logger::debug(__METHOD__ . " notifying user {$this->id} for being joined"
            . " as member to group {$group->id} by user {$inviter->id}");
        if (!$this->notify_group_memberships) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

        if ($inviter->id != $current_user->id && $current_user->role == 'admin')
            $inviter_name = $admin->getName($this);
        else
            $inviter_name = $inviter->getName();

        $subject = str_replace(
            array(
                '{inviter}',
                '{group_name}',
            ),
            array(
                $inviter_name,
                $group->name,
            ),
            Lang::inst()->get('mailer_subject_invite_notification', $this)
        );
        $body = str_replace(
            array(
                '{inviter}',
                '{group_name}',
                '{http_host}',
            ),
            array(
                $inviter_name,
                $group->name,
                Application::inst()->getHttpHost(),
            ),
            Lang::inst()->get('mailer_body_invite_notification', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function notifyRemovedFromGroup(Group $group, User $deleter)
    {
        global $current_user, $admin;
        Logger::debug(__METHOD__ . " notifying user {$this->id} for being removed from group {$group->id}");
        if (!$this->notify_group_memberships) {
            Logger::debug(__METHOD__ . " canceled due to user's notification settings");
            return true;
        }

        if ($deleter->id != $current_user->id && $current_user->role == 'admin')
            $deleter_name = $admin->getName($this);
        else
            $deleter_name = $deleter->getName();

        $subject = str_replace(
            array(
                '{deleter}',
                '{group_name}',
            ),
            array(
                $deleter_name,
                $group->name,
            ),
            Lang::inst()->get('mailer_subject_group_leave_notification', $this)
        );
        $body = str_replace(
            array(
                '{deleter}',
                '{group_name}',
                '{http_host}',
            ),
            array(
                $deleter_name,
                $group->name,
                Application::inst()->getHttpHost(),
            ),
            Lang::inst()->get('mailer_body_group_leave_notification', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function notifyPasswordChanged($new_password)
    {
        global $admin;
        Logger::debug(__METHOD__ . " notifying user {$this->id} for password change");

        $admin_name = $admin->getName($this);

        $subject = Lang::inst()->get('mailer_subject_password_change_notification', $this);
        $body = str_replace(
            array(
                '{admin}',
                '{new_password}',
                '{http_host}',
            ),
            array(
                $admin_name,
                $new_password,
                Application::inst()->getHttpHost(),
            ),
            Lang::inst()->get('mailer_body_password_change_notification', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function sendEmailVerification()
    {
        global $admin;
        Logger::debug(__METHOD__ . " sending user {$this->id} email verification mail");

        $token = Application::inst()->insertToken($this->id);

        $subject = Lang::inst()->get('mailer_subject_email_verification', $this);
        $body = str_replace(
            array(
                '{http_host}',
                '{hash}',
            ),
            array(
                Application::inst()->getHttpHost(),
                $token,
            ),
            Lang::inst()->get('mailer_body_email_verification', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }

    public function sendNewPasswordEmail()
    {
        global $admin;
        Logger::debug(__METHOD__ . " sending user {$this->id} new password email");

        $token = Application::inst()->insertToken($this->id);

        $subject = Lang::inst()->get('mailer_subject_new_password', $this);
        $body = str_replace(
            array(
                '{http_host}',
                '{token}',
            ),
            array(
                Application::inst()->getHttpHost(),
                $token,
            ),
            Lang::inst()->get('mailer_body_new_password', $this)
        );

        return Mailer::inst()->send($subject, $body, $this);
    }
}