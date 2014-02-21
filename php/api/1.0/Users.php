<?php

Flight::route('GET /users/@userId/groups', array('UsersAPI', 'getGroups'));

class UsersAPI
{
    /**
     * @url GET {userId}/groups
     * @todo  separate members with identical initials
     */
    function getGroups($userId)
    {
        Logger::debug(__METHOD__ . " GET /users/$userId/groups called");

        $userId = (int)$userId;

        $groups = array();

        $groups_result = DB::inst()->query("SELECT group_id FROM group_memberships WHERE user_id = $userId");
        while ($group_id = DB::inst()->fetchFirstField($groups_result)) {
            $group_row = DB::inst()->getRowAssoc("SELECT id, name FROM groups WHERE id = $group_id");
            $members = array();

            $group_users_result = DB::inst()->query("SELECT user_id FROM group_memberships WHERE
                group_id = $group_id AND user_id != $userId");
            while ($user_id = DB::inst()->fetchFirstField($group_users_result)) {
                $user = new User();
                $user->fetch($user_id);
                $members[] = $user->getAsMemberArray();
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