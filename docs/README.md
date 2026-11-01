# XYO.Web Example — Documentation

`xyo.web.example` is a small, complete web site built with
[XYO.Web](https://github.com/g-stefan/xyo.web), the folder-routed PHP 8
framework. Its purpose is to be **a working reference**: every main feature
of the framework is used once, in the simplest form that still shows how the
pieces fit together, so you can read it, run it, and copy from it when you
start your own XYO.Web site.

The example page (`/example`) shows five cards, each one a component that
demonstrates a different technique:

| Card | Class | What it shows |
|---|---|---|
| Server component | `Example\ComponentServer` | a component rendered with the page, a classic HTML form POST with the CSRF token, a session counter |
| AJAX component | `Example\ComponentAjax` | a component that re-renders itself every 10 s over AJAX and contains a child component |
| Nested AJAX component | `Example\ComponentAjaxNested` | a child component with its own AJAX refresh (every 2 s), independent of its parent |
| DB AJAX component | `Example\ComponentAjaxDB` | reading and writing SQLite through the XYO.Web data source layer (`Table`, `atomicIncrement`) |
| Component Form AJAX | `Example\ComponentForm` | a form posted over AJAX with `ComponentForm`, reading and escaping the submitted values |

Next to the page, the project also has:

- a **data source** layer: two tables (`counter`, `user`) and one joined
  query (`QueryUser`) under `source/_site/data-source/`;
- a **command line service** (`source/service-example.php`) that uses the
  same tables from a cron-like script;
- **site libraries**: Lucide icons, OverlayScrollbars and a Tailwind CSS
  stylesheet, each wrapped as a component that registers its assets;
- a **build** with [fabricare](https://github.com/g-stefan/fabricare) that
  merges the XYO.Web release with the sources and compiles Tailwind CSS;
- **tests** that build the site, serve it with the PHP built-in web server
  and check every component end to end, also under concurrent requests.

## Quick start

```bash
fabricare vendor                                        # once: XYO.Web + Tailwind
fabricare make                                          # build the web root in output/
php -S 127.0.0.1:8000 -t output test/_lib/router.php    # serve it
```

Open <http://127.0.0.1:8000/example>. Then:

```bash
php output/service-example.php    # the command line service
php test/_run-all.php             # the tests
```

## How to use it

- **To learn XYO.Web**: run it, then read [components.md](components.md)
  next to `source/example/` — each card is one technique.
- **To start a new site**: copy the repository, rename the namespace
  `Example` and the route folder `example/`, keep the build and the tests,
  and follow the recipes in [extending.md](extending.md).
- **To check a framework upgrade**: change `"xyo.web"` in `fabricare.json`,
  `fabricare vendor`, `php test/_run-all.php`.

## Contents

- [Getting started](getting-started.md) — requirements, build, run the site,
  run the service, run the tests.
- [Project layout](project-layout.md) — every folder and file, how `source/`
  becomes the web root, how requests are routed.
- [The example page and its components](components.md) — a walk through the
  page and each component, line by line, with the rules they follow.
- [Data sources](data-source.md) — configuration, table and query
  descriptors, `load` / `insert` / `save` / `atomicIncrement` and their
  pitfalls, concurrent requests on SQLite.
- [Command line service](service.md) — `service-example.php` and how to
  write your own service.
- [Extending the example](extending.md) — recipes: a new page, a new
  component, a new table, a new library.
- [Testing](testing.md) — the test suite and how to add a test.

## Versions

This documentation matches `xyo.web.example` 0.23.0 built on XYO.Web
0.144.0 (see `version.json` and `fabricare.json`).

## License

Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>.
Licensed under the Apache-2.0 license. Some files (Lucide icons,
OverlayScrollbars) are under other licenses, see their headers and license
files.
