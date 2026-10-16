<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Every PHP file of source/ and test/ must pass "php -l".

require_once(__DIR__ . "/_lib/harness.php");

$root = realpath(__DIR__ . "/..");
$files = [];
foreach (["source", "test"] as $folder) {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . "/" . $folder, FilesystemIterator::SKIP_DOTS));
    foreach ($items as $item) {
        if (strtolower($item->getExtension()) === "php") {
            $files[] = $item->getPathname();
        }
    }
}
sort($files);

t_section("php -l");
check("found PHP files", count($files) > 0);
foreach ($files as $file) {
    $output = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY) . " -l " . escapeshellarg($file) . " 2>&1", $output, $code);
    check(substr($file, strlen($root) + 1), $code === 0);
}

t_done();
