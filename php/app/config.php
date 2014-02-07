<?php
/**
 * Configuration file
 */
$GLOBALS['config'] = array(
	'db' => array(
		'user' => 'adminDDT3KhA',
		'pass' => 'AaLdeR_I9ENs',
		'host' => 'localhost',
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
    'useragent' => 'Mealbookers data crawler',
);

if (file_exists(__DIR__ . "/config_override.php"))
	require_once __DIR__ . '/config_override.php';