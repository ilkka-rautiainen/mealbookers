<?php

class Application
{
    private static $instance = null;

    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {

    }

    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Application();

        return self::$instance;
    }

    /**
     * This is passed to frontend with current user
     */
    public function getFrontendConfiguration()
    {
        return array(
            'limits' => array(
                'suggestion_cancelable_time' => Conf::inst()->get('limits.suggestion_cancelable_time'),
                'suggestion_create_in_past_time' => Conf::inst()->get('limits.suggestion_create_in_past_time'),
            ),
        );
    }

    /**
     * Initializes the application for running.
     *  - Loads current user
     *  - Loads admin user
     */
    public function initAuthentication()
    {
        $user = false;

        if (isset($_COOKIE['id']) && isset($_COOKIE['check'])) {
            $user_id = (int)$_COOKIE['id'];
            $passhash = DB::inst()->getOne("SELECT passhash FROM users WHERE id = $user_id
                AND email_verified = 1");

            if (!is_null($passhash)) {
                $passhash = Application::inst()->hash($passhash);

                // Valid authentication
                if ($passhash == $_COOKIE['check']) {
                    $user = new User();
                    $user->fetch($user_id);
                }
            }
        }

        if (!$user) {
            $GLOBALS['current_user'] = new User();
            $GLOBALS['current_user']->role = 'guest';
            if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], array('fi', 'en')))
                $GLOBALS['current_user']->language = $_COOKIE['language'];
            else
                $GLOBALS['current_user']->language = Conf::inst()->get('defaultLanguage');
        }
        else {
            $GLOBALS['current_user'] = $user;
        }

        $GLOBALS['admin'] = new Admin();
    }

    /**
     * Checks if there's a valid authenticated session with the given role.
     * @param  string $role 'normal|admin'
     */
    public function checkAuthentication($requiredRole = 'normal')
    {
        global $current_user;
        Logger::debug(__METHOD__ . " required: $requiredRole, user has: {$current_user->role}");

        if ($current_user->role == 'normal' && $requiredRole == 'admin') {
            $this->exitWithHttpCode(403);
        }
        else if ($current_user->role == 'guest' && ($requiredRole == 'admin' || $requiredRole == 'normal')) {
            $this->exitWithHttpCode(403);
        }
    }

    public function exitWithHttpCode($number, $text = null, $level = null, $skip_general_code_error = false)
    {
        Logger::note(__METHOD__ . " exiting with http $number: $text");

        if (is_null($text)) {
            if ($number == 404)
                $text = "Not Found";
            else if ($number == 400)
                $text = "Bad Request";
            else if ($number == 403)
                $text = "Forbidden";
            else if ($number == 500)
                $text = "Internal Server Error";
            else if ($number == 501)
                $text = "Not Implemented";
            else
                return $this->exitWithHttpCode(501, "Error for HTTP error $number not implemented");
        }
        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) == 'cgi')
            header("Status: $number $text");
        else
            header("HTTP/1.1 $number $text");
        header("Fail-reason: $text");

        if ($level) {
            if (!in_array($level, array('danger', 'warning', 'info', 'success')))
                throw new Exception("Invalid http error level: $level");

            header("Fail-level: $level");
        }

        if ($skip_general_code_error)
            header("Skip-general-code-error: true");

        print "<h1>$number $text</h1>";

        if (DB::inst()->isTransactionActive()) {
            DB::inst()->rollbackTransaction();
        }

        die;
    }

    public function logError($type, $message, $file, $line, $stack_trace, $info = null)
    {
        $trace = array();
        foreach ($stack_trace as $trace_row) {
            $trace[] = array(
                'file' => $trace_row['file'],
                'line' => $trace_row['line'],
                'function' => $trace_row['function'],
                'class' => $trace_row['class'],
            );
        }
        DB::inst()->query("INSERT INTO exceptions (
                datetime,
                type,
                message,
                file,
                line,
                stack_trace,
                request,
                info
            ) VALUES (
                '" . date("Y-m-d H:i:s") . "',
                '" . DB::inst()->quote($type) . "',
                '" . DB::inst()->quote($message) . "',
                '" . DB::inst()->quote($file) . "',
                '" . $line . "',
                '" . DB::inst()->quote(json_encode($trace)) . "',
                '" . DB::inst()->quote(json_encode($_SERVER)) . "',
                " . ((is_null($info)) ? "NULL" : "'" . DB::inst()->quote(json_encode($info)) . "'") . "
            )");
    }

    public function getPostData()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null) {
            Logger::error(__METHOD__ . " " . json_last_error_msg());
            Application::inst()->exitWithHttpCode(400, "Invalid JSON sent");
            return;
        }
        return $data;
    }

    public function hash($s)
    {
        return sha1("gw89h#%HPHG392h23t)#(¤T" . $s);
    }

    public function isStrongPassword($password)
    {
        if (mb_strlen($password) < 5)
            return false;
        else
            return true;
    }

    public function getUniqueHash()
    {
        return sha1(microtime(true) . mt_rand() . "gwoipasoidfugoiauvas92762439)(/%\")(/%¤#¤)/#¤&\")(¤%");
    }

    public function getWeekdayNumber($timestamp = null)
    {
        $timestamp = (is_null($timestamp) ? time() : $timestamp);
        return ((int)date("N", $timestamp)) - 1;
    }

    public function getDateForDay($which)
    {
        if ($which == 'today') {
            return date("Y-m-d");
        }
        else if ($which == 'this_week_sunday') {
            return date("Y-m-d", strtotime("next sunday", strtotime("yesterday")));
        }
        else if ($which == 'this_week_monday') {
            return date("Y-m-d", strtotime("last monday", strtotime("tomorrow")));
        }
        else {
            throw new Exception("Unimplemented for $which");
        }
    }

    public function insertToken($id)
    {
        do {
            $token = $this->getUniqueHash();
        } while (DB::inst()->getOne("SELECT COUNT(token) FROM tokens WHERE token = '$token' LIMIT 1") > 0);

        DB::inst()->query("INSERT INTO tokens (token, id) VALUES ('$token', $id)");
        return $token;
    }

    public function getTokenId($token, $delete = true)
    {
        $id = DB::inst()->getOne("SELECT id FROM tokens WHERE token = '" . DB::inst()->quote($token) . "'");

        if (is_null($id))
            throw new NotFoundException("No such token found");

        if ($delete)
            $this->deleteToken($token);

        return $id;
    }

    public function deleteToken($token)
    {
        DB::inst()->query("DELETE FROM tokens WHERE token = '" . DB::inst()->quote($token) . "'");
    }

    public function generateInvitationCode()
    {
        do {
            $code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
        } while (DB::inst()->getOne("SELECT COUNT(id) FROM invitations WHERE code = '$code'") > 0);
        return $code;
    }

    public function getEmailValidationRegex()
    {
        return "/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/";
    }

    public function getHttpHost()
    {
        return Conf::inst()->get('server.http_host');
    }

    /**
     * @param $date string 'Y-m-d'
     * Formats:
     * %W: today, tomorrow, on wednesday, on thursday...
     * %w: on monday, on tuesday, on wednesday, on thursday...
     * %D: date formatted as the $date_format parameter states
     */
    public function formatWeekDay($date, User $viewer, $format = "%W %D", $date_format = "j.n.Y")
    {
        $timestamp = strtotime($date);
        $today = date("Y-m-d", strtotime("today 00:00:00"));
        $tomorrow = date("Y-m-d", strtotime("tomorrow 00:00:00"));

        $formatted_string = $format;
        if ($date == $today) {
            $today_i18n = mb_strtolower(Lang::inst()->get('today', $viewer));
            $formatted_string = mb_str_replace("%W", $today_i18n, $format);
        }
        else if ($date == $tomorrow) {
            $tomorrow_i18n = mb_strtolower(Lang::inst()->get('tomorrow', $viewer));
            $formatted_string = mb_str_replace("%W", $tomorrow_i18n, $format);
        }
        else {
            $weekday = $this->getWeekdayNumber($timestamp) + 1;
            $weekday_i18n = mb_strtolower(Lang::inst()->get('weekday_' + $weekday));
            $formatted_string = mb_str_replace("%w", $weekday_i18n, $format);
        }
        $formatted_string = str_replace("%D", date($date_format, $timestamp), $formatted_string);

        return $formatted_string;
    }
}