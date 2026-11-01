---
name: xyo-web-example
description: >-
  What xyo.web.example is for and how to use, build, run, test and extend it:
  the reference site for the XYO.Web PHP framework, with the /example page
  and its five components (server component with a CSRF form, AJAX
  component, nested AJAX component, DB AJAX component on SQLite, AJAX form
  with ComponentForm), the data sources TableCounter / TableUser / QueryUser
  and the InsertOnce trait, the command line service service-example.php
  (exit codes, runs from any folder), the library components (Lucide icons,
  OverlayScrollbars, Tailwind stylesheet XYOWebExampleCSS), the fabricare
  build (vendor, make, release, test) and the PHP test suite in test/
  (including the concurrency test). Use when someone asks what the example
  does, how to run or use it, or how to start a new XYO.Web site from it;
  when working inside the xyo.web.example repository; when adding a page,
  component, table, library or service; when debugging it (401, 404, 501,
  "database is locked", counters, database rows, service exit code).
---

# xyo.web.example

## What it is

A small, complete XYO.Web site whose purpose is to be a **working
reference**: each main framework feature is used once, in its simplest
correct form, so people can run it, read it next to the docs, and copy it to
start their own site. Full documentation: `docs/README.md` (getting-started,
project-layout, components, data-source, service, extending, testing). For
the framework itself use the **xyo-web**, **xyo-web-ui** and
**xyo-web-datasource** skills.

## Using it (answer users with this)

```bash
fabricare vendor                  # once: vendor/xyo.web.v<ver>.zip + vendor/tailwindcss.zip
fabricare make                    # output/ = XYO.Web release + source/ + compiled Tailwind CSS (needs 7z, npx)
php -S 127.0.0.1:8000 -t output test/_lib/router.php   # serve output/ like Apache would
#   open http://127.0.0.1:8000/example
php output/service-example.php    # the CLI service, from any folder; exit 0 ok, 1 failed
php test/_run-all.php             # all tests (or: fabricare test); needs the vendor zip + 7z / tar.exe / unzip
php test/0004.ajax-db.php         # one test file
fabricare release                 # release/xyo.web.example.v<version>.zip + .sha512.json
```

What the page shows: *Server component* counts form submits (session);
*AJAX component* counts every 10 s, *Nested AJAX component* inside it every
2 s, each independently (session); *DB AJAX component* counts every 5 s in
SQLite, shared by all visitors; *Component Form AJAX* posts the form over
AJAX and prints the values under *Results* (*Cancel* resets it).

Typical uses: learn XYO.Web (read `docs/components.md` next to
`source/example/`); start a new site (copy, rename namespace `Example` and
folder `example/`, follow `docs/extending.md`); check a framework upgrade
(change `"xyo.web"` in `fabricare.json`, `fabricare vendor`, run the tests).

Deploy: copy `output/` to an Apache web root (`mod_rewrite`,
`AllowOverride All`); the server user must write `_repository/` and `_log/`.

The XYO.Web version is `solution.vendor["xyo.web"]` in `fabricare.json`.
The framework source is not in this repository; to read it, extract the
vendor zip into a scratch folder (`web.php` is one minified class per line).

## Map

| Path | What |
|---|---|
| `source/` | copied over the XYO.Web release into `output/` (the web root) |
| `source/config.01.php` | SQLite connection `db` → `./_repository/example.sqlite` |
| `source/example/page.php` | `/example`, class `Example\Test`: registers and renders the components |
| `source/example/component-*.php` | `ComponentServer`, `ComponentAjax`, `ComponentAjaxNested`, `ComponentAjaxDB`, `ComponentForm` |
| `source/_site/data-source/` | `TableCounter` (counter: id, count), `TableUser` (user: id, counter_id, name), `QueryUser` (user LEFT JOIN counter), trait `InsertOnce` |
| `source/_library/` | Tailwind source CSS + `_Library\XYOWebExampleCSS` (replaces the default `xyo-web-css` group) |
| `source/_site/xyo/lucide-icons/`, `overlay-scrollbars/` | asset libraries wrapped as components |
| `source/service-example.php` | CLI launcher (`chdir(__DIR__)`, `serviceRun(__FILE__)`, exit code from `http_response_code()`) + service module |
| `fabricare/*.js` | build overrides: vendor (+ .xyo.web, .tailwindcss), make (+ .tailwindcss), test; clean / release are fabricare defaults |
| `test/` | `0001`..`0009` test files, `_lib/` harness, site builder, router, HTTP client, `worker-ajax-db.php` |

