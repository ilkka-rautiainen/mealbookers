<?php

class Notifications
{
    private static $instance = null;
    const NOTIFICATION_TYPE_SUGGEST = 1;
    const NOTIFICATION_TYPE_CANCEL = 2;
    const NOTIFICATION_TYPE_ACCEPT = 3;
    const NOTIFICATION_TYPE_LEFT_ALONE = 4;
    
    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {

    }
    
    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Notifications();
        
        return self::$instance;
    }

    /**
     * Adds notifications to be used by any app through the api
     */
    public function add(User $user, Suggestion $suggestion, $type, $token = "", User $other_user = null, $menu = null)
    {
        Logger::debug(__METHOD__ . " for user {$user->id} and suggestion {$suggestion->id} of type $type");

        $suggestion_time_str = $suggestion->getTime();
        $restaurant_name_str = DB::inst()->getOne("SELECT name FROM restaurants WHERE id = {$suggestion->restaurant_id}");
        
        if ($other_user)
            $other_user_first_name = $other_user->first_name;
        else
            $other_user_first_name = "";

        DB::inst()->query("INSERT INTO notifications (
                `user_id`,
                `time`,
                `type`,
                `suggestion_id`,
                `token`,
                `other_user_id`,
                `suggestion_time_str`,
                `restaurant_name_str`,
                `other_user_first_name`
            ) VALUES (
                {$user->id},
                " . time() . ",
                " . (int)$type . ",
                {$suggestion->id},
                '" . DB::inst()->quote($token) . "',
                " . (($other_user) ? $other_user->id : 'NULL') . ",
                '" . DB::inst()->quote($suggestion_time_str) . "',
                '" . DB::inst()->quote($restaurant_name_str) . "',
                '" . DB::inst()->quote($other_user_first_name) . "'
            )");
        
        // Get and send the created notification
        $notification_id = DB::inst()->getInsertId();

        $notification = new Notification();
        $notification->populateFromRow(DB::inst()->getRowAssoc("SELECT * FROM notifications WHERE id = $notification_id"));

        if ($notification->suggestion_id) {
            $suggestion = new Suggestion();
            $suggestion->populateFromRow(DB::inst()->getRowAssoc("SELECT * FROM suggestions WHERE id = {$notification->suggestion_id}"));
            $notification->suggestion = $suggestion;

            $restaurant = new Restaurant();
            $restaurant->populateFromRow(DB::inst()->getRowAssoc("SELECT * FROM restaurants WHERE id = {$suggestion->restaurant_id}"));
            $notification->restaurant = $restaurant;
        }

        $initials_context = $user->getInitialsContext();

        $data = array(
            'notification' => $notification->getAsArrayWithInitialsContext($initials_context),
        );

        GCM::inst()->send($user, $data);
    }

    public function deleteOld()
    {
        DB::inst()->query("DELETE FROM notifications
            WHERE time < " . (time() - Conf::inst()->get('limits.notification_validity_time')));
    }
}