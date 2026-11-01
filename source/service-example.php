<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// ---
if (!defined("XYO_WEB")) {
    // This file is inside the web root: run the service only from the
    // command line, never for an HTTP request to service-example.php.
    if (PHP_SAPI !== "cli") {
        http_response_code(403);
        die("Forbidden");
    }
    // web.php and the SQLite path in config.01.php are relative to the web
    // root, the folder of this file: run from there, whatever the current
    // folder is.
    chdir(__DIR__);
    require_once("./_site/xyo/web/web.php");
    defined("XYO_WEB") or die("Forbidden");
    \XYO\Web\Main\serviceRun(__FILE__);
    // An error (an exception gives the 501 page) sets the response code:
    // report it to cron / the task scheduler with the exit code.
    $code = http_response_code();
    exit((is_int($code) && ($code >= 400)) ? 1 : 0);
}
// ---

defined("XYO_WEB_SERVICE") or die("Forbidden");

use \DataSource\QueryUser;
use \DataSource\TableCounter;
use \DataSource\TableUser;

class ServiceExample extends \XYO\Web\Module
{

    public function process($options = null)
    {

        $tableCounter = $this->getDataSource(TableCounter::class);
        $tableCounter->createStorage();

        $tableUser = $this->getDataSource(TableUser::class);
        $tableUser->createStorage();

        // ---

        // Test if a row exists with count(), not load(): load() keeps its
        // SQLite read cursor open, and a write while a cursor is open fails
        // with "database is locked" when another process writes too.
        // insert() (not save(), which would update) creates the row with this
        // id; insertOnce() also handles another run creating it first.
        $tableUser->clear();
        $tableUser->id = 1;
        if ($tableUser->count() == 0) {
            $tableUser->counter_id = 1;
            $tableUser->name = "Hello";
            $tableUser->insertOnce();
        }

        $tableCounter->clear();
        $tableCounter->id = 1;
        $created = false;
        if ($tableCounter->count() == 0) {
            $tableCounter->count = 1;
            $created = $tableCounter->insertOnce();
        }
        if (!$created) {
            $tableCounter->clear();
            $tableCounter->id = 1;
            $tableCounter->atomicIncrement("count");
        }

        $queryUser = $this->getDataSource(QueryUser::class);
        for ($queryUser->load(); $queryUser->loadIsValid(); $queryUser->loadNext()) {
            echo "Id: " . $queryUser->id . "\r\n";
            echo "CId: " . $queryUser->counter_id . "\r\n";
            echo "Name: " . $queryUser->name . "\r\n";
            echo "Count: " . $queryUser->count . "\r\n";
        }

    }


}

return ServiceExample::class;
