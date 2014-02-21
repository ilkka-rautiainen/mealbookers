<?php
require_once '../../app/app.php';
require_once '../../app/lib/flight/Flight.php';
require_once 'App.php';
require_once 'Restaurants.php';
require_once 'Users.php';

Flight::start();

function getPostData() {
    $data = json_decode(file_get_contents('php://input'));
    if ($data === null) {
        sendHttpError(401, "Invalid JSON sent");
        return;
    }
    return $data;
}

function sendHttpError($number, $text) {
    $sapi_type = php_sapi_name();
    if (substr($sapi_type, 0, 3) == 'cgi')
        header("Status: $number $text");
    else
        header("HTTP/1.1 $number $text");
    print "<h1>$number $text</h1>";
    die;
}