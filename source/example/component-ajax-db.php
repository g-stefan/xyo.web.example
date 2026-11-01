<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace Example;

defined("XYO_WEB") or die("Forbidden");

use \XYO\LucideIcons\LucideIcons;
use \DataSource\TableCounter;

class ComponentAjaxDB extends \XYO\Web\Component
{

    protected $table = null;
    protected $count = 0;

    public function init($options = null)
    {
        $this->table = $this->getDataSource(TableCounter::class);

        // Seed the counter rows 1..10. Fields that are set act as a filter, so
        // clear() before setting the id. count() tests if the row exists
        // without keeping a read cursor open (a load() would, and the writes
        // below could then fail with "database is locked" when several
        // browser tabs poll at once). insert() keeps the id, save() would
        // update a row that does not exist; insertOnce() also handles another
        // request inserting the same row first.
        for ($i = 1; $i <= 10; $i++) {
            $this->table->clear();
            $this->table->id = $i;
            if ($this->table->count() == 0) {
                $this->table->count = 0;
                $this->table->insertOnce();
            }
        }

        // Only for an AJAX request to this component, not to another one.
        if ($this->isComponentAJAX() && $this->isPost()) {
            for ($i = 1; $i <= 10; $i++) {
                $this->table->clear();
                $this->table->id = $i;
                $this->table->atomicIncrement("count");
            }
        }

        // Show row 1. $row is local, so its read cursor is closed at the end
        // of init(), and the counter stays 0 if the row was just deleted.
        $row = $this->getDataSource(TableCounter::class);
        $row->clear();
        $row->id = 1;
        if ($row->load(0, 1)) {
            $this->count = intval($row->count);
        }

        LucideIcons::register($this, "icons");
    }

    public function renderAJAX($options = null)
    { ?>

        <div
            class="min-w-[480px] bg-white mb-3 p-0 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">DB AJAX component</h3>
                <?php $this->renderComponent("icons", "database"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->count; ?></div>
                <p class="text-xs text-muted-foreground">This component is using ajax.</p>
            </div>
        </div>

    <?php }

    public function renderContainer($options = null)
    {
        echo "<div id=\"" . $this->id . "\">";
        $this->renderAJAX();
        echo "</div>";

        $this->view->jsSource(function () { ?>
            <script>

                setInterval(function () {
                    <?php $this->renderJSRequestPost(); ?>
                }, 5000);

            </script>
        <?php }, "load");
    }

}
