# Extending the example

Recipes for growing the example into your own site. Each one lists the files
to add; no registration step is needed elsewhere, the router and the
autoloader find them by path.

## A new page

`/about` → `source/about/page.php`:

```php
<?php

namespace About;

defined("XYO_WEB") or die("Forbidden");

use \_Library\XYOWebExampleCSS;      // found by the autoloader: _library/xyo-web-example-css.php

class Page extends \XYO\Web\Page
{
    public function init($options = null)
    {
        $this->setTitle("About");
        XYOWebExampleCSS::register($this);
    }

    public function render($options = null)
    { ?>
        <div class="p-6">
            <h1 class="text-2xl font-bold">About</h1>
            <a class="underline" href="<?php echo htmlspecialchars($this->site, ENT_QUOTES); ?>example">Back to the example</a>
        </div>
    <?php }
}

return Page::class;
```

- One namespace per page folder; the class name is free, the file must
  `return` it.
- Links start with `$this->site`, so the site also works in a subfolder.
- Register `XYOWebExampleCSS` on every page that uses Tailwind classes, then
  rebuild (`fabricare make`): Tailwind adds only the classes it finds in the
  files of `output/`.
- To share the HTML shell of all pages, add a `layout.php` (a class extending
  `\XYO\Web\Layout`, see `_site/xyo/web/default/layout.php` in the XYO.Web
  release) to the folder; it applies to that folder and below.

## A page for every sub path (slug)

`source/blog/slug.php` answers `/blog/anything`, `/blog/a/b`, ... (but not
`/blog` itself, which needs `blog/page.php`). Read the requested path from
`$this->info->path` (for example `basename($this->info->path)`), and look it
up in the database; render your own "not found" content when it does not
exist.

## A new component

`source/example/component-clock.php`, class `Example\ComponentClock`:

```php
<?php

namespace Example;

defined("XYO_WEB") or die("Forbidden");

class ComponentClock extends \XYO\Web\Component
{
    public function renderAJAX($options = null)
    {
        echo htmlspecialchars(date("H:i:s"), ENT_QUOTES);
    }

    public function renderContainer($options = null)
    {
        echo "<div id=\"" . $this->id . "\">";
        $this->renderAJAX();
        echo "</div>";

        $this->view->jsSource(function () { ?>
            <script>
                setInterval(function () {
                    <?php $this->renderJSRequestGet(); ?>
                }, 1000);
            </script>
        <?php }, "load");
    }
}
```

Register it in `page.php` `init()` with `ComponentClock::register($this, "clock");`
and print it in `render()` with `$this->renderComponent("clock");`.

Checklist:

- class name ↔ file name in kebab-case, in the folder of its namespace;
- `renderContainer()` prints the wrapper with `id="<?php echo $this->id; ?>"`;
- change state only when `$this->isComponentAJAX()` (the request is for this
  component), and only in `init()` / `process()`;
- use `renderJSRequestGet()` for a read-only refresh (no CSRF token needed),
  `renderJSRequestPost()` when the request changes something;
- escape every printed value.

To refresh several components with one request use
`renderJSRequestBatchPost(array("ajax", "ajax-db"))`: the answer is JSON and
`web.js` updates every element.

## A form with validation

Start from `component-form.php`:

```php
public function process($options = null)
{
    if (!($this->isComponentAJAX() && $this->isPost())) {
        return;
    }
    $email = $this->getElementValueString("email", "", 255);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->setElementError("email", "Enter a valid e-mail address.");
        return;
    }
    // save ...
}
```

and in `renderAJAX()`:

```php
<?php if ($this->elementHasError("email")) { ?>
    <p class="text-red-600"><?php echo htmlspecialchars($this->getElementError("email"), ENT_QUOTES); ?></p>
<?php } ?>
<input name="email" value="<?php echo htmlspecialchars($this->getElementValueString("email", ""), ENT_QUOTES); ?>" />
```

Remember `parent::init($options)` in `init()`.

## A new table

`source/_site/data-source/table-note.php`, class `DataSource\TableNote`:

```php
<?php

namespace DataSource;

defined("XYO_WEB") or die("Forbidden");

class TableNote extends \XYO\Web\DataSource\Table
{
    public $id;
    public $user_id;
    public $text;

    public static function descriptor($info)
    {
        $info->name = "note";
        $info->primaryKey = "id";
        $info->fields = array(
            "id" => array("bigint", "DEFAULT", "unsigned", "autoIncrement"),
            "user_id" => array("bigint", 0, "unsigned"),
            "text" => array("varchar", "", 1024)
        );
        $info->indexes = array("user_id");
    }
}
```

Call `$this->getDataSource(TableNote::class)->createStorage()` where the site
starts (the example does it in `page.php`), then use it as described in
[Data sources](data-source.md). If the site creates rows with fixed ids on
first use, add `use InsertOnce;` to the class and follow the rules of
[concurrent requests](data-source.md#concurrent-requests-sqlite): test with
`count()`, create with `insertOnce()`, and never write while a `load()`
cursor is open.

## A new library

A library is a component that registers its assets, like
`_site/xyo/lucide-icons/lucide-icons.php`:

1. put the files in `source/_site/<vendor>/<library>/`;
2. add a class `<Vendor>\<Library>` that sets `cssLinks` / `jsLinks` in
   `init()` under its own group name, with URLs that start with `$this->site`;
3. register it from the pages (or the layout) that need it.

Scripts and stylesheets must be served by the site itself: the
Content-Security-Policy allows only `'self'` for scripts, styles, fonts and
connections.

## Access control

Add `source/<folder>/authorization.php`, a class extending
`\XYO\Web\Authorization` whose `checkAuthorization()` returns `false` to
answer 401 for that folder and below (for example when
`$this->session->get("user")` is empty). See the XYO.Web documentation.
