<?php

Flight::route('GET /user', array('UserAPI', 'getUser'));
Flight::route('POST /user/login', array('UserAPI', 'login'));

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

        $user['groups'] = $current_user->getGroups();
        $user['config'] = Application::inst()->getFrontendConfiguration();
        $user['language'] = $current_user->language;

        print json_encode($user);
    }

    function login()
    {
        Logger::debug(__METHOD__ . " GET /user/login called");

        $data = getPostData();

        // 1: haetaan tietokannasta emaililla + hashatyllä salasanalla käyttäjän id
        // 2: jos ok
        //   -> asetetaan cookiet (id + 2*hashattu salasana saltilla)
        //   -> ilmoitetaan fronttiin ok
        // 2: jos ei -> palautetaan sanoma fronttiin
        
        // setcookie("name", "value", time() + 86400*365*30);
    }
}