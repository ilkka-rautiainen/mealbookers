<?php
/**
 * Main include file
 */
require __DIR__ . '/config.php';
require_once __DIR__ . '/lib/phpQuery.php';
function __autoload($name)
{
    if (file_exists(__DIR__ . "/$name.php"))
        require_once __DIR__ . "/$name.php";
    else if (file_exists(__DIR__ . "/import/$name.php"))
        require_once __DIR__ . "/import/$name.php";
    else if (file_exists(__DIR__ . "/models/$name.php"))
        require_once __DIR__ . "/models/$name.php";
	else if (file_exists(__DIR__ . "/exceptions/$name.php"))
		require_once __DIR__ . "/exceptions/$name.php";
}
Logger::info(__METHOD__ . " ## Start execution");
DB::inst();