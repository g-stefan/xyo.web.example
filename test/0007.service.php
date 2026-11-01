<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// service-example.php runs from the command line: it creates the storage,
// seeds user 1 / counter 1, increments counter 1 and lists the users joined
// with their counter.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0007");

t_section("first run on an empty database");
list($code, $output) = site_service($site);
check_eq("exit code 0", 0, $code);
check_contains("user id", "Id: 1", $output);
check_contains("user counter id", "CId: 1", $output);
check_contains("user name", "Name: Hello", $output);
check_contains("counter value", "Count: 1", $output);
check_not_contains("no warnings", "Warning", $output);
check_not_contains("no errors", "Error", $output);

t_section("second run increments the counter");
list($code, $output) = site_service($site);
check_eq("exit code 0", 0, $code);
check_contains("counter value", "Count: 2", $output);
$db = site_db($site);
check_eq("one user row", 1, intval($db->query("SELECT COUNT(*) FROM user")->fetchColumn()));
check_eq("one counter row", 1, intval($db->query("SELECT COUNT(*) FROM counter")->fetchColumn()));

t_section("rows are seeded with id 1 even when other rows exist");
$db->exec("DELETE FROM user");
$db->exec("DELETE FROM counter");
$db->exec("INSERT INTO counter (id, count) VALUES (7, 70)");
$db->exec("INSERT INTO user (id, counter_id, name) VALUES (9, 7, 'Other')");
$db = null;
list($code, $output) = site_service($site);
check_eq("exit code 0", 0, $code);
$db = site_db($site);
check_eq("user 1 exists", "Hello", $db->query("SELECT name FROM user WHERE id = 1")->fetchColumn());
check_eq("counter 1 exists", 1, intval($db->query("SELECT count FROM counter WHERE id = 1")->fetchColumn()));
check_contains("other user listed with its counter", "Name: Other", $output);
check_contains("other counter value", "Count: 70", $output);
$db = null;
list($code, $output) = site_service($site);
$db = site_db($site);
check_eq("still two counter rows", 2, intval($db->query("SELECT COUNT(*) FROM counter")->fetchColumn()));
check_eq("counter 1 incremented", 2, intval($db->query("SELECT count FROM counter WHERE id = 1")->fetchColumn()));
$db = null;

t_section("the service runs from any current folder");
$cwd = getcwd();
chdir(TEST_PROJECT_PATH);
$output = [];
$code = 0;
exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg($site . "/service-example.php") . " 2>&1", $output, $code);
chdir($cwd);
$output = implode("\n", $output);
check_eq("exit code 0", 0, $code);
check_contains("counter value", "Count: 3", $output);
check("no database created in the current folder", !file_exists(TEST_PROJECT_PATH . "_repository/example.sqlite"));

t_section("an error gives a non-zero exit code");
// a folder where the database file should be: the connection cannot open it
rename($site . "/_repository/example.sqlite", $site . "/_repository/example.sqlite.bak");
mkdir($site . "/_repository/example.sqlite");
list($code, $output) = site_service($site);
check_eq("exit code 1", 1, $code);
check_contains("the 501 page is printed", "501", $output);
rmdir($site . "/_repository/example.sqlite");
rename($site . "/_repository/example.sqlite.bak", $site . "/_repository/example.sqlite");
list($code, $output) = site_service($site);
check_eq("exit code 0 again", 0, $code);

t_section("the service is CLI only");
$client = new Client(site_server_start($site));
$client->get("service-example.php");
check_contains("service-example.php over HTTP does not run the service", "Forbidden", $client->body);
check_not_contains("no user listing over HTTP", "Name: Hello", $client->body);

t_done();
