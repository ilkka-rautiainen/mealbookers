<?php
require_once '../../app/app.php';
require_once '../../app/lib/flight/Flight.php';
require_once 'App.php';
require_once 'Restaurants.php';
require_once 'User.php';

Flight::start();

function getPostData() {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        sendHttpError(401, "Invalid JSON sent");
        return;
    }
    return $data;
}

function sendHttpError($number, $text = false) {
    if ($text === false) {
        if ($number == 404)
            $text = "Not Found";
        else if ($number == 400)
            $text = "Bad Request";
        else if ($number == 403)
            $text = "Forbidden";
        else if ($number == 500)
            $text = "Internal Server Error";
        else if ($number == 501)
            $text = "Not Implemented";
        else
            return sendHttpError(501, "Error for HTTP error $number not implemented");
    }
    $sapi_type = php_sapi_name();
    if (substr($sapi_type, 0, 3) == 'cgi')
        header("Status: $number $text");
    else
        header("HTTP/1.1 $number $text");
    print "<h1>$number $text</h1>";

    if (DB::inst()->isTransactionActive()) {
        DB::inst()->rollbackTransaction();
    }
    
    die;
}