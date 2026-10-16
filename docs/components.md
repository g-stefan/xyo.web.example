# The example page and its components

`source/example/page.php` answers `/example`. This chapter follows a request
through it and then explains each component.

## The page

```php
namespace Example;

defined("XYO_WEB") or die("Forbidden");

class Test extends \XYO\Web\Page
{
    public function init($options = null)
    {
        $this->setTitle("Web");

        XYOWebExampleCSS::register($this);                 // site stylesheet
        LucideIcons::register($this, "icons");             // icon font
        XYOWebLogo::register($this, "logo");
        OverlayScrollbars::register($this, "overlayscrollbars");
        ComponentServer::register($this, "server");
        ComponentAjax::register($this, "ajax");
        ComponentAjaxDB::register($this, "ajax-db");
        ComponentForm::register($this, "ajax-form");

        $this->getDataSource(TableCounter::class)->createStorage();
        $this->getDataSource(TableUser::class)->createStorage();
    }

    public function render($options = null)
    { ?>
        ...
        <?php $this->renderComponent("server"); ?>
        <?php $this->renderComponent("ajax"); ?>
        <?php $this->renderComponent("ajax-db"); ?>
        <?php $this->renderComponent("ajax-form"); ?>
        ...
    <?php }
}

return Test::class;
```

The rules every page follows:

- one namespace per route folder (`Example`), so class names do not clash
  with other pages;
- `defined("XYO_WEB") or die("Forbidden");` right after the namespace;
- the file **returns the class name**, the router creates the object;
- components are **registered in `init()`** with an id, and **rendered in
  `render()`** by that id;
- `createStorage()` creates the tables when they do not exist (it is cheap
  when they do), so the site needs no install step.

## Request lifecycle

### A normal page request (GET or form POST)

1. The layout is found (`layout.php` up the folder tree, else the XYO.Web
   default layout) and the page file is loaded.
2. `layout->init()`, then the layout components are initialized.
3. `page->init()` registers the components, then `page->initComponents()`
   calls, for each registered component, `init()`, its own
   `initComponents()` (recursively) and `process()`.
4. `page->process()`, `layout->process()`.
5. The session is closed (`session_write_close()`): **write session values in
   `init()` / `process()`, never while rendering.**
6. `layout->renderLayout($page)` prints `<html>`, the head (meta, title,
   CSS links with the CSP nonce), `page->render()` and, at the end of the
   body, the JS links and the collected inline scripts.

### An AJAX request for one component

The browser posts to the same URL with `_ajax=1`, `_component=<request id>`,
`_token` and `_tab`. Then:

1. `page->init()` runs (it only registers the components);
2. only the components **on the path** to the requested one are initialized:
   for `ajax/ajax-nested` that is `ajax` (its `init()`) and then
   `ajax-nested` with all its children;
3. `page->process()`, the session is closed;
4. only the requested component is rendered and sent back. The browser
   replaces the content of the element whose `id` is the component id.

Two consequences matter when writing components:

- A parent's `init()` also runs for an AJAX request addressed to one of its
  children. Use `$this->isComponentAJAX()` (true only when the request is for
  *this* component), not `$this->isAJAX()` (true for *any* AJAX request), to
  decide whether a request is "for me".
- Inline scripts registered with `$this->view->jsSource()` are printed only
  with a full page; an AJAX response does not re-register them, so a
  `setInterval()` started on page load is not duplicated by refreshes.

### Component ids

| Registered as | `$this->id` (HTML element id) | request id (`_component`) |
|---|---|---|
| `ajax` on the page | `ajax` | `ajax` |
| `ajax-nested` on `ajax` | `ajax.ajax-nested` | `ajax/ajax-nested` |
| `ajax-form` on the page | `ajax-form` | `ajax-form` |

`renderJSRequestPost()` and the other `renderJSRequest*()` methods print the
JavaScript call with the right ids and the CSRF token, so you never write them
by hand.

## Server component — `component-server.php`

A component rendered with the page every time; it overrides `render()`.

```php
public function init($options = null)
{
    if ($this->isPost()) {
        $this->session->set("server-count", $this->session->get("server-count", 0) + 1);
    }
    $this->count = $this->session->get("server-count", 0);
    LucideIcons::register($this, "icons");
}
```

`render()` prints the card and a plain HTML form:

```php
<form name="example" method="POST">
    <button type="submit">Submit</button>
    <?php $this->view->renderTokenAsFormInput() ?>
</form>
```

- The form posts to the page itself (no `action`), the whole page is
  rendered again.
- `renderTokenAsFormInput()` adds the hidden `_token` field. Without it the
  firewall answers 401.
- The counter is in the PHP session (`$this->session`), so each visitor has
  their own.
- A component can register its own children: here `icons`, rendered with
  `$this->renderComponent("icons", "server")` — the second argument is passed
  to `LucideIcons::render()` as the icon name.

## AJAX component — `component-ajax.php`

A component that refreshes itself. Instead of `render()` it implements two
methods, and the base class `render()` picks one:

- `renderContainer()` for the full page: the wrapper element plus the first
  content, and the script that starts the refresh;
- `renderAJAX()` for the AJAX response: only the content.

```php
public function init($options = null)
{
    if ($this->isComponentAJAX() && $this->isPost()) {
        $this->session->set("ajax-count", $this->session->get("ajax-count", 0) + 1);
    }
    $this->count = $this->session->get("ajax-count", 0);

    LucideIcons::register($this, "icons");
    ComponentAjaxNested::register($this, "ajax-nested");
}

public function renderContainer($options = null)
{
    echo "<div id=\"" . $this->id . "\">";
    $this->renderAJAX();
    echo "</div>";

    $this->view->jsSource(function () { ?>
        <script>
            setInterval(function () {
                <?php $this->renderJSRequestPost(); ?>
            }, 10000);
        </script>
    <?php }, "load");
}
```

