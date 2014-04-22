<?php
require_once '../app.php';
header("Content-type: text/html; charset=utf-8");
set_time_limit(3600);

if (isset($_GET['reset']) && !empty($_GET['reset'])) {
    if (!Conf::inst()->get('developerMode')) {
        Application::inst()->exitWithHttpCode(403, 'reset_only_in_developer_mode');
    }
    else {
        $reset = true;
    }
}
else {
    $reset = false;
}

$importers = array(
    new AlvariImport(),
    new KvarkkiImport(),
    new TUASImport(),
    new Puu2Import(),
    new SilinteriImport(),
    new LaureaImport(),
    new TaffaImport(),
    new DipoliImport(),
);

foreach ($importers as $importer) {
    try {
        $importer->init();
        if ($reset)
            $importer->reset();
        $importer->run(((isset($_GET['opening_hours']) && !empty($_GET['opening_hours'])) ? true : false));
    }
    catch (ImportException $e) {
        $errorMessage = __FILE__ . ":" . __LINE__ . " Error in " . get_class($importer) . ": "
            . $e->getMessage() . ", from:" . $e->getFile() . ":" . $e->getLine();
        Logger::error($errorMessage);
        print $errorMessage . "<br />";
    }
}
print "import executed";