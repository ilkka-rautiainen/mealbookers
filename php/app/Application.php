<?php

class Application
{
    private static $instance = null;
    private $isAuthenticated = false;
    
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

    public function initAuthentication()
    {
        // 1: hakee login-cookiesta id:n
        // 2: haetaan käyttäjä id:n avulla kannasta
        // 3: tsekataan passhashin oikeellisuus
        // 4: luodaan $current_user
        // 5: isAuthenticated = true
    }

    public function checkAuthentication()
    {
        // 1: tsekkaa että isAuthenticated == true
        // 2: jos ei -> feilaa
        /*if ($faile_ehto_täyttyy) {
            $this->exitWithHttpCode(403)
        }*/
    }

    public function exitWithHttpCode($number, $text = false)
    {
        Logger::note(__METHOD__ . " exiting with http $number: $text");

        if ($text === false) {
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
        print "<h1>$number $text</h1>";

        if (DB::inst()->isTransactionActive()) {
            DB::inst()->rollbackTransaction();
        }
        
        die;
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

    public function isStrongPassword($password, User $user)
    {
        if (mb_strlen($password) < 5)
            return false;
        else if (mb_stripos($password, $user->first_name) || mb_stripos($password, $user->last_name))
            return false;
        else
            return true;
    }

    public function getUniqueHash()
    {
        return md5(microtime(true) . mt_rand() . "gwoipasoidfugoiauvas92762439)(/%\")(/%¤#¤)/#¤&\")(¤%");
    }
}