<?php

class EventLog
{
    private static $instance = null;
    
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
            self::$instance = new EventLog();
        
        return self::$instance;
    }

    /**
     * Adds update events for all users related to an event
     * Suggestion: To all users that have been suggested to
     * Group: To all group members
     * User: To members in all user's groups
     * Restaurant: To everybody
     */
    public function add($object, $object_id)
    {
        Logger::debug(__METHOD__ . " $object $object_id");
        if ($object != 'restaurant') {
            $object_id = (int)$object_id;
            if ($object == 'suggestion') {
                $result = DB::inst()->query("SELECT user_id FROM suggestions_users
                    WHERE suggestions_users.suggestion_id = $object_id");
            }
            else if ($object == 'group') {
                $result = DB::inst()->query("SELECT user_id FROM group_memberships
                    WHERE group_id = $object_id");
            }
            else if ($object == 'user') {
                $result = DB::inst()->query("SELECT DISTINCT user_id FROM group_memberships
                    WHERE group_id IN (
                        SELECT group_id FROM group_memberships WHERE user_id = $object_id
                    )");
            }

            $inserts = array();
            $time = time();
            while ($user_id = DB::inst()->fetchFirstField($result)) {
                $inserts[] = "($time, $user_id)";
            }
            if (count($inserts)) {
                $insert = implode(", ", $inserts);
                DB::inst()->query("INSERT INTO event_log (time, user_id) VALUES $insert");
            }
        }
        // Restaurant
        else {
            DB::inst()->query("INSERT INTO event_log (time, user_id) VALUES (" . time() . ", NULL)");
        }
    }

    public function deleteOld()
    {
        DB::inst()->query("DELETE FROM event_log
            WHERE time < " . (time() - Conf::inst()->get('limits.force_ui_refresh')));
    }
}