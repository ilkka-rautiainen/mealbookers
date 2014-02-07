<?php
/**
 * This file runs sql updates
 */

require_once __DIR__ . '/app.php';
DB::inst()->runUpdates();