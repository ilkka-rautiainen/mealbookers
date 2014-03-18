<?php

Flight::route('POST /user/groups', array('GroupAPI', 'addGroup'));
Flight::route('POST /user/groups/@groupId', array('GroupAPI', 'editGroupName'));
Flight::route('POST /user(/@userId)/groups/@groupId/members', array('GroupAPI', 'inviteGroupMember'));
Flight::route('DELETE /user/groups/@groupId/members/@memberId', array('GroupAPI', 'deleteGroupMember'));

class GroupAPI
{
    /**
     * @todo implement with real current user
     */
    function addGroup()
    {
        Logger::debug(__METHOD__ . " POST /user/groups called");
        Application::inst()->checkAuthentication();

        $current_user = new User();
        $current_user->fetch(1);

        $data = Application::inst()->getPostData();

        if (!isset($data['name'])) {
            Application::inst()->exitWithHttpCode(400, "name not present in request");
        }

        if (!mb_strlen(trim($data['name']))) {
            return print json_encode(array(
                'status' => 'invalid_name',
            ));
        }

        DB::inst()->query("INSERT INTO groups (name, creator_id)
            VALUES ('" . DB::inst()->quote($data['name']) . "', {$current_user->id})");
        $group_id = DB::inst()->getInsertId();
        EventLog::inst()->add('group', $group_id);

        $group = new Group();
        $group->fetch($group_id);
        $current_user->joinGroup($group);

        print json_encode(array(
            'status' => 'ok',
        ));
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

    /**
     * @todo implement with real current user
     */
    function inviteGroupMember($userId, $groupId)
    {
        $current_user = new User();
        $current_user->fetch(1);

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
                if (!$invitee->notifyGroupJoin($group, $user)) {
                    throw new ApiException('failed');
                }

                print json_encode(array(
                    'status' => 'joined_existing',
                    'group' => $group->getAsArray($user, $user->getInitialsContext()),
                ));
            }
            // Invite new member
            else {
                
                if (!$user->inviteNewMember($email_address, $group)) {
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

        if (!$current_user->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(403, "You are not a member in that group");
        }

        if (!$deleted_member->isMemberOfGroup($group)) {
            Application::inst()->exitWithHttpCode(400, "Member you are deleting is not a member in the group");
        }

        $deleted_member->leaveGroup($group);

        // Current user deletes himself
        if ($deleted_member->id == $current_user->id) {

            $last_member = false;
            // He was the last member in the group
            if (!$group->hasMembers()) {
                $group->delete();
                $last_member = true;
            }

            print json_encode(array(
                'status' => 'removed_yourself',
                'last_member' => $last_member,
            ));
        }
        // He deletes someone other
        else {
            $deleted_member->notifyRemovedFromGroup($group, $current_user);
            print json_encode(array(
                'status' => 'ok',
            ));
        }
    }
}