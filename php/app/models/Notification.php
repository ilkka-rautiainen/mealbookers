<?php

class Notification {
    
    public $id;
    public $user_id;
    public $time;
    public $type;
    public $token;
    public $other_user_id;
    public $suggestion_id;
    public $suggestion;
    public $restaurant;
    public $other_user;

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->user_id = $row['user_id'];
        $this->time = $row['time'];
        $this->type = $row['type'];
        $this->suggestion_id = $row['suggestion_id'];
        $this->token = $row['token'];
        $this->other_user_id = $row['other_user_id'];
        $this->suggestion_time_str = $row['suggestion_time_str'];
        $this->restaurant_name_str = $row['restaurant_name_str'];
        $this->other_user_first_name = $row['other_user_first_name'];
    }

    public function getAsArrayWithInitialsContext($initials_context, User $viewer)
    {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'time' => $this->time,
            'type' => $this->type,
            'suggestion_id' => $this->suggestion_id,
            'token' => $this->token,
            'suggestion' => ($this->suggestion) ? $this->suggestion->getAsArrayWithInitialsContext($initials_context) : null,
            'restaurant' => ($this->restaurant) ? $this->restaurant->getAsArrayBase() : null,
            'menu' => ($this->restaurant && $this->suggestion && $this->type == Notifications::NOTIFICATION_TYPE_SUGGEST) ? $this->restaurant->getMenuForNotification($this->suggestion, $viewer) : null,
            'other_user' => ($this->other_user_id) ? $this->getOtherUserAsArray($initials_context) : null,
            'suggestion_time_str' => $this->suggestion_time_str,
            'restaurant_name_str' => $this->restaurant_name_str,
            'restaurant_name_str' => $this->restaurant_name_str,
        );
    }

    private function getOtherUserAsArray($initials_context)
    {
        $this->other_user = new User();
        $this->other_user->populateFromRow(DB::inst()->getRowAssoc("SELECT id, first_name, last_name FROM users WHERE id = {$this->other_user_id}"));
        $this->other_user->createInitialsInContext($initials_context);
        return $this->other_user->getAsArray();
    }
}