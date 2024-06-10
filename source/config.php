<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

$config = new \stdClass();
$config->dataSource = new \stdClass();

/*$config->dataSource->connections = array(
	"db" => array(
		"type" => "mysql",
		"user" => "root",
		"password" => "",
		"server" => "localhost",
		"port" => "3306",
		"database" => "xyo.web",
		"prefix" => ""
	)
);*/

$config->dataSource->connections = array(
	"db" => array(
		"type" => "sqlite",		
		"database" => "./site/repository/example.sqlite",
		"prefix" => ""
	)
);

return $config;
