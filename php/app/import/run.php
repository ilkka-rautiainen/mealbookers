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
    new KasperImport(),
    new KoneImport(),
    new SahkoImport(),
    new TtaloImport(),
);

foreach ($importers as $importer) {
    $importer->init();
    if ($reset)
        $importer->reset();
    $importer->run(((isset($_GET['opening_hours']) && !empty($_GET['opening_hours'])) ? true : false));
}

if (!isset($_GET['cron']))
    print "import executed";