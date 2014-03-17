<?php

Flight::route('GET /users', array('UsersAPI', 'searchUsers'));

class UsersAPI
{
    /**
     * @todo  implement with real user id from current user + implement auth
     */
    function searchUsers()
    {
        Logger::debug(__METHOD__ . " GET /users called");
        Application::inst()->checkAuthentication('admin');

        $user = (isset($_GET['user'])) ? $_GET['user'] : '';
        $group = (isset($_GET['group'])) ? $_GET['group'] : '';

        if (!$user && !$group) {
            return print json_encode(array(
                'status' => 'no_search_term',
            ));
        }
        
        $user = str_replace("*", "%", DB::inst()->quote($user));
        $group = str_replace("*", "%", DB::inst()->quote($group));

        if ($user && !$group) {
            $result = DB::inst()->query("SELECT users.*, GROUP_CONCAT(groups.name SEPARATOR ', ') groups FROM users
                INNER JOIN group_memberships ON users.id = group_memberships.user_id
                INNER JOIN groups ON groups.id = group_memberships.group_id
                WHERE users.email_address LIKE '$user%' OR
                CONCAT_WS(' ', users.first_name, users.last_name) LIKE '$user%' OR
                users.first_name LIKE '$user%' OR
                users.last_name LIKE '$user%'
                GROUP BY users.id
                LIMIT 30
            ");
        }
        else if ($group && !$user) {
            $result = DB::inst()->query("SELECT users.*, GROUP_CONCAT(all_groups.name SEPARATOR ', ') groups FROM users
                INNER JOIN group_memberships ON users.id = group_memberships.user_id
                INNER JOIN groups ON groups.id = group_memberships.group_id
                INNER JOIN groups AS all_groups ON all_groups.id IN (SELECT group_id FROM group_memberships WHERE user_id = users.id)
                WHERE groups.name LIKE '$group%'
                GROUP BY users.id
                LIMIT 30
            ");
        }
        else {
            $result = DB::inst()->query("SELECT users.*, GROUP_CONCAT(all_groups.name SEPARATOR ', ') groups FROM users
                INNER JOIN group_memberships ON users.id = group_memberships.user_id
                INNER JOIN groups ON groups.id = group_memberships.group_id
                INNER JOIN groups AS all_groups ON all_groups.id IN (SELECT group_id FROM group_memberships WHERE user_id = users.id)
                WHERE (users.email_address LIKE '$user%' OR
                CONCAT_WS(' ', users.first_name, users.last_name) LIKE '$user%' OR
                users.first_name LIKE '$user%' OR
                users.last_name LIKE '$user%') AND
                groups.name LIKE '$group%'
                GROUP BY users.id
                LIMIT 30
            ");
        }

        $results = array();
        while ($row = DB::inst()->fetchAssoc($result)) {
            $results[] = array(
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'email_address' => $row['email_address'],
                'groups' => $row['groups'],
            );
        }

        print json_encode(array(
            'status' => 'ok',
            'results' => $results,
        ));
    }
}