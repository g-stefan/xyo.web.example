<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");
require_once ("./_site/web.php");

class TableCounter extends \XYO\Web\DataSource\Table
{
    public $id;
    public $count;

    public function __construct($connection = null)
    {
        parent::__construct($connection);
    }

    public static function descriptor(&$info)
    {        
        $info->name = "counter";
        $info->primaryKey = "id";
        $info->fields = array(
            "id" => array("bigint", "DEFAULT", "unsigned", "autoIncrement"),
            "count" => array("int", 0, "unsigned")
        );
    }

}
