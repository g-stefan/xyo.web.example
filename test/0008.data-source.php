<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The data source rules described in docs/data-source.md, checked on the
// example tables and query: set fields filter load(), insert() / save(),
// atomicIncrement(), operators, the LEFT OUTER JOIN of QueryUser.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0008");
chdir($site);
require_once($site . "/_site/xyo/web/web.php");

use \DataSource\TableCounter;
use \DataSource\TableUser;
use \DataSource\QueryUser;

$connection = new \XYO\Web\DataSource\Connection();
check("sqlite connection configured", $connection->set("db", [
    "type" => "sqlite",
    "database" => $site . "/_repository/example.sqlite",
    "prefix" => ""
]));

t_section("autoloading");
check("DataSource\\TableCounter", class_exists(TableCounter::class));
check("DataSource\\QueryUser", class_exists(QueryUser::class));
check("_Library\\XYOWebExampleCSS", class_exists(\_Library\XYOWebExampleCSS::class));
check("XYO\\LucideIcons\\LucideIcons", class_exists(\XYO\LucideIcons\LucideIcons::class));

$counter = $connection->getDataSource(TableCounter::class);
$user = $connection->getDataSource(TableUser::class);
check("createStorage counter", $counter->createStorage() === true);
check("createStorage user", $user->createStorage() === true);
check("createStorage is idempotent", $counter->createStorage() === true);

t_section("insert");
$counter->clear();
$counter->count = 5;
check("insert with an empty key", $counter->insert() === true);
check_eq("auto increment id set after insert", 1, intval($counter->id));
$counter->clear();
$counter->id = 7;
$counter->count = 70;
$counter->insert();
check_eq("insert keeps a chosen id", 70, intval($connection->get()->queryValue("SELECT count FROM counter WHERE id = 7")));

t_section("save");
$counter->clear();
$counter->id = 9;
$counter->count = 90;
$counter->save();
$counter->clear();
check_eq("save() with a key that does not exist inserts nothing", 2, intval($counter->count()));
$counter->clear();
$counter->id = 7;
$counter->load(0, 1);
$counter->count = 71;
$counter->save();
check_eq("save() of a loaded row updates it", 71, intval($connection->get()->queryValue("SELECT count FROM counter WHERE id = 7")));

t_section("set fields filter load()");
$counter->clear();
$counter->id = 1;
check("load row 1", $counter->load(0, 1));
$counter->id = 7;
check("without clear(), the old count of row 1 filters the load of row 7", !$counter->load(0, 1));
check("a failed load() empties the fields", $counter->isEmpty("id") && $counter->isEmpty("count"));
$counter->clear();
$counter->id = 7;
check("with clear(), row 7 loads", $counter->load(0, 1));
check_eq("row 7 count", 71, intval($counter->count));
$counter->clear();
$counter->id = [1, 7];
check_eq("an array value matches any of its values", 2, intval($counter->count()));

t_section("iterate");
$ids = [];
$counter->clear();
$counter->setOrder("id", \XYO\Web\DataSource\Order::DESCENDENT);
for ($counter->load(); $counter->loadIsValid(); $counter->loadNext()) {
    $ids[] = intval($counter->id);
}
check_eq("load / loadIsValid / loadNext in order", [7, 1], $ids);

t_section("atomicIncrement");
$counter->clear();
$counter->id = 1;
check("atomicIncrement", $counter->atomicIncrement("count"));
$counter->clear();
$counter->id = 1;
$counter->atomicAdd("count", 10);
$counter->clear();
$counter->id = 1;
$counter->load(0, 1);
check_eq("5 + 1 + 10", 16, intval($counter->count));

t_section("operators");
for ($i = 0; $i < 5; $i++) {
    $counter->clear();
    $counter->count = $i;
    $counter->insert();
}
// rows: 1 => 16, 7 => 71, 8..12 => 0..4
$counter->clear();
$counter->setOperator("count", ">=", 70);
$counter->pushOperator("or");
$counter->setOperator("id", "between", 8, 9);
check_eq("count >= 70 OR id BETWEEN 8 AND 9", 3, intval($counter->count()));
$counter->clear();
$counter->setOperator("count", ">=", 70);
$counter->setOperator("id", "between", 8, 9);
check_eq("a second setOperator() without pushOperator() replaces the first", 2, intval($counter->count()));

t_section("update and delete");
$counter->clear();
$counter->setOperator("count", "<", 3);
$counter->update(["count" => 100]);
$counter->clear();
$counter->count = 100;
check_eq("update() changed the rows matching the condition", 3, intval($counter->count()));
$counter->clear();
$counter->count = 100;
$counter->delete();
$counter->clear();
check_eq("delete() by the set fields", 4, intval($counter->count()));

t_section("QueryUser LEFT OUTER JOIN");
$user->clear();
$user->id = 1;
$user->counter_id = 1;
$user->name = "Hello";
$user->insert();
$user->clear();
$user->id = 2;
$user->counter_id = 999;
$user->name = "No counter";
$user->insert();
$query = $connection->getDataSource(QueryUser::class);
$rows = [];
$query->clear();
for ($query->load(); $query->loadIsValid(); $query->loadNext()) {
    $rows[intval($query->id)] = [$query->name, $query->count];
}
check_eq("two users", [1, 2], array_keys($rows));
check_eq("user 1 joined with counter 1", ["Hello", 16], [$rows[1][0], intval($rows[1][1])]);
check_eq("user without counter has count null", ["No counter", null], $rows[2]);
$query->clear();
$query->name = "Hello";
check_eq("query fields filter too", 1, intval($query->count()));

t_done();
