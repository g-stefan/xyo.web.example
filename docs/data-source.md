# Data sources

XYO.Web generates the SQL for SQLite, MySQL and PostgreSQL from PHP
descriptors. A **table** class maps one table, a **query** class maps a
read-only join of tables. The objects are "active records": the fields of
the row are public properties of the object.

The example uses one SQLite connection (see `config.01.php` in
[Project layout](project-layout.md#configuration)) and lives in
`source/_site/data-source/`, namespace `DataSource`.

## Tables

`table-counter.php`:

```php
namespace DataSource;

defined("XYO_WEB") or die("Forbidden");

class TableCounter extends \XYO\Web\DataSource\Table
{
    public $id;
    public $count;

    public static function descriptor($info)
    {
        $info->name = "counter";
        $info->primaryKey = "id";
        $info->fields = array(
            "id" => array("bigint", "DEFAULT", "unsigned", "autoIncrement"),
            "count" => array("int", 0, "unsigned")
        );
    }
}
```

`table-user.php` has `id`, `counter_id` (`bigint`, default 0) and `name`
(`varchar`, no default, 255 characters).

A field is `array(type, default, modifier, extra)`:

| Position | Meaning |
|---|---|
| type | `int`, `bigint`, `varchar`, `text`, ... |
| default | value used by `insert()` for a field that is not set; `"DEFAULT"` means let the database choose (the field is left out of the `INSERT`); `null` no default |
| modifier | `unsigned` for numbers, the length for `varchar` |
| extra | `autoIncrement` for the primary key |

`$info->indexes = array("counter_id")` would add an index per listed field.

Declare the fields as public properties too (`public $id;`): the class
works without them, but they document the row and help editors.

Get an object with `$this->getDataSource(TableCounter::class)` from any
module (page, component, service) or authorization class. Each call returns a
new object on the shared connection.

## Storage

```php
$table->createStorage();      // CREATE TABLE if it does not exist, plus indexes
$table->destroyStorage();     // DROP TABLE IF EXISTS
$table->recreateStorage();    // drop + create
$table->storageCheckTable();  // true if the table matches the descriptor
$table->storageUpdateTable(); // migrate the table to the descriptor
```

The example calls `createStorage()` in `page.php` and in the service, so the
database is created on first use.

## Empty fields and filters

Every field of a table object is either **set** (any PHP value, `null`
included) or **empty**. `clear()` and a failed `load()` make all fields
empty; `isEmpty($field)` tells which.

`load()`, `count()`, `delete()` and `update()` build their `WHERE` from the
fields that are set: each set field adds `field = value` (`AND`). A field
set to an array adds `(field = v1 OR field = v2 ...)`.

This is the most common source of bugs: **after a successful `load()` all
fields hold the loaded row**, so setting only the key and calling `load()`
again filters on the key *and* on the old values of the other fields.

```php
// Wrong: after the first iteration "count" still holds the previous row.
for ($i = 1; $i <= 10; $i++) {
    $table->id = $i;
    $table->load(0, 1);
}

// Right: clear() first.
for ($i = 1; $i <= 10; $i++) {
    $table->clear();
    $table->id = $i;
    $table->load(0, 1);
}
```

## Reading

```php
$table->clear();
$table->id = 1;
if ($table->load(0, 1)) {         // LIMIT 0,1
    echo $table->count;
}

$table->clear();
for ($table->load(); $table->loadIsValid(); $table->loadNext()) {
    echo $table->id . ": " . $table->count . "\n";
}

$table->clear();
$n = $table->count();             // SELECT COUNT(*) with the same filters
```

- `load($start = null, $length = null)` loads the first matching row into the
  object and keeps the result open for `loadNext()`; it returns `false`
  when nothing matched (all fields are then empty).
- `loadIsValid()` is true while the object holds a row.
- `load()` throws `\XYO\Web\DataSource\DataSourceException` when the SQL
  fails; `tryLoad()` returns `false` instead.
- An exception that reaches the router gives the 501 error page and is
  written to `_log/<date>.router.log`.

More conditions, order and grouping:

```php
use XYO\Web\DataSource\Order;

$table->clear();
$table->setOperator("count", ">=", 10);           // =, !=, <, >, <=, >=, like,
$table->pushOperator("or");                       // and, or, (, )
$table->setOperator("id", "between", 1, 5);       // between, not-between, is-null, is-not-null
$table->setOrder("count", Order::DESCENDENT);     // Order::ASCENDENT, Order::DESCENDENT
$table->select(array("id", "count"));             // only these columns
$table->load();
// WHERE [count] >= ? OR [id] BETWEEN ? AND ? ORDER BY [count] DESC
```

- The first `setOperator()` is joined to the field filters with `AND`.
  Put `pushOperator("and")` or `pushOperator("or")` between two conditions:
  a `setOperator()` that directly follows another one **replaces** it.
- `like` matches anywhere: the value is wrapped in `%...%` (with `%` and
  `_` escaped).
- `setFunctionAs($field, "max", "maxCount")` adds `MAX(field) AS maxCount`
  (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`); `setGroup($field, true)` groups by
  it.
- All values are passed to the database as bound parameters.

## Writing

```php
// Insert: the auto increment key is left out when empty and set after the insert.
$table->clear();
$table->count = 0;
$table->insert();
echo $table->id;

// Insert with a chosen key.
$table->clear();
$table->id = 5;
$table->count = 0;
$table->insert();

// Update the loaded row: UPDATE ... SET <set fields> WHERE id = <id>.
$table->count = 10;
$table->save();

// Atomic change of one field of the row with this key, in a transaction.
$table->clear();
$table->id = 1;
$table->atomicIncrement("count");      // count = count + 1
$table->atomicAdd("count", 5);
$table->atomicSub("count", 2);

// Update many rows: SET from the array, WHERE from the set fields.
$table->clear();
$table->counter_id = 7;
$table->update(array("name" => "Seven"));

// Delete by key (or by all set fields when the key is empty).
$table->clear();
$table->id = 5;
$table->delete();
```

`save()` decides by the primary key: set (and not the descriptor default) →
`UPDATE`, empty → `INSERT`. It never inserts a row with a chosen key: when the
row may not exist yet, use `insert()` as the example does.

Prefer `atomicIncrement()` / `atomicAdd()` to `load()` + change +
`save()` for counters: concurrent requests (several browser tabs polling the
DB AJAX component) would otherwise overwrite each other's changes.

## Queries (joins)

`query-user.php` joins every user with its counter:

```php
class QueryUser extends \XYO\Web\DataSource\Query
{
    public $id;
    public $counter_id;
    public $name;
    public $count;

    public static function descriptor($info)
    {
        // base table: alias "user", class, fields ("*" = all)
        $info->base = array("user", TableUser::class, "*");
        $info->outer = array(
            // LEFT OUTER JOIN alias "counter"
            "counter" => array(
                TableCounter::class,
                array("count" => "count"),               // field as => field of the table
                array("id", array("user", "counter_id")) // counter.id = user.counter_id
            )
        );
    }
}
```

This becomes:

```sql
SELECT [user_].[id] AS [id], [user_].[counter_id] AS [counter_id],
       [user_].[name] AS [name], [counter_].[count] AS [count]
FROM [user] AS [user_]
LEFT OUTER JOIN [counter] AS [counter_] ON [counter_].[id] = [user_].[counter_id]
```

A query object reads like a table (`clear`, field filters, `setOperator`,
`setOrder`, `load`, `loadNext`, `loadIsValid`, `count`) but cannot write.
A user whose counter does not exist is still listed, with `count` `null`.

```php
$queryUser = $this->getDataSource(QueryUser::class);
for ($queryUser->load(); $queryUser->loadIsValid(); $queryUser->loadNext()) {
    echo $queryUser->name . ": " . $queryUser->count . "\n";
}
```

## Using another database

Change the connection in the configuration; the table, query and page code
stay the same:

```php
"db" => array(
    "type" => "mysql",            // or "postgresql"
    "server" => "localhost",
    "port" => "3306",
    "database" => "example",
    "user" => "example",
    "password" => "secret",
    "prefix" => "example_"
)
```

See the XYO.Web documentation for the connection options of each driver.
