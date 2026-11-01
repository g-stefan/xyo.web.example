<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Worker of 0009.concurrency.php, one browser tab polling the DB AJAX
// component:
//   php worker-ajax-db.php <base URL> <number of AJAX POSTs>
// Prints one line per request and exits with 1 if a request did not answer
// 200 with a counter.

require_once(__DIR__ . "/site.php");

$baseURL = $argv[1];
$posts = intval($argv[2]);
$failed = 0;

$client = new Client($baseURL);
$client->get("example");
echo "GET " . $client->status . "\n";
if ($client->status !== 200) {
    $failed++;
}
$token = $client->token();

for ($i = 0; $i < $posts; $i++) {
    $counters = test_counters($client->post("example", [
        "_component" => "ajax-db",
        "_ajax" => "1",
        "_token" => $token,
        "_tab" => "worker"
    ]));
    echo "POST " . $client->status . " " . implode(",", $counters) . "\n";
    if (($client->status !== 200) || (count($counters) !== 1)) {
        $failed++;
    }
}

exit($failed ? 1 : 0);
