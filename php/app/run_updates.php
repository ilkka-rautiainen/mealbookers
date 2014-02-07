<?php
/**
 * This file runs sql updates
 */

require_once __DIR__ . '/app.php';
try {
    DB::inst()->runUpdates();
    print "updates ok";
}
catch (Exception $e) {
    print "Error: " . $e->getMessage();
}