<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace DataSource;

defined("XYO_WEB") or die("Forbidden");

// insert() for a row with a chosen id that another request or process may
// create at the same time (several browser tabs on the first visit, two runs
// of the service). The second insert() of the same id fails on the primary
// key; insertOnce() returns false instead when the row exists after all.
trait InsertOnce
{

    public function insertOnce()
    {
        try {
            $this->insert();
            return true;
        } catch (\XYO\Web\DataSource\DataSourceException $e) {
            $id = $this->id;
            $this->clear();
            $this->id = $id;
            if ($this->count() == 0) {
                throw $e;
            }
            return false;
        }
    }

}
