<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");
require_once ("./site/web.php");

class TableUser extends \XYO\Web\DataSource\Table
{
    public $id;
    public $counter_id;
    public $name;

    public function __construct($connection = null)
    {
        parent::__construct($connection);
    }

    public static function descriptor(&$info)
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
