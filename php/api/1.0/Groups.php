<?php

Flight::route('POST /user(/@userId)/groups/@groupId/members', array('GroupAPI', 'inviteGroupMember'));
Flight::route('POST /user(/@userId)/groups/@groupId', array('GroupAPI', 'editGroupName'));
Flight::route('POST /user(/@userId)/groups', array('GroupAPI', 'addGroup'));
Flight::route('DELETE /user(/@userId)/groups/@groupId/members/@memberId', array('GroupAPI', 'removeGroupMember'));

class GroupAPI
{
    function addGroup($userId = null)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " POST /user/$userId/groups called");
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                Application::inst()->exitWithHttpCode(404, "No user found with id $userId");
            }
        }
        else {
            Logger::debug(__METHOD__ . " POST /user/groups called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        try {
            $data = Application::inst()->getPostData();

            if (!isset($data['name'])) {
                Application::inst()->exitWithHttpCode(400, "name not present in request");
            }

            if (!mb_strlen(trim($data['name']))) {
                throw new ApiException('invalid_name');
            }

            DB::inst()->query("INSERT INTO groups (name, creator_id)
                VALUES ('" . DB::inst()->quote($data['name']) . "', {$user->id})");
            $group_id = DB::inst()->getInsertId();
            EventLog::inst()->add('group', $group_id);

            $group = new Group();
            $group->fetch($group_id);
            $user->joinGroup($group);

            // Admin made the group
            if ($user->id != $current_user->id) {
                if (!$user->notifyGroupJoin($group, $user))
                    throw new ApiException('ok_but_notification_failed');
            }

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

    function editGroupName($userId, $groupId)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " POST /user/$userId/groups/$groupId called");
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                Application::inst()->exitWithHttpCode(404, "No user found with id $userId");
            }
        }
        else {
            Logger::debug(__METHOD__ . " POST /user/groups/$groupId called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        $groupId = (int)$groupId;
        $data = Application::inst()->getPostData();

        try {
            $group = new Group();
            $group->fetch($groupId);
        }
        catch (NotFoundException $e) {
            Application::inst()->exitWithHttpCode(404, "No group found with the given id");
        }

        if (!$user->isMemberOfGroup($group)) {
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
            EventLog::inst()->add('group', $groupId);

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

    function inviteGroupMember($userId, $groupId)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " POST /user/$userId/groups/@groupId/members called");
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                Application::inst()->exitWithHttpCode(404, "No user found with id $userId");
            }
        }
        else {
            Logger::debug(__METHOD__ . " POST /user/groups/@groupId/members called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        $groupId = (int)$groupId;
        $data = Application::inst()->getPostData();

        try {
            $group = new Group();
            $group->fetch($groupId);
        }
        catch (NotFoundException $e) {
            Application::inst()->exitWithHttpCode(404, "No group found with the given id");
        }

        if (!$user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        $notification_error = false;
        try {
            DB::inst()->startTransaction();
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
                if (!$invitee->notifyGroupJoin($group, $user)) {
                    $notification_error = true;
                }

                print json_encode(array(
                    'status' => 'joined_existing',
                    'notification_error' => $notification_error,
                ));
            }
            // Invite new member
            else {
                if (!$user->inviteNewMember($email_address, $group)) {
                    throw new ApiException('failed_to_send_invite');
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

    function removeGroupMember($userId, $groupId, $memberId)
    {
        global $current_user;

        if ($userId) {
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                Application::inst()->exitWithHttpCode(404, "No user found with id $userId");
            }
            Logger::info(__METHOD__ . " DELETE /user/$userId/groups/$groupId/members/$memberId called by user {$user->id}");
        }
        else {
            Application::inst()->checkAuthentication();

            $user = &$current_user;
            Logger::info(__METHOD__ . " DELETE /user/groups/$groupId/members/$memberId called by user {$user->id}");
        }
        
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

        if (!$user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        if ($user->id != $deleted_member->id && !$deleted_member->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(400, "Member you are deleting is not a member in the group");
        }

        $deleted_member->leaveGroup($group);

        // User deletes himself
        if ($deleted_member->id == $user->id) {

            $last_member = false;
            // He was the last member in the group
            if (!$group->hasMembers()) {
                $group->delete();
                $last_member = true;
            }

            // Admin deleted him
            if ($user->id != $current_user->id)
                $user->notifyRemovedFromGroup($group, $user);

            print json_encode(array(
                'status' => 'removed_himself',
                'last_member' => $last_member,
            ));
        }
        // He deletes someone other
        else {
            $deleted_member->notifyRemovedFromGroup($group, $user);
            print json_encode(array(
                'status' => 'ok',
            ));
        }
    }
}