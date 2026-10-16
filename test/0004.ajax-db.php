<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The DB AJAX component keeps exactly the counter rows 1..10, increments all
// of them on each AJAX POST and shows the value of row 1.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0004");
$client = new Client(site_server_start($site));

function counter_rows($site)
{
    return site_db($site)->query("SELECT id, count FROM counter ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
}

function post_db($client, $token)
{
    return test_counters($client->post("example", [
        "_component" => "ajax-db",
        "_ajax" => "1",
        "_token" => $token,
        "_tab" => "test"
    ]));
}

t_section("first page view seeds the rows");
$client->get("example");
$token = $client->token();
$rows = counter_rows($site);
check_eq("rows 1..10", range(1, 10), array_map("intval", array_keys($rows)));
check_eq("all counters 0", array_fill(0, 10, 0), array_map("intval", array_values($rows)));

t_section("AJAX POST increments every row");
check_eq("shows 1", [1], post_db($client, $token));
check_eq("shows 2", [2], post_db($client, $token));
$rows = counter_rows($site);
check_eq("still rows 1..10", range(1, 10), array_map("intval", array_keys($rows)));
check_eq("all counters 2", array_fill(0, 10, 2), array_map("intval", array_values($rows)));

t_section("rows that differ do not create new rows");
// service-example.php increments only row 1
list($code, $output) = site_service($site);
check_eq("service exit code 0", 0, $code);
$rows = counter_rows($site);
check_eq("row 1 = 3 after the service", 3, intval($rows[1]));
for ($i = 0; $i < 3; $i++) {
    $html = $client->get("example");
}
$rows = counter_rows($site);
check_eq("page views keep rows 1..10", range(1, 10), array_map("intval", array_keys($rows)));
check_eq("page shows the value of row 1", 3, test_counters($html)[3] ?? null);
check_eq("next AJAX POST shows row 1 + 1", [4], post_db($client, $token));

t_section("a missing row is seeded again with its id");
site_db($site)->exec("DELETE FROM counter WHERE id = 5");
$client->get("example");
$rows = counter_rows($site);
check_eq("rows 1..10 again", range(1, 10), array_map("intval", array_keys($rows)));
check_eq("row 5 seeded with 0", 0, intval($rows[5] ?? -1));

t_done();
