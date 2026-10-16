<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// The AJAX form posts its fields and shows them back, HTML escaped.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0005");
$client = new Client(site_server_start($site));

$html = $client->get("example");
$token = $client->token();

t_section("form markup");
check_contains("form id", "id=\"ajax-form_form\"", $html);
check_contains("submit handler posts the form", "XYO.Web.Component.AJAX.postForm(\"ajax-form\", \"ajax-form_form\"", $html);

t_section("POST the form");
$html = $client->post("example", [
    "_component" => "ajax-form",
    "_ajax" => "1",
    "_token" => $token,
    "_tab" => "test",
    "first-name" => "  Ada ",
    "last-name" => "Lovelace",
    "email" => "ada@example.com",
    "country" => "Canada"
]);
check_eq("status 200", 200, $client->status);
check_contains("first name shown, trimmed", "First Name: Ada<br />", $html);
check_contains("last name shown", "Last Name: Lovelace<br />", $html);
check_contains("e-mail shown", "E-Mail: ada@example.com<br />", $html);
check_contains("country shown", "Country: Canada<br />", $html);

t_section("values are HTML escaped");
$html = $client->post("example", [
    "_component" => "ajax-form",
    "_ajax" => "1",
    "_token" => $token,
    "_tab" => "test",
    "first-name" => "<script>alert(1)</script>",
    "last-name" => "\"quoted\" & <b>bold</b>",
    "email" => "",
    "country" => "United States"
]);
check_not_contains("no injected script tag", "<script>alert(1)</script>", $html);
check_contains("script tag escaped", "&lt;script&gt;alert(1)&lt;/script&gt;", $html);
check_contains("quotes and ampersand escaped", "&quot;quoted&quot; &amp; &lt;b&gt;bold&lt;/b&gt;", $html);

t_section("missing fields");
$html = $client->post("example", [
    "_component" => "ajax-form",
    "_ajax" => "1",
    "_token" => $token,
    "_tab" => "test"
]);
check_eq("status 200", 200, $client->status);
check_contains("empty first name", "First Name: <br />", $html);

t_done();
