<?php

Flight::route('GET /user/groups', array('UserAPI', 'getGroups'));

class UserAPI
{
    /**
     * @todo  separate members with identical initials
     * @todo  implement with real user id from current user + implement auth
     */
    function getGroups()
    {
        Logger::debug(__METHOD__ . " GET /user/groups called");

        $groups = array();

        $groups_result = DB::inst()->query("SELECT group_id FROM group_memberships WHERE user_id = 1");
        while ($group_id = DB::inst()->fetchFirstField($groups_result)) {
            $group_row = DB::inst()->getRowAssoc("SELECT id, name FROM groups WHERE id = $group_id");
            $members = array();

            $group_users_result = DB::inst()->query("SELECT user_id FROM group_memberships WHERE
                group_id = $group_id AND user_id != 1");
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
        print json_encode($groups);
    }
}