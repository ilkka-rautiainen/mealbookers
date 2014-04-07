<?php

Flight::route('POST /user(/@userId)/groups/join', array('GroupAPI', 'joinGroup'));
Flight::route('POST /user(/@userId)/groups/@groupId/members', array('GroupAPI', 'inviteMemberToGroup'));
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
                throw new HttpException(404, 'user_not_found');
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
                throw new HttpException(400, 'name_missing');
            }

            if (!mb_strlen(trim($data['name']))) {
                throw new HttpException(409, 'invalid_name');
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
                throw new HttpException(404, 'user_not_found');
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
            throw new HttpException(404, 'group_not_found');
        }

        if (!$user->isMemberOfGroup($group)) {
            throw new HttpException(403, 'not_member_of_group', 'danger');
        }

        // Edit group
        if (!isset($data['name'])) {
            throw new HttpException(400, 'name_missing');
        }

        $name = $data['name'];
        if (!strlen($name)) {
            throw new HttpException(409, 'invalid_name');
        }

        DB::inst()->query("UPDATE groups SET name = '" . DB::inst()->quote($name) . "'
            WHERE id = $groupId");
        EventLog::inst()->add('group', $groupId);

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function inviteMemberToGroup($userId, $groupId)
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
                throw new HttpException(404, 'user_not_found');
            }
        }
        else {
            Logger::debug(__METHOD__ . " POST /user/groups/@groupId/members called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        // Fetch group
        $groupId = (int)$groupId;
        $data = Application::inst()->getPostData();

        try {
            $group = new Group();
            $group->fetch($groupId);
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'group_not_found');
        }

        if (!$user->isMemberOfGroup($group)) {
            throw new HttpException(403, 'not_member_of_group', 'danger');
        }

        $notification_error = false;

        // Check data
        DB::inst()->startTransaction();
        if (!isset($data['email_address'])) {
            throw new HttpException(400, 'email_address_missing');
        }

        $email_address = $data['email_address'];
        if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/", strtoupper($email_address))) {
            throw new HttpException(409, 'invalid_email');
        }

        // Check if there's already a user with that email
        if ($invitee_id = DB::inst()->getOne("SELECT id FROM users
            WHERE email_address = '" . DB::inst()->quote($email_address) . "'"))
        {
            $invitee = new User();
            $invitee->fetch($invitee_id);
            if ($invitee->isMemberOfGroup($group)) {
                throw new HttpException(409, 'already_member');
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
                throw new HttpException(500, 'failed_to_send_invite');
            }
            print json_encode(array(
                'status' => 'invited_new',
            ));
        }

        DB::inst()->commitTransaction();
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
                throw new HttpException(404, 'user_not_found');
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
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'group_not_found');
        }

        try {
            $deleted_member = new User();
            $deleted_member->fetch($memberId);
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'group_member_not_found');
        }

        if (!$user->isMemberOfGroup($group)) {
            throw new HttpException(403, 'not_member_of_group', 'danger');
        }

        if ($user->id != $deleted_member->id && !$deleted_member->isMemberOfGroup($group)) {
            throw new HttpException(400, 'deleted_user_not_member_of_group', 'danger');
        }

        $deleted_member->leaveGroup($group);

        // The active user himself is deleted
        if ($deleted_member->id == $user->id) {

            $last_member = false;
            // He was the last member in the group
            if (!$group->hasMembers()) {
                $group->delete();
                $last_member = true;
            }

            $response = array(
                'status' => 'removed_himself',
                'last_member' => $last_member,
            );

            // Admin deleted him
            if ($user->id != $current_user->id) {
                if (!$user->notifyRemovedFromGroup($group, $user))
                    $response['notification_failed'] = true;
            }

            print json_encode($response);
        }
        // He deletes someone other
        else {
            $notificationSucceeded = $deleted_member->notifyRemovedFromGroup($group, $user);
            print json_encode(array(
                'status' => 'ok',
                'notification_failed' => !$notificationSucceeded,
            ));
        }
    }

    function joinGroup($userId, $groupId)
    {
        global $current_user;

        if ($userId) {
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                throw new HttpException(404, 'user_not_found');
            }
            Logger::info(__METHOD__ . " POST /user/$userId/groups/join called by user {$current_user->id}");
        }
        else {
            Application::inst()->checkAuthentication();

            $user = &$current_user;
            Logger::info(__METHOD__ . " POST /user/groups/join called by user {$current_user->id}");
        }

        // Fetch data and do checks
        $data = Application::inst()->getPostData();

        if (!isset($data['code'])) {
            throw new HttpException(400, 'code_missing');
        }

        // Fetch group
        $group_id = DB::inst()->getOne("SELECT group_id FROM invitations
            WHERE code = '" . DB::inst()->quote($data['code']) . "'");
        if (is_null($group_id))
            throw new HttpException(404, 'invitation_not_found_with_code');

        $group = new Group();
        $group->fetch($group_id);

        if ($user->isMemberOfGroup($group)) {
            DB::inst()->query("DELETE FROM invitations WHERE code = '" . DB::inst()->quote($data['code']) . "'");
            throw new HttpException(409, 'already_member', 'info');
        }

        // Join group and delete invitation
        $user->joinGroup($group);
        DB::inst()->query("DELETE FROM invitations WHERE code = '" . DB::inst()->quote($data['code']) . "'");

        print json_encode(array(
            'status' => 'ok',
        ));
    }
}