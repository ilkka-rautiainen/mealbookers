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
spl_autoload_register('classLoader');
Logger::info(__METHOD__ . " ## Start execution");
Application::inst()->initAuthentication();
DB::inst();