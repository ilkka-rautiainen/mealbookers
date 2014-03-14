<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user', array('UserAPI', 'updateUser'));
Flight::route('DELETE /user', array('UserAPI', 'deleteUser'));
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

        // Live view: up to date
        if (isset($_GET['after']) && !$current_user->hasUpdatesAfter((int)$_GET['after'])) {
            return print json_encode(array(
                'status' => 'up_to_date',
                'timestamp' => time(),
            ));
        }

        $user = $current_user->getAsArray();
        $current_user_array = $user;

        $groups = $current_user->getGroupsAsArray();
        $user['groups'] = $groups;
        $user['me'] = $current_user_array;
        $user['email_address'] = $current_user->email_address;
        $user['first_name'] = $current_user->first_name;
        $user['last_name'] = $current_user->last_name;
        $user['notification_settings'] = $current_user->getNotificationSettingsAsArray();
        $user['config'] = Application::inst()->getFrontendConfiguration();
        $user['language'] = $current_user->language;
        $user['timestamp'] = time();

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

            if (!isset($data['suggestion'])
                || !isset($data['suggestion']['received'])
                || !isset($data['suggestion']['accepted'])
                || !isset($data['suggestion']['left_alone'])
                || !isset($data['suggestion']['deleted'])
            ) {
                Application::inst()->exitWithHttpCode(400, "Invalid suggestion object");
            }

            if (!isset($data['group'])
                || !isset($data['group']['memberships'])
            ) {
                Application::inst()->exitWithHttpCode(400, "Invalid group object");
            }

            if (!isset($data['name'])
                || !isset($data['name']['first_name'])
                || !isset($data['name']['last_name'])
            ) {
                Application::inst()->exitWithHttpCode(400, "Invalid name object");
            }

            // UPDATE NAME
            if (!strlen($data['name']['first_name']))
                throw new ApiException('no_first_name');
            if (!strlen($data['name']['last_name']))
                throw new ApiException('no_last_name');
                
            DB::inst()->query("UPDATE users SET
                    first_name = '" . DB::inst()->quote($data['name']['first_name']) . "',
                    last_name = '" . DB::inst()->quote($data['name']['last_name']) . "'
                WHERE id = {$current_user->id}");

            // UPDATE NOTIFICATION SETTINGS
            DB::inst()->query("UPDATE users SET
                    notify_suggestion_received = " . ((int)((boolean) $data['suggestion']['received'])) . ",
                    notify_suggestion_accepted = " . ((int)((boolean) $data['suggestion']['accepted'])) . ",
                    notify_suggestion_left_alone = " . ((int)((boolean) $data['suggestion']['left_alone'])) . ",
                    notify_suggestion_deleted = " . ((int)((boolean) $data['suggestion']['deleted'])) . ",
                    notify_group_memberships = " . ((int)((boolean) $data['group']['memberships'])) . "
                WHERE id = {$current_user->id}");

            // UPDATE PASSWORD
            // New password given
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
            // No new password but the old given
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

    function login()
    {
        Logger::debug(__METHOD__ . " GET /user/login called");

        $data = Application::inst()->getPostData();

        $passhash = Application::inst()->hash($data["password"]);


        $user_id = DB::inst()->getOne("SELECT id FROM users WHERE
            email_address = '" . DB::inst()->quote($data["email"]) . "' AND
            passhash = '$passhash'");

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
            EventLog::inst()->add('user', $this->id);
            echo json_encode(array('response' => "ok" ));
        
        }
        else {
            echo json_encode(array('response' => "fail" ));
        }
    }
}