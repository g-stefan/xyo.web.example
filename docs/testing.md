# Testing

## Run

```bash
php test/_run-all.php
```

or

```bash
fabricare test
```

Requirements: PHP 8 with `pdo_sqlite`, the XYO.Web vendor archive
(`vendor/xyo.web.v<version>.zip`, `fabricare vendor` once) and a tool
that extracts it: `7z` / `7za` on `PATH`, the Windows `tar.exe`
(`%SystemRoot%\System32\tar.exe`) or `unzip` (`7zr` alone cannot read a
`.zip`). Apache, Node.js and a built `output/` are not needed.

Sample output:

```text
OK    0001.php-lint.php                RESULT: 31 passed, 0 failed
OK    0002.page-render.php             RESULT: 30 passed, 0 failed
...
OK    0009.concurrency.php             RESULT: 9 passed, 0 failed

ALL TEST FILES PASSED (9 files in test)
```

A failed file prints its full output under the summary line. The exit code
is 0 only when every file passed.

## How the tests work

Each `test/[0-9][0-9][0-9][0-9].*.php` file runs in its own PHP process and:

1. builds a fresh site in `temp/test/<number>/site`: the XYO.Web release
   from `vendor/` plus a copy of `source/` (what `fabricare make` does,
   without Tailwind);
2. when it needs HTTP, starts the PHP built-in web server on a free port with
   `test/_lib/router.php`, which emulates the `.htaccess` rules of XYO.Web
   (rewrite to `index.php?__=<path>`, deny `config.*`, `_repository/`,
   `_log/`);
3. talks to the site with a small HTTP client that keeps the session and
   CSRF cookies, reads the SQLite database directly with PDO, or runs the
   service with `php service-example.php`.

The server log of a test is `temp/test/<number>/server.log`, the framework
log `temp/test/<number>/site/_log/`.

| File | Checks |
|---|---|
| `0001.php-lint.php` | `php -l` on every PHP file of `source/` and `test/` |
| `0002.page-render.php` | `/example` renders all components, CSP header, CSRF token and cookies, stylesheet swap, tables created; `/` default page, 404, trailing slash 301, direct `page.php` forbidden |
| `0003.ajax-components.php` | the AJAX and nested AJAX counters count only their own requests; CSRF rejects missing / wrong tokens |
| `0004.ajax-db.php` | the DB component keeps exactly rows 1..10 (also when row 1 differs or a row is deleted), increments all of them, shows row 1 |
| `0005.ajax-form.php` | the AJAX form shows the posted values, trimmed and HTML escaped; *Cancel* resets the form |
| `0006.server-component.php` | full page form POST counter, CSRF, separate sessions |
| `0007.service.php` | the CLI service seeds user 1 / counter 1, increments, lists the join; runs from any current folder; exits 1 on an error; does not run over HTTP |
| `0008.data-source.php` | the data source rules described in [data-source.md](data-source.md), `insertOnce()` |
| `0009.concurrency.php` | 8 service runs at once, then 6 browser tabs (6 servers, `_lib/worker-ajax-db.php`) polling the DB component at once, on an empty database: no request fails, no increment is lost, nothing in the router log |

## Helpers

`test/_lib/harness.php`:

| Function | |
|---|---|
| `t_section($name)` | print a section title |
| `check($label, $condition)` | pass if `$condition` is true |
| `check_eq($label, $expected, $actual)` | pass if `$expected === $actual` |
| `check_contains($label, $needle, $haystack)` / `check_not_contains(...)` | substring checks |
| `t_done()` | print `RESULT: n passed, m failed` and exit with 0 / 1 |

`test/_lib/site.php`:

| Function / class | |
|---|---|
| `site_build($name)` | build a fresh site, returns its path |
| `site_server_start($site)` | start a built-in server on the site (stopped at exit), returns its base URL; call it again for one more server on the same site (the built-in server answers one request at a time) |
| `test_run_parallel($commands, $cwd, $logPath)` | start every command (an argument array) at once, wait, return `[exitCode, output]` for each |
| `new Client($url)` | `get($path)`, `post($path, $fields)`, `status`, `body`, `header($name)`, `cookies`, `token()` (the CSRF token found in the last page) |
| `test_counters($html)` | every `Counter: N` value of a response, in order |
| `site_db($site)` | PDO on `_repository/example.sqlite` |
| `site_service($site)` | run `service-example.php`, returns `[exitCode, output]` |

## Add a test

Create `test/0009.my-feature.php`:

```php
<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// What this file checks, in one or two lines.

require_once(__DIR__ . "/_lib/site.php");

$site = site_build("0009");
$client = new Client(site_server_start($site));

t_section("GET /example");
$html = $client->get("example");
check_eq("status 200", 200, $client->status);

$token = $client->token();
$html = $client->post("example", [
    "_component" => "ajax",       // request id of the component
    "_ajax" => "1",
    "_token" => $token,
    "_tab" => "test"
]);
check_eq("AJAX counter", [1, 0], test_counters($html));

t_done();
```

`_run-all.php` picks it up by its name.
