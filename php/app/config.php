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
    'mail' => array(
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_username' => 'mealbookers@gmail.com',
        'smtp_password' => 'booker123',
    ),
	'developerMode' => true,
);

if (file_exists(__DIR__ . "/config_override.php"))
	require_once __DIR__ . '/config_override.php';