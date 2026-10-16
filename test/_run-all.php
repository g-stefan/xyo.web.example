<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Runs every "[index].[name].php" test file of this folder.
//   php test/_run-all.php
//
// Needs the xyo.web vendor archive (run "fabricare vendor" once) and 7zr / 7z
// on PATH. Each test builds its own copy of the site in temp/test/<name>.

require(__DIR__ . "/_lib/run-folder.php");

exit(run_folder(__DIR__));
