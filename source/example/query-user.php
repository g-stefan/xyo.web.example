<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");
require_once ("./site/web.php");
require_once ("./example/table-counter.php");
require_once ("./example/table-user.php");

class QueryUser extends \XYO\Web\DataSource\Query
{

    public $id;
    public $counter_id;
    public $name;
    public $count;

    public function __construct($connection = null)
    {
        parent::__construct($connection);
    }

    public static function descriptor(&$info)
    {        
        $info->base = array("user", TableUser::class, "*");
        $info->outer = array(
            "counter" => array(
                TableCounter::class,
                array(
                    "count" => "count"
                ),
                array(
                    "id",
                    array(
                        "user",
                        "counter_id"
                    )
                )
            )
        );
    }

}
