<?php
require_once '../app.php';
header("Content-type: text/html; charset=utf-8");

try {
    $alvari = new Restaurant();
    $alvari->fetch(1);
    $alvariImport = new AlvariImport();
    $alvariImport->init($alvari);
    $alvariImport->run();
    print "import ok";
}
catch (ImportException $e) {
    Logger::error(__FILE__ . ":" . __LINE__ . " Error in import: " . $e->getMessage() . ", from:" . $e->getFile() . ":" . $e->getLine());
}