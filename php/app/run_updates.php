<?php
/**
 * This file runs sql updates
 */
set_time_limit(3600);
require_once __DIR__ . '/app.php';
try {
    if (isset($_GET['reset']) && !empty($_GET['reset'])) {
        if (!Conf::inst()->get('developerMode')) {
            Application::inst()->exitWithHttpCode(403, 'reset_only_in_developer_mode');
        }
        else {
            DB::inst()->resetDB();
        }
    }
    DB::inst()->runUpdates();
    print "updates ok";
}
catch (Exception $e) {
    print "Error: " . $e->getMessage();
}