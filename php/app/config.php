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
    'import' => array(
        'useragent' => 'Mealbookers data crawler',
    ),
    'mealDefaultLang' => 'fi',
    'restaurantsDefaultLang' => 'fi',
    'mail' => array(
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_username' => 'mealbookers@gmail.com',
        'smtp_password' => 'booker123',
        'from_address' => 'mealbookers@gmail.com',
    ),
	'developerMode' => true,
    'limits' => array(
        'suggestion_cancelable_time' => 30 * 60, // Time (in past) for suggestion to be cancelable/acceptable
        'suggestion_create_in_past_time' => 5 * 60, // How long in past suggestion can be created
        'backend_threshold' => 60, // How much backend permits over the limit
    ),
);

if (file_exists(__DIR__ . "/config_override.php"))
	require_once __DIR__ . '/config_override.php';