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
    require_once("./_site/xyo/web/web.php");
    defined("XYO_WEB") or die("Forbidden");
    \XYO\Web\Main\serviceRun(__FILE__);
    exit(0);
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

        // A failed load() empties the fields, so set the id again and use
        // insert() to create the row with this id (save() would update).
        $tableUser->clear();
        $tableUser->id = 1;
        if (!$tableUser->load(0, 1)) {
            $tableUser->clear();
            $tableUser->id = 1;
            $tableUser->counter_id = 1;
            $tableUser->name = "Hello";
            $tableUser->insert();
        }

        $tableCounter->clear();
        $tableCounter->id = 1;
        if (!$tableCounter->load(0, 1)) {
            $tableCounter->clear();
            $tableCounter->id = 1;
            $tableCounter->count = 1;
            $tableCounter->insert();
        } else {
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
