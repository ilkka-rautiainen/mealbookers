<?php

class User
{
    
    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $language;
    public $active;

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
            'initials' => $this->getInitials(),
        );
    }

    public function getInitials()
    {
        return substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1);
    }

    public function getGroups()
    {
        $groups = array();

        $groups_result = DB::inst()->query("SELECT group_id FROM group_memberships WHERE user_id = {$this->id}");
        while ($group_id = DB::inst()->fetchFirstField($groups_result)) {
            $group_row = DB::inst()->getRowAssoc("SELECT id, name FROM groups WHERE id = $group_id");
            $members = array();

            $group_users_result = DB::inst()->query("SELECT user_id FROM group_memberships WHERE
                group_id = $group_id AND user_id != {$this->id}");
            while ($user_id = DB::inst()->fetchFirstField($group_users_result)) {
                $user = new User();
                $user->fetch($user_id);
                $members[] = $user->getAsArray();
            }

            $groups[] = array(
                'id' => $group_row['id'],
                'name' => $group_row['name'],
                'members' => $members,
            );
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
                '{suggestion_time}',
                '{server_hostname}',
                '{hash}',
            ),
            array(
                $creator->getName(),
                $suggestion->getDate(),
                $restaurant->name,
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