<?php

Flight::route('POST /user/notificationReceived', array('UserAPI', 'markNotificationReceived'));
Flight::route('POST /user/registerAndroidGCM', array('UserAPI', 'registerAndroidGCM'));
Flight::route('GET /user(/@userId)', array('UserAPI', 'getUser'));
Flight::route('POST /user/login/forgot/new/@token', array('UserAPI', 'createNewPassword'));
Flight::route('POST /user/login/forgot', array('UserAPI', 'sendForgotPasswordLink'));
Flight::route('GET /user/login/forgot/@token', array('UserAPI', 'getUserForForgotPassword'));
Flight::route('POST /user/restaurant-order', array('UserAPI', 'updateRestaurantOrder'));
Flight::route('POST /user/login', array('UserAPI', 'login'));
Flight::route('POST /user/logout', array('UserAPI', 'logout'));
Flight::route('POST /user/register', array('UserAPI', 'registerUser'));
Flight::route('POST /user/email/verify/@token', array('UserAPI', 'verifyEmail'));
Flight::route('POST /user(/@userId)/language', array('UserAPI', 'updateUserLanguage'));
Flight::route('POST /user(/@userId)', array('UserAPI', 'updateUser'));
Flight::route('DELETE /user(/@userId)', array('UserAPI', 'deleteUser'));
Flight::route('GET /users', array('UserAPI', 'searchUsers'));

