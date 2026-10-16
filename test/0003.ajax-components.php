<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The AJAX component and the AJAX component nested inside it keep separate
// session counters: a request for one of them must not count for the other.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0003");
$client = new Client(site_server_start($site));

$client->get("example");
$token = $client->token();
check("CSRF token rendered", !is_null($token));

function post_component($client, $token, $component)
{
    return test_counters($client->post("example", [
        "_component" => $component,
        "_ajax" => "1",
        "_token" => $token,
        "_tab" => "test"
    ]));
}

t_section("POST the AJAX component");
$counters = post_component($client, $token, "ajax");
check_eq("status 200", 200, $client->status);
check_eq("ajax = 1, nested rendered inside = 0", [1, 0], $counters);
check_not_contains("only the component is returned, not the page", "<html", $client->body);

t_section("POST the nested AJAX component");
$counters = post_component($client, $token, "ajax/ajax-nested");
check_eq("status 200", 200, $client->status);
check_eq("nested = 1", [1], $counters);
$counters = post_component($client, $token, "ajax/ajax-nested");
check_eq("nested = 2", [2], $counters);

t_section("POST the AJAX component again");
$counters = post_component($client, $token, "ajax");
check_eq("ajax = 2, nested still 2", [2, 2], $counters);

t_section("page view does not count");
$html = $client->get("example");
check_eq("server 0, ajax 2, nested 2, db 0", [0, 2, 2, 0], test_counters($html));

t_section("CSRF");
$client->post("example", ["_component" => "ajax", "_ajax" => "1", "_tab" => "test"]);
check_eq("POST without _token is rejected", 401, $client->status);
$client->post("example", ["_component" => "ajax", "_ajax" => "1", "_token" => "bad", "_tab" => "test"]);
check_eq("POST with a wrong _token is rejected", 401, $client->status);
$counters = post_component($client, $token, "ajax");
check_eq("rejected requests did not count", [3, 2], $counters);

t_done();
