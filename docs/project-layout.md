# Project layout

```text
xyo.web.example/
├── fabricare.json            build description, XYO.Web version to use
├── version.json              version of this project
├── REUSE.toml   LICENSES/    license of every file (python -m reuse lint)
├── fabricare/                build scripts (override the fabricare defaults)
│   ├── vendor.js             → vendor.xyo.web.js + vendor.tailwind.js
│   ├── vendor.xyo.web.js     downloads vendor/xyo.web-<version>.7z
│   ├── vendor.tailwind.js    npm install of Tailwind into archive/vendor.7z
│   ├── make.js               output/ = XYO.Web release + source/, then Tailwind
│   ├── make.tailwind.js      compiles _library/xyo-web-example.css
│   ├── release.js            packs output/ into release/
│   └── test.js               runs php test/_run-all.php
├── source/                   the site (copied over the XYO.Web release)
│   ├── config.01.php         configuration: the SQLite connection
│   ├── service-example.php   command line service
│   ├── example/              the /example route
│   │   ├── page.php          the page (class Example\Test)
│   │   ├── component-server.php
│   │   ├── component-ajax.php
│   │   ├── component-ajax-nested.php
│   │   ├── component-ajax-db.php
│   │   └── component-form.php
│   ├── _library/             site assets
│   │   ├── xyo-web-example.css      Tailwind source (@import "tailwindcss")
│   │   └── xyo-web-example-css.php  component that links the compiled CSS
│   ├── _repository/          data folder (SQLite database), denied to the web
│   └── _site/
│       ├── data-source/      tables and queries
│       │   ├── table-counter.php
│       │   ├── table-user.php
│       │   └── query-user.php
│       └── xyo/              third party libraries wrapped as components
│           ├── lucide-icons/
│           └── overlay-scrollbars/
├── test/                     tests (see testing.md)
├── docs/                     this documentation
├── .claude/skills/           Claude Code skill for this project
├── vendor/   archive/        downloaded dependencies (not committed)
└── output/   temp/  release/ build results (not committed)
```

The empty `index.html` files in the folders stop directory listing on servers
where it is enabled.

## source/ is the web root

`fabricare make` extracts the XYO.Web release into `output/` and copies
`source/` over it. The release provides:

| File | Purpose |
|---|---|
| `index.php` | entry point: loads `_site/xyo/web/web.php` and runs the router |
| `.htaccess` | rewrites every URL that is not a file to `index.php?__=<path>`, denies `config.*` |
| `_site/xyo/web/web.php` | the whole framework in one file |
| `_site/xyo/web/web.js` | the browser side (`XYO.Web.Component.AJAX.*`, ...) |
| `_site/xyo/web/default/` | default page, default layout, `http-301/400/401/404/500/501.php` |
| `_site/xyo/web/library/` | `XYOWebCSS` (default stylesheet) and `XYOWebLogo` |
| `_site/xyo/web/data-source/` | the SQLite, MySQL and PostgreSQL drivers |
| `_log/` | log files, denied to the web |

`XYO_WEB_PATH` is the web root (the folder of `index.php`) and everything
below is found from there.

## Routing: the folder tree is the router

A URL path is a folder of the web root:

| URL | File |
|---|---|
| `/example` | `example/page.php` |
| `/` | `page.php`, here missing, so the default XYO.Web page |
| `/example/` | redirected (301) to `/example` |
| `/anything-else` | `http-404.php` (the default one) |

The router looks, in this order, for `page.php` in the folder, `api.php` in
the folder, `slug.php` in the nearest parent folder, and, only in command line
service mode, `service.php`. `layout.php` and `authorization.php` are searched
from the folder up to the root; the example has neither, so the default
layout and no access rule are used.

A route file returns its class name, the router creates the object and runs
`init()`, `initComponents()`, `process()` and `render()`. See
[components.md](components.md).

## Autoloading

XYO.Web registers an autoloader that turns a class name into a kebab-case
path and loads it from `_site/` or from the web root:

| Class | File |
|---|---|
| `Example\ComponentAjaxDB` | `example/component-ajax-db.php` |
| `DataSource\TableCounter` | `_site/data-source/table-counter.php` |
| `XYO\LucideIcons\LucideIcons` | `_site/xyo/lucide-icons/lucide-icons.php` |
| `_Library\XYOWebExampleCSS` | `_library/xyo-web-example-css.php` |

So a component needs no `require`: name the class after its file and put it
in the folder that matches its namespace. A file is loaded by the autoloader
only when its class is used, which is why each class lives in its own file.

## Configuration

`config.*.php` files of the web root are loaded in sorted order (if there is
none, `config.php`); each returns an array that is merged into the
configuration. `config.01.php` declares the database connection:

```php
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
```

- `db` is the connection name. `getDataSource($class)` uses the connection
  named `db`; `getDataSource($class, "other")` uses another one. The
  connection is opened on first use.
- `type` can be `sqlite`, `mysql` or `postgresql`.
- The path is relative to the current folder, which is the web root both for
  web requests (the folder of `index.php`) and for the service (run it from
  the web root). Use `__DIR__ . "/_repository/example.sqlite"` if you need it
  to work from anywhere.
- `prefix` is added to every table name.

Split the configuration by concern, for example `config.01.php` for the
database and `config.02.php` for local secrets that are not committed.

## Security defaults you get from XYO.Web

- Every route file starts with `defined("XYO_WEB") or die("Forbidden");`, so
  calling `example/page.php` directly prints `Forbidden`.
- Each page response has a Content-Security-Policy with a nonce: inline
  `<script>` and `<style>` must be registered through `$this->view` (which
  adds the nonce), inline `style=""` and `onclick=""` attributes are blocked.
- Every POST (form or AJAX) must carry the CSRF token, checked against the
  session and the `_token` cookie; a missing or wrong token gives 401.
- `_repository/`, `_log/` and `config.*` are denied by `.htaccess`.
