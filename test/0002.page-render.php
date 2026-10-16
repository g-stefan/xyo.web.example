<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The example page renders every component, with the security headers, the
// CSRF token and the example stylesheet in place of the default one.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0002");
$client = new Client(site_server_start($site));

t_section("GET /example");
$html = $client->get("example");
check_eq("status 200", 200, $client->status);
check_contains("page title", "<title>Web</title>", $html);
check_contains("logo component", "xyo-web-logo", $html);
check_contains("server component", "Server component", $html);
check_contains("AJAX component", ">AJAX component<", $html);
check_contains("nested AJAX component", "Nested AJAX component", $html);
check_contains("DB AJAX component", "DB AJAX component", $html);
check_contains("AJAX form component", "Component Form AJAX", $html);
check_eq("counters start at zero (server, ajax, nested, db)", [0, 0, 0, 0], test_counters($html));
check("CSRF token rendered", !is_null($client->token()));
check("session cookie set", array_key_exists("PHPSESSID", $client->cookies));
check("CSRF cookie set", array_key_exists("_token", $client->cookies));
check_contains("Content-Security-Policy header with nonce", "'nonce-", (string) $client->header("Content-Security-Policy"));
check_contains("example stylesheet linked", "_library/xyo-web-example.css", $html);
check_not_contains("default xyo-web.css removed", "library/xyo-web.css", $html);
check_contains("lucide icons stylesheet linked", "lucide-icons.min.css", $html);
check_contains("overlay scrollbars script linked", "overlayscrollbars.browser.es6.min.js", $html);
check_contains("xyo.web client script linked", "_site/xyo/web/web.js", $html);
check_contains("AJAX component polls with a POST", "XYO.Web.Component.AJAX.post(\"ajax\"", $html);
check_contains("nested component polls with its own id", "XYO.Web.Component.AJAX.post(\"ajax/ajax-nested\"", $html);

t_section("database storage created");
$db = site_db($site);
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
check("table counter exists", in_array("counter", $tables));
check("table user exists", in_array("user", $tables));

t_section("other routes");
$html = $client->get("");
check_eq("GET / status 200", 200, $client->status);
check_contains("GET / renders the default xyo.web page", "This is the default welcome page.", $html);
$client->get("does-not-exist");
check_eq("GET /does-not-exist status 404", 404, $client->status);
$client->get("example/");
check_eq("GET /example/ redirects (trailing slash)", 301, $client->status);
$client->get("example/page.php");
check_contains("page.php called directly is forbidden", "Forbidden", $client->body);

t_done();
