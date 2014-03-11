<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user', array('UserAPI', 'updateUser'));
Flight::route('DELETE /user', array('UserAPI', 'deleteUser'));
Flight::route('POST /user/groups/@groupId', array('UserAPI', 'editGroupName'));
Flight::route('POST /user/groups/@groupId/members', array('UserAPI', 'inviteGroupMember'));
Flight::route('DELETE /user/groups/@groupId/members/@memberId', array('UserAPI', 'deleteGroupMember'));
Flight::route('POST /user/login', array('UserAPI', 'login'));
Flight::route('POST /user/registerUser', array('UserAPI', 'registerUser'));

class UserAPI
{
    /**
     * @todo  implement with real user id from current user + implement auth
     */
    function getUser()
    {
        Logger::debug(__METHOD__ . " GET /user called");
        Application::inst()->checkAuthentication();

        $current_user = new User();
        $current_user->fetch(1);
        $user = $current_user->getAsArray();
        $current_user_array = $user;

        $groups = $current_user->getGroupsAsArray();
        $user['groups'] = $groups;
        $user['me'] = $current_user_array;
        $user['email_address'] = $current_user->email_address;
        $user['config'] = Application::inst()->getFrontendConfiguration();
        $user['language'] = $current_user->language;

        print json_encode($user);
    }

