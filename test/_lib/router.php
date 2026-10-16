<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Router script for the PHP built-in web server ("php -S"). It does what the
// .htaccess file of xyo.web does under Apache:
//   - config.* files and the _repository / _log folders are denied,
//   - a request for an existing file is served as is,
//   - everything else is rewritten to index.php?__=<path> (query string kept).

$root = rtrim(str_replace("\\", "/", $_SERVER["DOCUMENT_ROOT"]), "/");
$path = rawurldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
$path = ltrim($path, "/");

$deny = false;
if (preg_match("/(^|\\/)config\\.[^\\/]*$/", $path)) {
    $deny = true;
}
if (preg_match("/^(_repository|_log)(\\/|$)/", $path)) {
    $deny = true;
}
if ($deny) {
    http_response_code(403);
    echo "Forbidden";
    return true;
}

if (strlen($path) && is_file($root . "/" . $path)) {
    return false;
}

$_GET["__"] = $path;
chdir($root);
require($root . "/index.php");
return true;
