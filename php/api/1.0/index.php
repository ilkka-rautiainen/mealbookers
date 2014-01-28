<?php

require_once '../../restler/vendor/restler.php';
use Luracast\Restler\Restler;

$r = new Restler();
$r->setSupportedFormats('JsonFormat');
$r->addAPIClass('Restaurants');
$r->handle();
