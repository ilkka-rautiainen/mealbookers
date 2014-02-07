<?php
/**
 * Configuration file
 */
$GLOBALS['config'] = array(
	'db' => array(
		'user' => 'root',
		'pass' => '',
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
	'mealDefaultLang' => 'fi',
);

if (file_exists(__DIR__ . "/config_override.php"))
	require_once __DIR__ . '/config_override.php';