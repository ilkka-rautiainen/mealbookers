<?php

Flight::route('GET /app/status', array('AppAPI', 'getStatus'));
Flight::route('GET /app/log/@passphrase(/@rows)', array('AppAPI', 'getLog'));
Flight::route('GET /app/language(/@lang)', array('AppAPI', 'getLanguage'));
Flight::route('POST /app/contact', array('AppAPI', 'contact'));

class AppAPI
{
    public static function getStatus($passphrase, $rows = 1000)
    {
        Logger::info(__METHOD__ . " GET /app/status called");
        print json_encode(array(
            'status' => 'ok',
        ));
    }

    public static function getLog($passphrase, $rows = 1000)
    {
        Logger::info(__METHOD__ . " GET /app/log called");
        Application::inst()->checkAuthentication('admin');
        $rows = (int)$rows;

        if ($passphrase != "mealilogi")
            Application::inst()->exitWithHttpCode(404);

        $filename = "../../log/" . Conf::inst()->get('log.file');

        print "<pre>" . implode("", (array_reverse(array_slice(array_reverse(file($filename)), 0, $rows)))) . "</pre>";
    }

    public static function getLanguage($lang = 'en')
    {
        require __DIR__ . '/../../app/language/include.php';
        global $language;
        $lang = substr($lang, 0, 2);
        Logger::info(__METHOD__ . " GET /app/language/$lang called");

        if (isset($language[$lang]))
            $lang = $language[$lang];
        else
            $lang = $language['en'];

        unset($lang['backend_only']);

        print json_encode($lang);
    }

	public static function contact()
	{
        Logger::debug(__METHOD__ . " POST /app/contact called");

        $data = Application::inst()->getPostData();

        if (!isset($data['email'])) {
            throw new HttpException(400, 'email_address_missing');
        }

        if (!preg_match(Application::inst()->getEmailValidationRegex(), mb_strtoupper($data['email'])))
            throw new HttpException(409, 'invalid_email');

        if (!isset($data['title']) || !isset($data['text']) || !$data['title'] || !$data['text'])
            throw new HttpException(409, 'missing_data');

        $result = DB::inst()->query("SELECT * FROM users WHERE `role` = 'admin'");

        while ($row = DB::inst()->fetchAssoc($result)) {
            $user = new User();
            $user->populateFromRow($row);
            if (!$user->sendContactEmail($data['title'], $data['text'], $data['email']))
                throw new HttpException(500, 'email_failed');
        }

        print json_encode(array(
            'status' => 'ok',
        ));
	}
}