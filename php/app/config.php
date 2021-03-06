<?php
/**
 * Configuration file
 */
$GLOBALS['config'] = array(
    'admin' => array(
        'email_address' => 'mealbookers@gmail.com',
    ),
    'server' => array(
        'http_host' => 'localhost',
        'relative_path' => '/',
    ),
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
		'level' => 'debug',
		'file' => 'mealbookers.log',
	),
    /**
     * initialsMaxLettersFromLastName - maximum number of letters taken from last name
     * Example names: John Doe, John Davis
     * Value 2: John Do., John Da.
     * Value 1: John 1, John 2
     */
    'initialsMaxLettersFromLastName' => 3,
    'import' => array(
        'useragent' => 'Mealbookers data crawler',
    ),
    'defaultLanguage' => 'fi',
    'mealDefaultLanguage' => 'fi',
    'restaurantsDefaultLanguage' => 'fi',
    'mail' => array(
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_auth' => true,
        'smtp_username' => 'mealbookers@gmail.com',
        'smtp_password' => 'booker123',
        'from_address' => 'mealbookers@gmail.com',
    ),
	'developerMode' => false,
    'limits' => array(
        'suggestion_cancelable_time' => 30 * 60, // Time (in past) for suggestion to be cancelable/acceptable
        'suggestion_create_in_past_time' => 5 * 60, // How long in past suggestion can be created
        'backend_threshold' => 60, // How much backend permits over the limit
        'force_ui_refresh' => 60 * 15, // If ui hasn't updated within this time, refresh is forced
        'notification_validity_time' => 60 * 60 * 10, // Remove older notifications than this
    ),
    'gcm' => array(
        'api_key' => '',
    ),
);

if (file_exists(__DIR__ . "/../config_override.php"))
	require_once __DIR__ . '/../config_override.php';