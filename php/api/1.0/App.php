<?php

Flight::route('GET /app/log/@passphrase(/@rows)', array('AppAPI', 'getLog'));
Flight::route('GET /app/language(/@lang)', array('AppAPI', 'getLanguage'));

class AppAPI
{
    function getLog($passphrase, $rows = 1000)
    {
        Logger::info(__METHOD__ . " GET /app/log called");
        Application::inst()->checkAuthentication();
        $rows = (int)$rows;

        if ($passphrase != "mealilogi")
            Application::inst()->exitWithHttpCode(404);
        

        $filename = "../../app/" . Conf::inst()->get('log.file');

        print implode("<br />", (array_reverse(array_slice(array_reverse(file($filename)), 0, $rows))));
    }

	function getLanguage($lang = 'en')
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
}