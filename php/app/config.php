<?php

$GLOBALS['config'] = array(
	'db' => array(
		'user' => 'adminlvsuknv',
		'pass' => 'pZpHurr8-UWK',
		'host' => 'localhost',
		'port' => '5432',
		'dbname' => 'app',
	),
	'log' => array(
		'levels' => array(
	        'emergency',
	        'alert',
	        'critical',
	        'error',
	        'warn',
	        'note',
	        'info',
	        'debug',
	        'trace',
		),
		'level' => 'trace',
		'file' => 'mealbookers.log',
	),
);

if (file_exists(__DIR__ . "/config_override.php"))
	require_once __DIR__ . '/config_override.php';