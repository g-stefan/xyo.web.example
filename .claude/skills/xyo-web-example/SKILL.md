---
name: xyo-web-example
description: >-
  How to use, build, run, test and extend xyo.web.example, the reference site
  for the XYO.Web PHP framework: the /example page with its five components
  (server component with a CSRF form, AJAX component, nested AJAX component,
  DB AJAX component on SQLite, AJAX form with ComponentForm), the data
  sources TableCounter / TableUser / QueryUser, the command line service
  service-example.php, the library components (Lucide icons,
  OverlayScrollbars, Tailwind stylesheet XYOWebExampleCSS), the fabricare
  build (vendor, make, release, test) and the PHP test suite in test/. Use
  when working inside the xyo.web.example repository, when someone asks what
  the example does or how to run it, when adding a page, component, table,
  library or service to it, when debugging it (401, 404, 501, counters,
  database rows), or when using it as a template for a new XYO.Web site.
---

# xyo.web.example

A small XYO.Web site whose purpose is to be a **working reference**: each
main framework feature is used once, in its simplest correct form. Full
documentation: `docs/README.md` (getting-started, project-layout,
components, data-source, service, extending, testing). For the framework
itself use the **xyo-web**, **xyo-web-ui** and **xyo-web-datasource** skills.

## Commands

```bash
fabricare vendor                  # download vendor/xyo.web-<ver>.7z + Tailwind (archive/vendor.7z), once
fabricare make                    # output/ = XYO.Web release + source/ + compiled Tailwind CSS
fabricare release                 # release/xyo.web.example-<version>.7z + .sha512.json
php test/_run-all.php             # all tests (or: fabricare test); needs vendor archive + 7zr/7z
php test/0004.ajax-db.php         # one test file
php -S 127.0.0.1:8000 -t output test/_lib/router.php   # serve output/ like Apache would
cd output && php service-example.php                   # the CLI service, from the web root
```

The XYO.Web version is `solution.vendor["xyo.web"]` in `fabricare.json`.
The framework source is not in this repository; to read it, extract
`vendor/xyo.web-<ver>.7z` into a scratch folder (`web.php` is one minified
file per class/line).

## Map

| Path | What |
|---|---|
| `source/` | copied over the XYO.Web release into `output/` (the web root) |
| `source/config.01.php` | SQLite connection `db` → `./_repository/example.sqlite` |
| `source/example/page.php` | `/example`, class `Example\Test`: registers and renders the components |
| `source/example/component-*.php` | `ComponentServer`, `ComponentAjax`, `ComponentAjaxNested`, `ComponentAjaxDB`, `ComponentForm` |
| `source/_site/data-source/` | `TableCounter` (counter: id, count), `TableUser` (user: id, counter_id, name), `QueryUser` (user LEFT JOIN counter) |
| `source/_library/` | Tailwind source CSS + `_Library\XYOWebExampleCSS` (replaces the default `xyo-web-css` group) |
| `source/_site/xyo/lucide-icons/`, `overlay-scrollbars/` | asset libraries wrapped as components |
| `source/service-example.php` | CLI launcher + service module (`serviceRun(__FILE__)`) |
| `fabricare/*.js` | build overrides: vendor, make, make.tailwind, release, test |
| `test/` | `0001`..`0008` test files, `_lib/` harness, site builder, router, HTTP client |

Autoload: `Example\ComponentAjaxDB` → `example/component-ajax-db.php`,
`DataSource\TableCounter` → `_site/data-source/table-counter.php` (kebab-case
path, tried under `_site/` then the web root). One class per file.

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
- Data sources: `clear()` before setting a key (set fields filter `load()`);
  a failed `load()` empties all fields; `insert()` to create a row with a
  chosen id (`save()` with a key is an UPDATE); `atomicIncrement()` for
  counters; `pushOperator("and"|"or")` between two `setOperator()` calls.
- Asset URLs start with `$this->site`; assets must be local (CSP `'self'`).
- CLI launchers inside the web root must refuse non-CLI requests
  (`PHP_SAPI !== "cli"`); prefer launchers outside the web root.

## Workflow for a change

1. Edit `source/` (and `docs/` when behaviour changes).
2. Add or update a numbered test in `test/` (`site_build()`,
   `site_server_start()`, `Client`, `test_counters()`, `site_db()`,
   `site_service()`; see `docs/testing.md`).
3. `php test/_run-all.php` must print `ALL TEST FILES PASSED`, and
   `python -m reuse lint` must stay compliant (new files: an SPDX header,
   or a path covered by `REUSE.toml`; third-party files get their own
   annotation and their license text in `LICENSES/`).
4. Files use CRLF line endings: edit with the Edit tool or binary-mode
   scripts, never `sed -i`; check `git diff --stat` shows only intended lines.
5. Debug a 501 with `_log/<date>.router.log` (tests: `temp/test/<n>/site/_log/`),
   a server problem with `temp/test/<n>/server.log`; 401 on POST = missing
   or wrong `_token` (or session cookie).