Autoload: `Example\ComponentAjaxDB` → `example/component-ajax-db.php`,
`DataSource\TableCounter` → `_site/data-source/table-counter.php` (kebab-case
path, tried under `_site/` then the web root). One class / trait per file.

## Expected behaviour (what the tests assert)

| Component | Element id / request id | Counts |
|---|---|---|
| server | `server` / — | full page POST with `_token`, session `server-count` |
| ajax | `ajax` / `ajax` | its own AJAX POST every 10 s, session `ajax-count` |
| nested | `ajax.ajax-nested` / `ajax/ajax-nested` | its own AJAX POST every 2 s, session `nested-count` |
| ajax-db | `ajax-db` / `ajax-db` | AJAX POST every 5 s: `atomicIncrement` rows 1..10, shows row 1 |
| ajax-form | `ajax-form` (form `ajax-form_form`) | shows posted first-name, last-name, email, country, escaped |

`counter` must always hold exactly rows 1..10 seeded by the DB component;
the service adds user 1 / counter 1 and increments counter 1 per run.
Concurrent service runs and polling tabs must never 501 or lose an increment
(`0009.concurrency.php`).

## Rules this project follows (keep them when editing)

- Route/class files: `namespace`, then `defined("XYO_WEB") or die("Forbidden");`,
  route files end with `return ClassName::class;`.
- Register components in `init()`, render them in `render()`; change session
  or database state in `init()`/`process()` (the session is closed before render).
- Inside a component use `$this->isComponentAJAX() && $this->isPost()` to
  react to a request **for this component**. `isAJAX()` is true for any AJAX
  request, and a parent's `init()` also runs for requests to its children.
- AJAX components: `renderContainer()` prints `<div id="<?php echo $this->id; ?>">`
  + `renderAJAX()` + a `jsSource(fn, "load")` timer calling
  `renderJSRequestPost()`/`Get()`; never print raw `<script>` (CSP nonce).
- `ComponentForm` subclasses call `parent::init($options)`.
- Escape every printed value with `htmlspecialchars($v, ENT_QUOTES)`.
- Data sources: `clear()` before setting a key (set fields filter `load()`
  and `count()`); a failed `load()` empties all fields; `insert()` to create
  a row with a chosen id (`save()` with a key is an UPDATE);
  `atomicIncrement()` for counters; `pushOperator("and"|"or")` between two
  `setOperator()` calls.
- SQLite concurrency: `load()` keeps a read cursor open (`clear()` does not
  close it) and a write while it is open gives `database is locked` → 501
  when two requests write at once. Test existence with `count()`, read
  before a write with a local table object, create fixed-id rows with
  `insertOnce()` (trait `DataSource\InsertOnce`, false = another request
  created it).
- `View::htmlClasses` / `bodyClasses` add classes only, never attributes;
  attributes go in the UniqueLists `View::htmlAttributes` / `bodyAttributes`
  (one value per name, `set()` again replaces it; `set($name, true)` = bare
  attribute, `set($name)` alone renders `name="name"`, `false` = omitted,
  `get($name, $default)` reads it, `remove($name)` drops it), rendered by the
  layout's `renderHTMLAttributes()` / `renderBodyAttributes()`.
- Asset URLs start with `$this->site`; assets must be local (CSP `'self'`).
- CLI launchers inside the web root must refuse non-CLI requests
  (`PHP_SAPI !== "cli"`), `chdir()` to the web root, and return a non-zero
  exit code when `http_response_code() >= 400`; prefer launchers outside the
  web root.

## Workflow for a change

1. Edit `source/` (and `docs/` when behaviour changes).
2. Add or update a numbered test in `test/` (`site_build()`,
   `site_server_start()` (call again for more servers), `Client`,
   `test_counters()`, `site_db()`, `site_service()`, `test_run_parallel()`;
   see `docs/testing.md`).
3. `php test/_run-all.php` must print `ALL TEST FILES PASSED`, and
   `python -m reuse lint` must stay compliant (new files: an SPDX header,
   or a path covered by `REUSE.toml`; third-party files get their own
   annotation and their license text in `LICENSES/`).
4. Files use CRLF line endings: edit with the Edit tool or binary-mode
   scripts, never `sed -i`; check `git diff --stat` shows only intended lines.
5. Debug a 501 with `_log/<date>.router.log` and
   `_log/<date>.sqlite-connection.log` (tests: `temp/test/<n>/site/_log/`),
   a server problem with `temp/test/<n>/server.log`; 401 on POST = missing
   or wrong `_token` (or session cookie). `fabricare make` failing with
   `'7z' is not recognized` = 7-Zip not on `PATH`.
