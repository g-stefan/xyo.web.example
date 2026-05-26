<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

defined("XYO_WEB") or die("Forbidden");

class TableCounter extends \XYO\Web\DataSource\Table
{
    public $id;
    public $count;

    public static function descriptor($info)
    {
        $info->name = "counter";
        $info->primaryKey = "id";
        $info->fields = array(
            "id" => array("bigint", "DEFAULT", "unsigned", "autoIncrement"),
            "count" => array("int", 0, "unsigned")
        );
    }

}
