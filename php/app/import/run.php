<?php
require_once '../app.php';
header("Content-type: text/html; charset=utf-8");
set_time_limit(3600);

$importers = array(
    // new AlvariImport(),
    new KvarkkiImport(),
    // new TUASImport(),
    // new Puu2Import(),
    // new SilinteriImport(),
    // new LaureaImport(),
);

try {
    foreach ($importers as $importer) {
        $importer->init();
        if (isset($_GET['reset']) && !empty($_GET['reset']))
            $importer->reset();
        $importer->run();
    }
    print "import ok";
}
catch (ImportException $e) {
    $errorMessage = __FILE__ . ":" . __LINE__ . " Error in import: "
        . $e->getMessage() . ", from:" . $e->getFile() . ":" . $e->getLine();
    Logger::error($errorMessage);
    print $errorMessage;
}