# Command line service

A **service** is a module run from the command line, for example by cron or
the Windows Task Scheduler: maintenance, imports, sending queued e-mail. It
has the same configuration, data sources and classes as the site.

## Run it

From the web root (`output/` after a build):

```bash
php service-example.php
```

Output on the first run:

```text
Id: 1
CId: 1
Name: Hello
Count: 1
```

Each run increments counter 1, the same counter shown by the DB AJAX
component (rows 1..10, see [components.md](components.md#db-ajax-component--component-ajax-dbphp)).

## How service-example.php works

The file is both the launcher and the service:

```php
if (!defined("XYO_WEB")) {
    // This file is inside the web root: run the service only from the
    // command line, never for an HTTP request to service-example.php.
    if (PHP_SAPI !== "cli") {
        http_response_code(403);
        die("Forbidden");
    }
    require_once("./_site/xyo/web/web.php");
    defined("XYO_WEB") or die("Forbidden");
    \XYO\Web\Main\serviceRun(__FILE__);
    exit(0);
}

defined("XYO_WEB_SERVICE") or die("Forbidden");

class ServiceExample extends \XYO\Web\Module
{
    public function process($options = null)
    {
        ...
    }
}

return ServiceExample::class;
```

1. Started by `php`, `XYO_WEB` is not defined yet: the first block loads the
   framework and calls `serviceRun(__FILE__)`.
2. `serviceRun()` defines `XYO_WEB_SERVICE`, fakes a GET request
   (`$_SERVER`, `$_GET`) and runs the normal router, which loads this same
   file again as the route file.
3. The second time `XYO_WEB` is defined, so the file only declares the class
   and returns its name. The router creates it and calls `init()`,
   `process()` and `render()`, like for an API route.

`require_once("./_site/xyo/web/web.php")` is relative to the current
folder, hence "run it from the web root".

### Why the PHP_SAPI check

`service-example.php` is copied into the web root with the rest of
`source/`, and the `.htaccess` rules serve existing `.php` files directly. Without the
check, a request to `https://host/service-example.php` would run the
service with the web server's rights: change the database and print the
user list to anyone. `PHP_SAPI !== "cli"` stops it before the framework is
loaded. The second guard, `defined("XYO_WEB_SERVICE") or die(...)`, stops a
web request that reaches the file through the router.

The safest layout is to keep launchers **outside** the web root (see below)
and leave only the service module inside it.

## What the example service does

```php
$tableCounter = $this->getDataSource(TableCounter::class);
$tableCounter->createStorage();
$tableUser = $this->getDataSource(TableUser::class);
$tableUser->createStorage();

// user 1 / counter 1 are created with these ids when missing
$tableUser->clear();
$tableUser->id = 1;
if (!$tableUser->load(0, 1)) {
    $tableUser->clear();
    $tableUser->id = 1;
    $tableUser->counter_id = 1;
    $tableUser->name = "Hello";
    $tableUser->insert();
}

$tableCounter->clear();
$tableCounter->id = 1;
if (!$tableCounter->load(0, 1)) {
    $tableCounter->clear();
    $tableCounter->id = 1;
    $tableCounter->count = 1;
    $tableCounter->insert();
} else {
    $tableCounter->atomicIncrement("count");
}

// list every user with its counter (LEFT OUTER JOIN)
$queryUser = $this->getDataSource(QueryUser::class);
for ($queryUser->load(); $queryUser->loadIsValid(); $queryUser->loadNext()) {
    echo "Id: " . $queryUser->id . "\r\n";
    ...
}
```

A failed `load()` empties every field, so the key is set again before
`insert()`. See [Data sources](data-source.md).

## Writing your own service

Two ways, both outside the web root for the launcher:

**A route folder with `service.php`** (found by the router only in service
mode, never for a web request):

```text
output/tasks/cleanup/service.php   ← class Cleanup extends \XYO\Web\Module, return Cleanup::class;
cron/cleanup.php                   ← outside the web root
```

```php
// cron/cleanup.php
chdir(__DIR__ . "/../output");
require_once("./_site/xyo/web/web.php");
\XYO\Web\Main\service("tasks/cleanup");
```

**A service file run by name** (the pattern of `service-example.php`):

```php
// cron/report.php
chdir(__DIR__ . "/../output");
require_once("./_site/xyo/web/web.php");
\XYO\Web\Main\serviceRun(__DIR__ . "/report-service.php");
```

where `report-service.php` starts with
`defined("XYO_WEB_SERVICE") or die("Forbidden");` and returns its module
class.

Notes:

- `chdir()` to the web root first when the configuration or the code uses
  relative paths (as `config.01.php` does for the SQLite file).
- `service()` replaces `$_SERVER` and `$_GET`; read command line arguments
  from `$GLOBALS["argv"]` or environment variables.
- A service runs the normal firewall for a GET request: a session is
  started, but there is no CSRF check and no HTTP output that matters.
- Exceptions are logged to `_log/<date>.router.log`, as for the site.
