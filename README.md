# XYO.Web Example

Usage example

A small, complete web site built with [XYO.Web](https://github.com/g-stefan/xyo.web),
the folder-routed PHP framework: a page with server, AJAX, nested AJAX,
database and AJAX form components, SQLite data sources and a command line
service. Use it as a working reference and as a starting point for a new site.

```bash
fabricare vendor
fabricare make
php -S 127.0.0.1:8000 -t output test/_lib/router.php
```

Then open <http://127.0.0.1:8000/example>. Run the tests with
`php test/_run-all.php`.

## Documentation

See [docs/README.md](docs/README.md):
[getting started](docs/getting-started.md),
[project layout](docs/project-layout.md),
[components](docs/components.md),
[data sources](docs/data-source.md),
[command line service](docs/service.md),
[extending](docs/extending.md),
[testing](docs/testing.md).

## License

Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
Licensed under the [Apache-2.0](LICENSE) license.

**This is a compilation of different works.**
Some files are under other licenses and carry additional requirements and copyright information.
This can be indicated in the license file, license or copyright comment block of the source file or/and binary file help commands.

