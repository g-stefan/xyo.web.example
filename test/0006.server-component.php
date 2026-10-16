<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The server component counts full page form POSTs (CSRF protected).

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0006");
$client = new Client(site_server_start($site));

$html = $client->get("example");
$token = $client->token();
check_eq("server counter starts at 0", 0, test_counters($html)[0] ?? null);

t_section("form POST");
$html = $client->post("example", ["_token" => $token]);
check_eq("status 200", 200, $client->status);
check_contains("full page rendered", "<html", $html);
check_eq("server counter 1", 1, test_counters($html)[0] ?? null);
$html = $client->post("example", ["_token" => $token]);
check_eq("server counter 2", 2, test_counters($html)[0] ?? null);
check_eq("other counters untouched", [0, 0, 0], array_slice(test_counters($html), 1));

t_section("CSRF");
$client->post("example", []);
check_eq("POST without _token is rejected", 401, $client->status);
$other = new Client($client->baseURL);
$other->post("example", ["_token" => $token]);
check_eq("token from another session is rejected", 401, $other->status);

t_section("separate sessions");
$html = $other->get("example");
check_eq("new session starts at 0", 0, test_counters($html)[0] ?? null);

t_done();
