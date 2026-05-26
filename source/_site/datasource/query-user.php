<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/datasource/table-counter.php");
require_once(XYO_WEB_PATH . "_site/datasource/table-user.php");

class QueryUser extends \XYO\Web\DataSource\Query
{

    public $id;
    public $counter_id;
    public $name;
    public $count;

    public static function descriptor($info)
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
