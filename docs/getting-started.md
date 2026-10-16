# Getting started

## Requirements

To **run** the site:

- PHP 8 with the `pdo_sqlite` extension (the example database is SQLite);
- a web server: Apache with `mod_rewrite` and `.htaccess` enabled
  (`AllowOverride All`), or the PHP built-in web server for development.

To **build** it from the sources:

- [fabricare](https://github.com/g-stefan/fabricare) on `PATH`;
- `7zr` or `7z` (7-Zip) and `curl`;
- Node.js with `npm` / `npx` (Tailwind CSS is compiled with
  `@tailwindcss/cli`).

## Build

```bash
fabricare vendor
```

`vendor` downloads the XYO.Web release named in `fabricare.json`
(`"xyo.web": "0.135.0"`) to `vendor/xyo.web-0.135.0.7z`, and installs
Tailwind CSS into `archive/vendor.7z`. Both steps are skipped when the files
already exist. Set `VENDOR_SOURCE_GIT` to download from another place than
`https://github.com/g-stefan`, and `VENDOR_SOURCE_AUTH` to add extra `curl`
arguments (for example an authorization header).

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

`release` packs `output/` into `release/xyo.web.example-<version>.7z` and
records its SHA-512 in `release/xyo.web.example-<version>.sha512.json`.

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

From the web root (the service uses paths relative to the current folder):

```bash
cd output
php service-example.php
```

It creates the tables if needed, makes sure user 1 and counter 1 exist,
increments counter 1 and prints every user with its counter:

```text
Id: 1
CId: 1
Name: Hello
Count: 1
```

See [Command line service](service.md).

## Run the tests

```bash
php test/_run-all.php
```

or `fabricare test`. The tests need `vendor/xyo.web-<version>.7z` (run
`fabricare vendor` once) and `7zr` / `7z` on `PATH`; they do not need Apache,
Node.js or a built `output/`. See [Testing](testing.md).

## Clean

```bash
fabricare clean
```

removes `output/` and `temp/`.
