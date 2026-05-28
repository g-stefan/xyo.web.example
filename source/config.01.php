<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

defined("XYO_WEB") or die("Forbidden");

require_once("./_site/xyo/web/web.php");

return array(
	"dataSource" => array(
		"connection" => array(
			"db" => array(
				"type" => "sqlite",
				"database" => "./_repository/example.sqlite",
				"prefix" => ""
			)
		)
	)
);
