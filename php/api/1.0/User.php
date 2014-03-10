<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user', array('UserAPI', 'updateUser'));
Flight::route('POST /user/login', array('UserAPI', 'login'));
Flight::route('POST /user/registerUser', array('UserAPI', 'registerUser'));

Flight::register('db', 'Database', array('localhost', 'database', 'username', 'password'));

class UserAPI
{
    /**
     * @todo  implement with real user id from current user + implement auth
     */
    function getUser()
    {
        Logger::debug(__METHOD__ . " GET /user called");

        $current_user = new User();
        $current_user->fetch(1);
        $user = $current_user->getAsArray();

        $user['groups'] = $current_user->getGroupsAsArray();
        $user['email_address'] = $current_user->email_address;
        $user['config'] = Application::inst()->getFrontendConfiguration();
        $user['language'] = $current_user->language;

        print json_encode($user);
    }

    /**
     * @todo  implement with real user id from current user + implement auth
     */
    function updateUser()
    {
        Logger::debug(__METHOD__ . " POST /user called");

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
                    throw new UpdateAccountException('no_old_password');
                }
                else if ($data['password']['new'] != $data['password']['repeat']) {
                    throw new UpdateAccountException('passwords_dont_match');
                }
                $old_hash = DB::inst()->getOne("SELECT passhash FROM users WHERE id = {$current_user->id}");
                if ($old_hash != Application::inst()->hash($data['password']['old'])) {
                    throw new UpdateAccountException('wrong_password');
                }
                else if (!Application::inst()->isStrongPassword($data['password']['new'], $current_user)) {
                    throw new UpdateAccountException('weak_password');
                }
                else {
                    DB::inst()->query("UPDATE users
                        SET passhash = '" . Application::inst()->hash($data['password']['new']) . "'
                        WHERE id = {$current_user->id}");
                }
            }


            DB::inst()->commitTransaction();
            print json_encode(array(
                'status' => 'ok'
            ));
        }
        catch (UpdateAccountException $e) {
            print json_encode(array(
                'status' => $e->getMessage()
            ));
        }
    }

    function login()
    {
        Logger::debug(__METHOD__ . " GET /user/login called");

        $data = Application::inst()->getPostData();

        $passhash = sha1($data["password"]);

        $result = DB::inst()->query("SELECT id FROM users WHERE email_address='".$data["email"]."' AND passhash='".mysql_real_escape_string("$passhash")."' AND active=1");
        $row = DB::inst()->fetchAssoc($result);

        if ($data["remember"] === true) {
            $remember = time() + 3600*24*365;
        }
        else {
            $remember = 0;
        }

        if (count($row)!= 0){
            $passhash2 = sha1("4k89".$passhash."sa");

            setcookie("id", $row["id"], $remember, '/');

            setcookie("check", $passhash2, $remember, '/');

            echo json_encode(array('status' => "ok" ));
        
        }
        else {
            echo json_encode(array('status' => "fail" ));
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