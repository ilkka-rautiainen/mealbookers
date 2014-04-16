<?php
/**
 * Main include file
 */
function classLoader($name)
{
    if (file_exists(__DIR__ . "/$name.php"))
        require_once __DIR__ . "/$name.php";
    else if (file_exists(__DIR__ . "/models/$name.php"))
        require_once __DIR__ . "/models/$name.php";
    else if (file_exists(__DIR__ . "/import/$name.php"))
        require_once __DIR__ . "/import/$name.php";
	else if (file_exists(__DIR__ . "/exceptions/$name.php"))
		require_once __DIR__ . "/exceptions/$name.php";
}
require_once 'lib/mb_str_replace.php';
spl_autoload_register('classLoader');
mb_internal_encoding("UTF-8");
Logger::info(__METHOD__ . " ## Start execution");
Application::inst()->initAuthentication();
DB::inst();