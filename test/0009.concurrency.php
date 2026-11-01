<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Requests and service runs at the same time on one SQLite database, starting
// from an empty one. A write made while a load() read cursor is open, or the
// second insert() of the same id, used to answer 501 ("database is locked",
// UNIQUE constraint failed) and lose counter increments.

require_once(__DIR__ . "/_lib/site.php");

define("SERVICE_RUNS", 8);
define("TABS", 6);
define("POSTS", 5);

function router_log($site)
{
    $text = "";
    foreach (glob($site . "/_log/*.router.log") as $file) {
        $text .= file_get_contents($file);
    }
    return $text;
}

t_section(SERVICE_RUNS . " service runs at the same time");
$site = site_build("0009.service");
$commands = [];
for ($i = 0; $i < SERVICE_RUNS; $i++) {
    $commands[] = [PHP_BINARY, "service-example.php"];
}
$results = test_run_parallel($commands, $site, dirname($site) . "/process");
$ok = 0;
foreach ($results as list($code, $output)) {
    if (($code === 0) && (strpos($output, "Name: Hello") !== false)) {
        $ok++;
    }
}
check_eq("every run exits 0 and lists the user", SERVICE_RUNS, $ok);
$db = site_db($site);
check_eq("one user row", 1, intval($db->query("SELECT COUNT(*) FROM user")->fetchColumn()));
check_eq("one counter row", 1, intval($db->query("SELECT COUNT(*) FROM counter")->fetchColumn()));
check_eq("no increment lost", SERVICE_RUNS, intval($db->query("SELECT count FROM counter WHERE id = 1")->fetchColumn()));
$db = null;
check_not_contains("no error logged by the router", "message", router_log($site));

t_section(TABS . " browser tabs polling the DB AJAX component at the same time");
$site = site_build("0009.http");
$commands = [];
for ($i = 0; $i < TABS; $i++) {
    // one server per tab: the PHP built-in server answers one request at a time
    $commands[] = [PHP_BINARY, __DIR__ . "/_lib/worker-ajax-db.php", site_server_start($site), (string) POSTS];
}
$results = test_run_parallel($commands, $site, dirname($site) . "/process");
$ok = 0;
foreach ($results as $index => list($code, $output)) {
    if ($code === 0) {
        $ok++;
        continue;
    }
    echo "  tab " . $index . ":\n    " . str_replace("\n", "\n    ", trim($output)) . "\n";
}
check_eq("every request answers 200 with the counter", TABS, $ok);
$rows = site_db($site)->query("SELECT id, count FROM counter ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
check_eq("rows 1..10", range(1, 10), array_map("intval", array_keys($rows)));
check_eq("no increment lost", array_fill(0, 10, TABS * POSTS), array_map("intval", array_values($rows)));
check_not_contains("no error logged by the router", "message", router_log($site));

t_done();
