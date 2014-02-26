<?php

Flight::route('GET /app/log/@passphrase(/@rows)', array('AppAPI', 'getLog'));
Flight::route('GET /app/language(/@lang)', array('AppAPI', 'getLanguage'));

class AppAPI
{
    /**
     * @url GET log/{passphrase}
     * @url GET log/{passphrase}/{rows}
     */
    function getLog($passphrase, $rows = 1000)
    {
        global $config;
        Logger::debug(__METHOD__ . " GET /app/log called");
        $rows = (int)$rows;

        if ($passphrase != "mealilogi")
            sendHttpError(404);
        

        $filename = "../../app/" . $config['log']['file'];

        print implode("<br />", (array_reverse(array_slice(array_reverse(file($filename)), 0, $rows))));
    }

	/**
     * @url GET language/{lang}
	 */
	function getLanguage($lang = 'en')
	{
        global $language;
        Logger::debug(__METHOD__ . " GET /app/language/$lang called");

        if (isset($language[$lang]))
            print json_encode($language[$lang]);
        else
            print json_encode($language['en']);
	}
}