    /**
     * @todo implement with real user
     */
    function deleteUser()
    {
        Logger::debug(__METHOD__ . " DELETE /user called");
        Application::inst()->checkAuthentication();

        $current_user = new User();
        $current_user->fetch(1);

        $current_user->delete();

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    /**
     * @todo  implement with real user id from current user + implement auth
     */
    function updateUser()
    {
        Logger::debug(__METHOD__ . " POST /user called");
        Application::inst()->checkAuthentication();

        try {
            DB::inst()->startTransaction();

            $current_user = new User();
            $current_user->fetch(1);

            $data = Application::inst()->getPostData();

            if (!isset($data['password'])
                || !isset($data['password']['old'])
                || !isset($data['password']['new'])
                || !isset($data['password']['repeat'])
            ) {
                Application::inst()->exitWithHttpCode(400, "Invalid password object");
            }

            if ($data['password']['new'] || $data['password']['repeat']) {
                if (!$data['password']['old']) {
                    throw new ApiException('no_old_password');
                }
                else if ($data['password']['new'] != $data['password']['repeat']) {
                    throw new ApiException('passwords_dont_match');
                }
                $old_hash = DB::inst()->getOne("SELECT passhash FROM users WHERE id = {$current_user->id}");
                if ($old_hash != Application::inst()->hash($data['password']['old'])) {
                    throw new ApiException('wrong_password');
                }
                else if (!Application::inst()->isStrongPassword($data['password']['new'], $current_user)) {
                    throw new ApiException('weak_password');
                }
                else {
                    DB::inst()->query("UPDATE users
                        SET passhash = '" . Application::inst()->hash($data['password']['new']) . "'
                        WHERE id = {$current_user->id}");
                }
            }
            else if ($data['password']['old']) {
                throw new ApiException('no_new_password');
            }


            DB::inst()->commitTransaction();
            print json_encode(array(
                'status' => 'ok'
            ));
        }
        catch (ApiException $e) {
            print json_encode(array(
                'status' => $e->getMessage()
            ));
        }
    }

    /**
     * @todo implement with real current user
     */
    function editGroupName($groupId)
    {
        Logger::debug(__METHOD__ . " POST /user/groups/$groupId called");
        Application::inst()->checkAuthentication();

        $current_user = new User();
        $current_user->fetch(1);

        $groupId = (int)$groupId;
        $data = Application::inst()->getPostData();

        try {
            $group = new Group();
            $group->fetch($groupId);
        }
        catch (NotFoundException $e) {
            Application::inst()->exitWithHttpCode(404, "No group found with the given id");
        }

        if (!$current_user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        try {
            // Edit group
            if (!isset($data['name'])) {
                Application::inst()->exitWithHttpCode(400, "name not present in request");
            }
                
            $name = $data['name'];
            if (!strlen($name)) {
                throw new ApiException('invalid_name');
            }

            DB::inst()->query("UPDATE groups SET name = '" . DB::inst()->quote($name) . "'
                WHERE id = $groupId");

            print json_encode(array(
                'status' => 'ok',
            ));
        }
        catch (ApiException $e) {
            print json_encode(array(
                'status' => $e->getMessage()
            ));
        }
    }

    /**
     * @todo implement with real current user
     */
    function inviteGroupMember($groupId)
    {
        Logger::debug(__METHOD__ . " POST /user/groups/@groupId/members called");
        Application::inst()->checkAuthentication();

        $current_user = new User();
        $current_user->fetch(1);

        $groupId = (int)$groupId;
        $data = Application::inst()->getPostData();

        try {
            $group = new Group();
            $group->fetch($groupId);
        }
        catch (NotFoundException $e) {
            Application::inst()->exitWithHttpCode(404, "No group found with the given id");
        }

        if (!$current_user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        try {
            DB::inst()->startTransaction();
            // Edit group
            if (!isset($data['email_address'])) {
                Application::inst()->exitWithHttpCode(400, "email_address not present in request");
            }
                
            $email_address = $data['email_address'];
            if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/", strtoupper($email_address))) {
                throw new ApiException('invalid_email');
            }

            // Check if there's already a user with that email
            if ($invitee_id = DB::inst()->getOne("SELECT id FROM users
                WHERE email_address = '" . DB::inst()->quote($email_address) . "'"))
            {
                $invitee = new User();
                $invitee->fetch($invitee_id);
                if ($invitee->isMemberOfGroup($group)) {
                    throw new ApiException('already_member');
                }
                $invitee->joinGroup($group);
                if (!$invitee->sendGroupInviteNotification($group, $current_user)) {
                    throw new ApiException('failed');
                }

                print json_encode(array(
                    'status' => 'joined_existing',
                    'group' => $group->getAsArray($current_user, $current_user->getInitialsContext()),
                ));
            }
            // Invite new member
            else {
                if (!$current_user->invite($email_address, $group)) {
                    throw new ApiException('failed');
                }
                print json_encode(array(
                    'status' => 'invited_new',
                ));
            }

            DB::inst()->commitTransaction();
        }
        catch (ApiException $e) {
            DB::inst()->rollbackTransaction();
            print json_encode(array(
                'status' => $e->getMessage()
            ));
        }
    }

    /**
     * @todo implement with real current user
     * @todo implement current user case
     */
    function deleteGroupMember($groupId, $memberId)
    {
        Application::inst()->checkAuthentication();
        $current_user = new User();
        $current_user->fetch(1);

        Logger::info(__METHOD__ . " DELETE /user/groups/$groupId/members/$memberId called by user {$current_user->id}");
        
        $groupId = (int)$groupId;
        $memberId = (int)$memberId;

        try {
            $group = new Group();
            $group->fetch($groupId);
            $deleted_member = new User();
            $deleted_member->fetch($memberId);
        }
        catch (NotFoundException $e) {
            Application::inst()->exitWithHttpCode(404, "No group/member found with the given id");
        }

        if (!$deleted_member->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(400, "Member you are deleting is not a member in the group");
        }

        if (!$current_user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        $deleted_member->leaveGroup($group);
        $deleted_member->sendGroupLeaveNotification($group, $current_user);

        if ($deleted_member->id == $current_user->id) {
            print json_encode(array(
                'status' => 'removed_yourself',
            ));
        }
        else {
            print json_encode(array(
                'status' => 'ok',
            ));
        }
    }

    function login()
    {
        Logger::debug(__METHOD__ . " GET /user/login called");

        $data = Application::inst()->getPostData();

        $passhash = Application::inst()->hash($data["password"]);


        $user_id = DB::inst()->getOne("SELECT id FROM users WHERE
            email_address = '" . DB::inst()->quote($data["email"]) . "' AND
            passhash = '$passhash' AND active = 1");

        if ($user_id) {

            if ($data["remember"]) {
                $expiry_time = PHP_INT_MAX;
            }
            else {
                $expiry_time = 0;
            }
            setcookie("id", $user_id, $expiry_time, '/');
            setcookie("check", Application::inst()->hash($passhash), $expiry_time, '/');

            print json_encode(array(
                'status' => 'ok',
            ));
        
        }
        else {
            print json_encode(array(
                'status' => 'fail',
            ));
        }
    }

    function registerUser()
    {
        Logger::debug(__METHOD__ . " GET /user/registerUser called");

        $data = Application::inst()->getPostData();

        $result = DB::inst()->query("SELECT id FROM users WHERE email_address='".$data["email"]."'");
        $row = DB::inst()->fetchAssoc($result);

        
        if (count($row) == 0){
            /**
            * @todo Implement email costruction and sending 
            */
            //INSERT INTO `app`.`users` (`email_address`, `passhash`, `first_name`, `last_name`, `language`, `joined`) VALUES ('simo.hsv@suomi24.fi', 'sakjdölsaköldsa', 'Simo', 'Haakana', 'fi', '234');
            $result = DB::inst()->query("INSERT INTO `app`.`users` (`email_address`, `passhash`, `first_name`, `last_name`, `language`, `joined`) VALUES ('".$data["email"]."', '".sha1($data["password"])."', '".$data["firstName"]."', '".$data["lastName"]."', 'fi', '".time()."')");

            echo json_encode(array('response' => "ok" ));
        
        }
        else {
            echo json_encode(array('response' => "fail" ));
        }
    }
}