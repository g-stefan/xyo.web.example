<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

defined("XYO_WEB") or die("Forbidden");

class TableUser extends \XYO\Web\DataSource\Table
{
    public $id;
    public $counter_id;
    public $name;

    public static function descriptor($info)
    {
        $info->name = "user";
        $info->primaryKey = "id";
        $info->fields = array(
            "id" => array("bigint", "DEFAULT", "unsigned", "autoIncrement"),
            "counter_id" => array("bigint", 0, "unsigned"),
            "name" => array("varchar", null, 255)
        );
    }

}
