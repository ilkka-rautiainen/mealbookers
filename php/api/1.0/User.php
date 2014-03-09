<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user/login', array('UserAPI', 'login'));
Flight::route('POST /user/registerUser', array('UserAPI', 'registerUser'));

Flight::register('db', 'Database', array('localhost', 'database', 'username', 'password'));

class UserAPI
{
    /**
     * @todo  separate members with identical initials
     * @todo  implement with real user id from current user + implement auth
     */
    function getUser()
    {
        Logger::debug(__METHOD__ . " GET /user called");

        $current_user = new User();
        $current_user->fetch(1);
        $user = $current_user->getAsArray();

        $user['groups'] = $current_user->getGroupsAsArray();
        $user['config'] = Application::inst()->getFrontendConfiguration();
        $user['language'] = $current_user->language;

        print json_encode($user);
    }

    function login()
    {
        Logger::debug(__METHOD__ . " GET /user/login called");

        $data = getPostData();

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

        $data = getPostData();

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