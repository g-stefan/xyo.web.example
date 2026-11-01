# Getting started

## Requirements

To **run** the site:

- PHP 8 with the `pdo_sqlite` extension (the example database is SQLite);
- a web server: Apache with `mod_rewrite` and `.htaccess` enabled
  (`AllowOverride All`), or the PHP built-in web server for development.

To **build** it from the sources:

- [fabricare](https://github.com/g-stefan/fabricare) on `PATH`;
- `7z` (the full 7-Zip, not only `7zr`: the XYO.Web release is a `.zip`)
  and `curl` on `PATH`;
- Node.js with `npm` / `npx` (Tailwind CSS is compiled with
  `@tailwindcss/cli`).

## Build

```bash
fabricare vendor
```

`vendor` downloads the XYO.Web release named in `fabricare.json`
(`"xyo.web": "0.144.0"`) to `vendor/xyo.web.v0.144.0.zip`, and installs
Tailwind CSS with `npm` into `vendor/tailwindcss.zip`. Both steps are
skipped when the files already exist; to change the XYO.Web version, edit
`fabricare.json` and run `vendor` again. Set `VENDOR_SOURCE_GIT` to download
from another place than `https://github.com/g-stefan` (for example your own
Gitea), and `VENDOR_SOURCE_AUTH` to add extra `curl` arguments (for example
an authorization header). When the release is not found the build stops with
`not found - <url>`.

```bash
fabricare make
```

`make` (also the default action, `fabricare` alone) builds the site in
`output/`:

1. removes `output/` and extracts the XYO.Web release into it (`index.php`,
   `.htaccess`, `_site/xyo/web/...`, `_log/`);
2. copies `source/` over it;
3. extracts Tailwind into `temp/` and compiles
   `source/_library/xyo-web-example.css` into
   `output/_library/xyo-web-example.css`. Tailwind scans the files of
   `output/` for the classes in use, so the stylesheet contains only what
   the pages need.

`output/` is a complete web root: copy it to the server.

```bash
fabricare release
```

`release` packs `output/` into `release/xyo.web.example.v<version>.zip`
and records its SHA-512 in
`release/xyo.web.example.v<version>.sha512.json`. The version comes from
`version.json`.

## Run the site

### With Apache

Point a virtual host (or an alias) to `output/`. The `.htaccess` file from
XYO.Web rewrites every URL that is not an existing file to
`index.php?__=<path>` and denies the `config.*` files; `_repository/` and
`_log/` have their own `.htaccess` that denies everything. The site works in
a subfolder too (for example `https://host/app/example`): links are built from
`$this->site`.

The web server user must be able to write to `_repository/` (the SQLite
database `example.sqlite` is created there on the first request) and to
`_log/`.

Open `/example`. The root `/` shows the default XYO.Web welcome page, because
the example has no `page.php` in the web root.

### With the PHP built-in web server

For development, the router script used by the tests emulates the
`.htaccess` rules:

```bash
php -S 127.0.0.1:8000 -t output test/_lib/router.php
```

Then open <http://127.0.0.1:8000/example>.

The built-in server also works on `source/` alone if you extract the XYO.Web
release into a copy of it, which is what the tests do (see
[Testing](testing.md)); without `fabricare make` the Tailwind stylesheet is
missing, so the page is unstyled but fully working.

## What you should see

- **Server component** — press *Submit*: the page reloads and the counter
  goes up by one (kept in the session).
- **AJAX component** — the counter goes up by one every 10 seconds, without a
  page reload.
- **Nested AJAX component** — inside the AJAX component; goes up every
  2 seconds, independently of its parent.
- **DB AJAX component** — goes up every 5 seconds; the value is stored in the
  SQLite database, so it is shared by all visitors and survives a new session.
- **Component Form AJAX** — fill in the fields and press *Save*: the form is
  posted over AJAX and the values are shown under *Results*.

## Run the command line service

```bash
php output/service-example.php
```

The service changes to its own folder (the web root) first, so it works
from any current folder. It creates the tables if needed, makes sure user 1
and counter 1 exist, increments counter 1 and prints every user with its
counter:

```text
Id: 1
CId: 1
Name: Hello
Count: 1
```

The exit code is 0 on success and 1 when the run failed (the error is in
`output/_log/<date>.router.log`), so cron or the Windows Task Scheduler can
report it. See [Command line service](service.md).

## Run the tests

```bash
php test/_run-all.php
```

or `fabricare test`. The tests need `vendor/xyo.web.v<version>.zip` (run
`fabricare vendor` once) and a tool that extracts it (`7z`, the Windows
`tar.exe` or `unzip`); they do not need Apache, Node.js or a built `output/`.
See [Testing](testing.md).

## Troubleshooting

| Symptom | Cause / what to do |
|---|---|
| `fabricare vendor`: `not found - <url>` | no XYO.Web release with that version at `VENDOR_SOURCE_GIT`; check `fabricare.json` |
| `fabricare make`: `'7z' is not recognized` | install 7-Zip and put the folder of `7z.exe` on `PATH` (`7zr` cannot read `.zip`) |
| page without styles | Tailwind was not compiled: run `fabricare make` (a site served from a test build has no stylesheet) |
| 401 on a POST | missing or wrong `_token` field, or the session / `_token` cookie was not sent |
| 404 | no `page.php` / `api.php` / `slug.php` for that path; the example page is `/example` (`/` shows the default XYO.Web page) |
| 501 | an exception: see `_log/<date>.router.log` (and `<date>.sqlite-connection.log` for SQL errors) |
| `database is locked` in the log | a write while a `load()` cursor was open, see [concurrent requests](data-source.md#concurrent-requests-sqlite) |
| `_repository/example.sqlite` not created | the web server user cannot write to `_repository/` |
| service exit code 1 | the run failed, the 501 page was printed; see `_log/<date>.router.log` |

## Clean

```bash
fabricare clean
```

removes `output/` and `temp/`.
