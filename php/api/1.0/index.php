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
        Application::inst()->exitWithHttpCode(400, "Invalid JSON sent");
        return;
    }
    return $data;
}