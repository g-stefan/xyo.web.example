<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// ---
if (!defined("XYO_WEB")) {
    require_once("./_site/xyo/web/web.php");
    defined("XYO_WEB") or die("Forbidden");
    \XYO\Web\Main\serviceRun(__FILE__);
    exit(0);
}
// ---

defined("XYO_WEB_SERVICE") or die("Forbidden");
require_once(XYO_WEB_PATH . "_site/datasource/query-user.php");

class ServiceExample extends \XYO\Web\Module
{

    public function process($options = null)
    {

        $tableCounter = $this->getDataSource(TableCounter::class);
        $tableCounter->createStorage();

        $tableUser = $this->getDataSource(TableUser::class);
        $tableUser->createStorage();

        // ---

        $tableUser->clear();
        ;
        $tableUser->id = 1;
        if (!$tableUser->load(0, 1)) {
            $tableUser->clear();
            $tableUser->counter_id = 1;
            $tableUser->name = "Hello";
            $tableUser->save();
        }

        $tableCounter->clear();
        $tableCounter->id = 1;
        if (!$tableCounter->load(0, 1)) {
            $tableCounter->count = 1;
            $tableCounter->save();
        } else {
            $tableCounter->count = $tableCounter->count + 1;
            $tableCounter->save();
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
