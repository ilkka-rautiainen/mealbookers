<?php
/**
 * This file runs sql updates
 */

require_once __DIR__ . '/app.php';
try {
    if (isset($_GET['reset']) && !empty($_GET['reset']))
        DB::inst()->resetDB();
    DB::inst()->runUpdates();
    print "updates ok";
}
catch (Exception $e) {
    print "Error: " . $e->getMessage();
}