class UserAPI
{
    function searchUsers()
    {
        Logger::debug(__METHOD__ . " GET /users called");
        Application::inst()->checkAuthentication('admin');

        $user = (isset($_GET['user'])) ? $_GET['user'] : '';
        $group = (isset($_GET['group'])) ? $_GET['group'] : '';

        if (!$user && !$group) {
            throw new HttpException(409, 'no_search_term', 'info');
        }

        $user = str_replace("*", "%", DB::inst()->quoteLike($user));
        $group = str_replace("*", "%", DB::inst()->quoteLike($group));

        if ($user && !$group) {
            $result = DB::inst()->query("SELECT
                    users.*,
                    GROUP_CONCAT(groups.name SEPARATOR ', ') groups
                FROM users
                LEFT JOIN group_memberships ON users.id = group_memberships.user_id
                LEFT JOIN groups ON groups.id = group_memberships.group_id
                WHERE users.email_address LIKE '%$user%' OR
                CONCAT_WS(' ', users.first_name, users.last_name) LIKE '%$user%' OR
                users.first_name LIKE '%$user%' OR
                users.last_name LIKE '%$user%'
                GROUP BY users.id
                LIMIT 30
            ");
        }
        else if ($group && !$user) {
            $result = DB::inst()->query("SELECT
                    users.*,
                    GROUP_CONCAT(groups.name ORDER BY group_memberships.joined ASC SEPARATOR ', ') groups
                FROM users
                INNER JOIN group_memberships ON group_memberships.user_id = users.id
                INNER JOIN groups ON groups.id = group_memberships.group_id
                WHERE users.id IN (SELECT DISTINCT gm.user_id FROM groups
                INNER JOIN group_memberships gm ON gm.group_id = groups.id
                WHERE groups.name LIKE '%$group%')
                GROUP BY users.id
                LIMIT 30
            ");
        }
        else {
            $result = DB::inst()->query("SELECT
                    users.*,
                    GROUP_CONCAT(groups.name ORDER BY group_memberships.joined ASC SEPARATOR ', ') groups
                FROM users
                INNER JOIN group_memberships ON group_memberships.user_id = users.id
                INNER JOIN groups ON groups.id = group_memberships.group_id
                WHERE users.id IN (SELECT DISTINCT gm.user_id FROM groups
                INNER JOIN group_memberships gm ON gm.group_id = groups.id
                WHERE (users.email_address LIKE '%$user%' OR
                CONCAT_WS(' ', users.first_name, users.last_name) LIKE '%$user%' OR
                users.first_name LIKE '%$user%' OR
                users.last_name LIKE '%$user%') AND
                groups.name LIKE '%$group%')
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

    function getUser($userId = null, $omit_authentication = false)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " GET /user/$userId called");

            if (!$omit_authentication) {
                Application::inst()->checkAuthentication('admin');
            }

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                throw new HttpException(404, 'user_not_found');
            }
        }
        else {
            Logger::debug(__METHOD__ . " GET /user called");
            $user = &$current_user;
        }

        if ($user->role == 'guest') {
            return print json_encode(array(
                'status' => 'ok',
                'user' => array(
                    'role' => 'guest',
                ),
            ));
        }

        // Live view: up to date
        if (isset($_GET['after']) && !$user->hasUpdatesAfter((int)$_GET['after'])) {
            return print json_encode(array(
                'status' => 'up_to_date',
                'timestamp' => time(),
            ));
        }

        $result = $user->getAsArray();
        $user_array = $result;

        $groups = $user->getGroupsAsArray();
        $result['groups'] = $groups;

        /**
         * Note: friends is amount of all friends in all groups (not distinctive)
         */
        $friends = 0;
        foreach ($groups as $group) {
            $friends += count($group['members']);
        }
        $result['friends'] = $friends;

        $result['me'] = $user_array;
        $result['email_address'] = $user->email_address;
        $result['first_name'] = $user->first_name;
        $result['last_name'] = $user->last_name;
        $result['notification_settings'] = $user->getNotificationSettingsAsArray();
        $result['config'] = Application::inst()->getFrontendConfiguration();
        $result['language'] = $user->language;
        $result['role'] = $user->role;
        $result['timestamp'] = time();
        $result['has_android_app'] = (DB::inst()->getOne("SELECT android_gcm_regid FROM users WHERE id = {$user->id}")) ? true : false;
        $result['suggestion_method'] = $user->suggestion_method;

        print json_encode(array(
            'status' => 'ok',
            'user' => $result,
        ));
    }

    function markNotificationReceived()
    {
        global $current_user;

        Logger::debug(__METHOD__ . " POST /user/markNotificationReceived called");
        Application::inst()->checkAuthentication();

        $data = Application::inst()->getPostData();

        if (!isset($data['id'])) {
            throw new HttpException(400, 'id missing');
        }
        $id = (int) $data['id'];

        DB::inst()->query("UPDATE notifications SET received = 1 WHERE id = $id AND user_id = {$current_user->id}");

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function registerAndroidGCM()
    {
        global $current_user;

        Logger::debug(__METHOD__ . " POST /user/registerAndroidGCM called");
        Application::inst()->checkAuthentication();

        $data = Application::inst()->getPostData();

        if (!isset($data['regid'])) {
            throw new HttpException(400, 'regid missing');
        }

        DB::inst()->query("UPDATE users SET android_gcm_regid = '" . DB::inst()->quote($data['regid']) . "', suggestion_method = 'androidApp' WHERE id = {$current_user->id}");

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function deleteUser($userId = null)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " DELETE /user/$userId called");
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
            Logger::debug(__METHOD__ . " DELETE /user called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        $user->delete();

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function updateUser($userId = null)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " POST /user/$userId called");
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
            Logger::debug(__METHOD__ . " POST /user called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        DB::inst()->startTransaction();

        $data = Application::inst()->getPostData();

        if (!isset($data['password'])
            || !isset($data['password']['old'])
            || !isset($data['password']['new'])
            || !isset($data['password']['repeat'])
        ) {
            throw new HttpException(400, 'invalid_password_object');
        }

        if (!isset($data['suggestion'])
            || !isset($data['suggestion']['received'])
            || !isset($data['suggestion']['accepted'])
            || !isset($data['suggestion']['left_alone'])
            || !isset($data['suggestion']['deleted'])
        ) {
            throw new HttpException(400, 'invalid_suggestion_object');
        }

        if (!isset($data['group'])
            || !isset($data['group']['memberships'])
        ) {
            throw new HttpException(400, 'invalid_group_object');
        }

        if (!isset($data['name'])
            || !isset($data['name']['first_name'])
            || !isset($data['name']['last_name'])
        ) {
            throw new HttpException(400, 'invalid_name_object');
        }

        if (!isset($data['role'])) {
            throw new HttpException(400, 'invalid_role');
        }

        // UPDATE NAME
        if (!strlen($data['name']['first_name']))
            throw new HttpException(409, 'no_first_name');
        if (!strlen($data['name']['last_name']))
            throw new HttpException(409, 'no_last_name');

        DB::inst()->query("UPDATE users SET
                first_name = '" . DB::inst()->quote($data['name']['first_name']) . "',
                last_name = '" . DB::inst()->quote($data['name']['last_name']) . "'
            WHERE id = {$user->id}");
        $user->fetch($user->id);

        // UPDATE NOTIFICATION SETTINGS
        DB::inst()->query("UPDATE users SET
                notify_suggestion_received = " . ((int)((boolean) $data['suggestion']['received'])) . ",
                notify_suggestion_accepted = " . ((int)((boolean) $data['suggestion']['accepted'])) . ",
                notify_suggestion_left_alone = " . ((int)((boolean) $data['suggestion']['left_alone'])) . ",
                notify_suggestion_deleted = " . ((int)((boolean) $data['suggestion']['deleted'])) . ",
                notify_group_memberships = " . ((int)((boolean) $data['group']['memberships'])) . "
            WHERE id = {$user->id}");

        if (in_array($data['suggestion_method'], array('email', 'androidApp'))) {
            DB::inst()->query("UPDATE users SET suggestion_method = '" . $data['suggestion_method'] . "' WHERE id = {$user->id}");
        }


        // UPDATE PASSWORD
        // New password given
        if ($data['password']['new'] || $data['password']['repeat']) {
            if ($current_user->role != 'admin') {
                if (!$data['password']['old']) {
                    throw new HttpException(409, 'no_old_password');
                }
                $old_hash = DB::inst()->getOne("SELECT passhash FROM users WHERE id = {$user->id}");
                if ($old_hash != Application::inst()->hash($data['password']['old'])) {
                    throw new HttpException(409, 'wrong_password');
                }
            }

            if ($data['password']['new'] != $data['password']['repeat']) {
                throw new HttpException(409, 'passwords_dont_match');
            }
            else if (!Application::inst()->isStrongPassword($data['password']['new'])) {
                throw new HttpException(409, 'weak_password');
            }
            else {
                if ($user->id != $current_user->id
                    && !$user->notifyPasswordChanged($data['password']['new']))
                    throw new HttpException(500, 'notify_failed');

                DB::inst()->query("UPDATE users
                    SET passhash = '" . Application::inst()->hash($data['password']['new']) . "'
                    WHERE id = {$user->id}");

                if ($user->id == $current_user->id) {
                    if (isset($_COOKIE['remember']) && $_COOKIE['remember'])
                        $expiry_time = time() + 86400*365*5;
                    else
                        $expiry_time = 0;

                    setcookie(
                        "check",
                        Application::inst()->hash(Application::inst()->hash($data['password']['new'])),
                        $expiry_time,
                        Conf::inst()->get('server.relative_path')
                    );
                }
            }
        }
        // No new password but the old given
        else if ($data['password']['old']) {
            throw new HttpException(409, 'no_new_password');
        }

        // UPDATE ROLE
        if ($current_user->role == 'admin' && in_array($data['role'], array(
                'normal',
                'admin',
            )) && $user->id != $current_user->id && $data['role'] != $user->role)
        {
            DB::inst()->query("UPDATE users SET role = '" . $data['role'] . "' WHERE id = {$user->id}");
        }

        DB::inst()->commitTransaction();
        print json_encode(array(
            'status' => 'ok'
        ));
    }

    function updateUserLanguage($userId = null)
    {
        global $current_user;

        if ($userId) {
            Logger::debug(__METHOD__ . " POST /user/$userId/language called");
            Application::inst()->checkAuthentication('admin');

            try {
                $user = new User();
                $user->fetch($userId);
            }
            catch (NotFoundException $e) {
                throw new HttpException(404, 'user_not_found', 'danger');
            }
        }
        else {
            Logger::debug(__METHOD__ . " POST /user/language called");
            Application::inst()->checkAuthentication();

            $user = &$current_user;
        }

        $data = Application::inst()->getPostData();

        if (!isset($data['language']) || !in_array($data['language'], array('fi', 'en')))
            throw new HttpException(400, 'invalid_language');

        DB::inst()->query("UPDATE users SET language = '" . $data['language'] . "' WHERE id = {$user->id}");

        print json_encode(array(
            'status' => 'ok'
        ));
    }

    function updateRestaurantOrder()
    {
        global $current_user;

        Logger::debug(__METHOD__ . " POST /user/restaurant-order called");
        Application::inst()->checkAuthentication();

        $data = Application::inst()->getPostData();

        if (!is_array($data) || !count($data)) {
            Application::inst()->exitWithHttpCode(400, 'invalid_data');
        }

        DB::inst()->startTransaction();
        DB::inst()->query("DELETE FROM users_restaurants_order WHERE user_id = {$current_user->id}");

        $insert = array();
        $points = 0;
        for ($i = count($data) - 1; $i >= 0; $i--) {
            if (!isset($data[$i])) {
                Application::inst()->exitWithHttpCode(400, 'inconsistent_data');
            }
            $inserts[] = "({$current_user->id}, " . intval($data[$i]) . ", $points)";
            $points++;
        }

        DB::inst()->query("INSERT INTO users_restaurants_order (user_id, restaurant_id, order_points)
            VALUES " . implode(", ", $inserts));
        DB::inst()->commitTransaction();

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function login()
    {
        Logger::debug(__METHOD__ . " POST /user/login called");

        $data = Application::inst()->getPostData();

        if (!isset($data['password'])
            || !isset($data['email'])
            || !isset($data['remember']))
            Application::inst()->exitWithHttpCode(400);

        $passhash = Application::inst()->hash($data["password"]);


        if (!$user_id = DB::inst()->getOne("SELECT id FROM users WHERE
            email_address = '" . DB::inst()->quote($data["email"], false) . "' AND
            passhash = '$passhash'"))
        {
            throw new HttpException(409, 'wrong_username_or_password');
        }

        if (DB::inst()->getOne("SELECT email_verified FROM users WHERE id = $user_id") == 0)
            throw new HttpException(409, 'email_not_verified');

        if ($data["remember"])
            $expiry_time = time() + 86400*365*5;
        else
            $expiry_time = 0;

        setcookie("id", $user_id, $expiry_time, Conf::inst()->get('server.relative_path'));
        setcookie("check", Application::inst()->hash($passhash), $expiry_time, Conf::inst()->get('server.relative_path'));
        setcookie("remember", ($data['remember']) ? "1" : "0", $expiry_time, Conf::inst()->get('server.relative_path'));

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function logout()
    {
        Logger::debug(__METHOD__ . " POST /user/logout called");

        setcookie("id", "", 0, Conf::inst()->get('server.relative_path'));
        setcookie("check", "", 0, Conf::inst()->get('server.relative_path'));
        setcookie("remember", "", 0, Conf::inst()->get('server.relative_path'));

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function sendForgotPasswordLink()
    {
        Logger::debug(__METHOD__ . " POST /user/login/forgot called");

        $data = Application::inst()->getPostData();

        if (!isset($data['email'])) {
            throw new HttpException(400, 'email_address_missing');
        }

        if (!preg_match(Application::inst()->getEmailValidationRegex(), mb_strtoupper($data['email'])))
            throw new HttpException(409, 'invalid_email');

        if (!$user_id = DB::inst()->getOne("SELECT id FROM users WHERE
            email_address = '" . DB::inst()->quote($data["email"], false) . "'"))
        {
            throw new HttpException(404, 'email_not_found');
        }

        $user = new User();
        $user->fetch($user_id);

        if (!$user->sendNewPasswordEmail()) {
            throw new HttpException(500, 'link_sending_failed');
        }

        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function getUserForForgotPassword($token)
    {
        Logger::debug(__METHOD__ . " GET /user/login/forgot/$token called");

        if (!$token) {
            throw new HttpException(400, 'token_missing');
        }

        try {
            $user_id = Application::inst()->getTokenId($token, false);
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'token_not_found');
        }

        UserApi::getUser($user_id, true);
    }

    function createNewPassword($token)
    {
        Logger::debug(__METHOD__ . " POST /user/login/forgot/new/$token called");

        if (!$token) {
            throw new HttpException(400, 'token_missing');
        }

        // Get data
        $data = Application::inst()->getPostData();

        if (!isset($data['new'])
            || !isset($data['repeat'])) {
            throw new HttpException(400, 'passwords_missing');
        }

        // Retrieve user id from token
        try {
            $user_id = Application::inst()->getTokenId($token, false);
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'token_not_found');
        }

        // Retrieve user
        $user = new User();
        try {
            $user->fetch($user_id);
        }
        catch (NotFoundException $e) {
            throw new HttpException(404, 'user_not_found');
        }

        // Check passwords
        if ($data['new'] != $data['repeat']) {
            throw new HttpException(409, 'passwords_dont_match');
        }
        else if (!Application::inst()->isStrongPassword($data['new'])) {
            throw new HttpException(409, 'weak_password');
        }

        // Update the password
        DB::inst()->query("UPDATE users SET passhash = '" . Application::inst()->hash($data['new']). "'
            WHERE id = $user_id");
        Application::inst()->deleteToken($token);
        print json_encode(array(
            'status' => 'ok',
        ));
    }

    function registerUser()
    {
        Logger::debug(__METHOD__ . " GET /user/register called");

        $data = Application::inst()->getPostData();

        if (DB::inst()->getOne("SELECT COUNT(id) FROM users WHERE email_address = '" . $data['email'] . "'"))
            throw new HttpException(409, 'email_exists');

        if (!isset($data['first_name'])
            || !isset($data['last_name'])
            || !isset($data['email'])
            || !isset($data['password'])
            || !isset($data['password_repeat'])
            || !isset($data['language'])
            || !isset($data['invitation_code'])
            || !isset($data['study_year'])
            || !isset($data['study_programme'])
            || !isset($data['study_programme_other'])
        ) {
            throw new HttpException(400, 'data_invalid');
        }

        $email_address = $data['email'];
        if (!preg_match(Application::inst()->getEmailValidationRegex(), mb_strtoupper($email_address)))
            throw new HttpException(409, 'invalid_email');

        if (!strlen($data['first_name']))
            throw new HttpException(409, 'no_first_name');
        if (!strlen($data['last_name']))
            throw new HttpException(409, 'no_last_name');

        // if ((!strlen($data['study_programme']) || !in_array($data['study_programme'], Lang::inst()->get('register_study_programmes', null, $data['language']))) && !$data['study_programme_other'])
        //     throw new HttpException(409, 'give_study_programme');
        // else if ($data['study_programme_other'])
        //     $data['study_programme'] = '';

        // if (strlen($data['study_programme']) && !in_array($data['study_year'], array(1, 2, 3, 4, 5, 'n')))
        //     throw new HttpException(409, 'give_study_year');
        // else if ($data['study_programme_other'])
        //     $data['study_year'] = '';

        if ($data['password'] != $data['password_repeat'])
            throw new HttpException(409, 'passwords_dont_match');
        $passhash = Application::inst()->hash($data['password']);

        if (!Application::inst()->isStrongPassword($data['password']))
            throw new HttpException(409, 'weak_password');

        if (!in_array($data['language'], array('fi', 'en')))
            $data['language'] = Config::inst()->get('defaultLanguage');

        DB::inst()->startTransaction();
        $result = DB::inst()->query("INSERT INTO users (
                email_address,
                passhash,
                first_name,
                last_name,
                language,
                joined,
                email_verified,
                notify_suggestion_received,
                notify_suggestion_accepted,
                notify_suggestion_left_alone,
                notify_suggestion_deleted,
                notify_group_memberships,
                study_programme,
                study_year
            ) VALUES (
                '" . DB::inst()->quote($data['email']) . "',
                '$passhash',
                '" . DB::inst()->quote($data['first_name']) . "',
                '" . DB::inst()->quote($data['last_name']) . "',
                '" . $data['language'] . "',
                '" . time() . "',
                0
,                1,
                1,
                1,
                1,
                1,
                '" . DB::inst()->quote($data['study_programme']) . "',
                " . self::getStartYearFromStudyYear($data['study_year']) . "
            )");

        $user = new User();
        $user->fetch(DB::inst()->getInsertId());

        // Invitation
        if ($data['invitation_code'] && $group_id = DB::inst()->getOne("SELECT group_id FROM invitations
            WHERE code = '" . DB::inst()->quote($data['invitation_code']) . "'"))
        {
            $group = new Group();
            $group->fetch($group_id);
            $user->joinGroup($group);
            DB::inst()->query("DELETE FROM invitations
                WHERE code = '" . DB::inst()->quote($data['invitation_code']) . "'");
        }

        EventLog::inst()->add('user', $user->id);

        if (!$user->sendEmailVerification())
            throw new HttpException(500, 'verification_email_sending_failed', 'danger');

        DB::inst()->commitTransaction();
        print json_encode(array(
            'status' => 'ok',
        ));
    }

    /**
     * Helper
     */
    function getStartYearFromStudyYear($study_year)
    {
        $array = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            'n' => 6
        );
        if (!in_array($study_year, array_keys($array)))
            return "NULL";

        $month = (int)date("n");
        $isSpring = $month >= 1 && $month <= 7;
        return ((int)date("Y")) - $array[$study_year] - ($isSpring ? 1 : 0);
    }

    function verifyEmail($token)
    {
        Logger::debug(__METHOD__ . " POST /user/email/verify/$token called");

        try {
            $user_id = Application::inst()->getTokenId($token);
            DB::inst()->query("UPDATE users SET email_verified = 1 WHERE id = $user_id");
            $passhash = DB::inst()->getOne("SELECT passhash FROM users WHERE id = $user_id");

            // Add valid login
            setcookie("id", $user_id, 0, Conf::inst()->get('server.relative_path'));
            setcookie("check", Application::inst()->hash($passhash), 0, Conf::inst()->get('server.relative_path'));
            setcookie("remember", "0", 0, Conf::inst()->get('server.relative_path'));

            print json_encode(array(
                'status' => 'ok',
            ));
        }
        catch (NotFoundException $e) {
            print json_encode(array(
                'status' => 'not_found',
            ));
        }
    }
}