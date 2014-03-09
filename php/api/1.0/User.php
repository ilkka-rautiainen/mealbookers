<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user/login', array('UserAPI', 'login'));

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

        $result = DB::inst()->query("SELECT id FROM users WHERE email_address='".$data["email"]."' AND passhash='".mysql_real_escape_string("$passhash")."'");
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

            echo json_encode("ok");
        
        }
        else {
            echo json_encode("Wrong email or password!");
        }
    }
}