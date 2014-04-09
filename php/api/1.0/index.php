<?php
require_once '../../app/app.php';
require_once '../../app/lib/flight/Flight.php';
require_once 'App.php';
require_once 'Restaurants.php';
require_once 'Groups.php';
require_once 'User.php';
require_once 'Invitation.php';

try {
    Flight::start();
}
catch (HttpException $e) {
    if (DB::inst()->isTransactionActive()) {
        DB::inst()->rollbackTransaction();
    }

    Application::inst()->exitWithHttpCode(
        $e->getCode(),
        $e->getMessage(),
        $e->getLevel(),
        $e->getSkipGeneralCodeError()
    );
}