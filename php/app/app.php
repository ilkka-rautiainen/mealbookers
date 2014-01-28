<?php
require __DIR__ . '/config.php';
function __autoload($name)
{
	if (file_exists(__DIR__ . "/$name.php"))
		require_once __DIR__ . "/$name.php";
}
DB::inst()->init();