- The wrapper `<div id="<?php echo $this->id; ?>">` is required: the AJAX
  response is written into the element with that id.
- `jsSource($fn, "load")` captures the `<script>` body and runs it on the
  window `load` event, with the CSP nonce. Do not print `<script>` tags
  directly: the Content-Security-Policy would block them.
- `renderJSRequestPost()` prints
  `XYO.Web.Component.AJAX.post("ajax", "ajax", [], "<token>");`.
- `renderAJAX()` renders the child with `$this->renderComponent("ajax-nested")`
  inside the content, so the child is part of the parent's refresh.

## Nested AJAX component — `component-ajax-nested.php`

The same pattern, registered on the AJAX component instead of the page, with
its own counter and a 2 second refresh. Its element id is
`ajax.ajax-nested`, its request id `ajax/ajax-nested`.

When the parent refreshes, the child is rendered inside the parent's content
with `renderContainer()` (the request is not for the child), so its element
is replaced but keeps its id, and the child's timer keeps working.

Because both use `isComponentAJAX()`, the parent counts only its own
requests (every 10 s) and the child only its own (every 2 s).

## DB AJAX component — `component-ajax-db.php`

The same AJAX pattern, with the counter in the database instead of the
session, so all visitors see the same value.

```php
public function init($options = null)
{
    $this->table = $this->getDataSource(TableCounter::class);

    // Seed the counter rows 1..10.
    for ($i = 1; $i <= 10; $i++) {
        $this->table->clear();
        $this->table->id = $i;
        if (!$this->table->load(0, 1)) {
            $this->table->clear();
            $this->table->id = $i;
            $this->table->count = 0;
            $this->table->insert();
        }
    }

    if ($this->isComponentAJAX() && $this->isPost()) {
        for ($i = 1; $i <= 10; $i++) {
            $this->table->clear();
            $this->table->id = $i;
            $this->table->atomicIncrement("count");
        }
    }

    $this->table->clear();
    $this->table->id = 1;
    $this->table->load(0, 1);   // the card shows row 1
    ...
}
```

- `clear()` before setting the key: every field that is set is a filter of
  `load()`.
- `insert()` (not `save()`) to create a row with a chosen id: `save()` with
  the primary key set is an `UPDATE`.
- `atomicIncrement()` runs `UPDATE counter SET count = count + 1 WHERE id = ?`
  in a transaction: two browsers polling at the same time cannot lose an
  increment, which a `load()` + `count += 1` + `save()` could.

See [Data sources](data-source.md) for the rules behind these calls.

## Component Form AJAX — `component-form.php`

A `\XYO\Web\ComponentForm` posts a form over AJAX and renders itself again
with the result.

```php
public function init($options = null)
{
    parent::init($options);     // ComponentForm::init() prepares the element/alert/error lists
    LucideIcons::register($this, "icons");
}

public function renderAJAX($options = null)
{
    $this->renderFormAJAX(function () {
        ?> ... <input id="first-name" type="text" name="first-name" /> ... <?php
        echo "First Name: " . htmlspecialchars($this->getElementValueString("first-name", ""), ENT_QUOTES) . "<br />";
        ...
    });
}
```

- `renderFormAJAX($fn)` prints `<form id="<id>_form" method="post">`, calls
  `$fn` for the fields and adds a submit handler that posts the form (with
  `_component`, `_ajax`, `_token`) and replaces the component content with
  the answer.
- `getElementValueString($name, $default)` reads the posted value, trimmed,
  or `$default`. `getElementValueNumber()` reads a number.
- **Escape everything you print** with `htmlspecialchars(..., ENT_QUOTES)`:
  posted values are user input.
- Call `parent::init($options)` when you override `init()` of a
  `ComponentForm`; the alert / error helpers (`setElementError()`,
  `hasError()`, ...) and `getElementId()` use the lists it creates.

## Library components

A component does not have to render anything: it can only register assets
on the shared `View`. The libraries of the example are written this way.

`_library/xyo-web-example-css.php`:

```php
class XYOWebExampleCSS extends \XYO\Web\Component
{
    protected static $name = "xyo-web-example-css";

    public function init($options = null)
    {
        $this->view->cssLinks->removeGroup("xyo-web-css");   // drop the default XYO.Web stylesheet
        $this->view->cssLinks->set(self::$name, $this->site . "_library/xyo-web-example.css");
    }
}
```

- `cssLinks` / `jsLinks` are grouped lists: `set($group, $url)` adds a link,
  `removeGroup($group)` removes all links of a group. Each library uses its
  own group name, so registering it twice adds it once.
- Prefix URLs with `$this->site` (the base path of the site, `/` or
  `/subfolder/`), never with a hard-coded `/`.
- The page components are initialized after the layout ones, so the page
  can remove what the default layout registered.

`_site/xyo/lucide-icons/lucide-icons.php` links the icon font stylesheet and
renders an icon: `render($icon)` prints `<i class="lucide-<icon>"></i>`.

`_site/xyo/overlay-scrollbars/overlay-scrollbars.php` links the
OverlayScrollbars CSS / JS (`defer`) and adds the `overlay-scrollbars` class
to `<body>`; `overlayscrollbars.js` applies the custom scrollbars to every
element with that class on page load.

## Sessions

`$this->session->get($key, $default)` / `set($key, $value)` use the PHP
session, shared by all tabs of a browser. `tabGet()` / `tabSet()` keep a
value per browser tab: `web.js` sends a per-tab id (`_tab`) with every AJAX
request. A full page request carries no tab id and uses the tab `_default`,
so per-tab values are meant for state that lives in AJAX requests.
