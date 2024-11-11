<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./_site/web.php");
require_once ("./_site/library/lucide-icons.php");
require_once ("./example/table-counter.php");
use \XYO\Library\LucideIcons;

class ComponentAjaxDB extends \XYO\Web\ComponentAJAX
{

    protected $table = null;

    public function init()
    {
        $this->table = new TableCounter("db");

        for ($i = 1; $i <= 10; $i++) {
            $this->table->id = $i;
            if (!$this->table->load(0, 1)) {
                $this->table->clear();
                $this->table->count = 0;
                $this->table->save();
            }
        }

        if ($this->isAJAX() && $this->isPost()) {
            $this->table->clear();
            for ($this->table->load(0, 10); $this->table->loadValid(); $this->table->loadNext()) {
                $this->table->count += 1;
                $this->table->save();
            }
            $this->table->clear();
            $this->table->id = 1;
            $this->table->load(0, 1);
        }

        LucideIcons::register($this, "icons");
    }

    protected function renderAJAX()
    { ?>

        <div class="min-w-[480px] bg-white mb-3 p-0 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">DB AJAX component</h3>
                <?php $this->renderComponent("icons", "database"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->table->count; ?></div>
                <p class="text-xs text-muted-foreground">This component is using ajax.</p>
            </div>
        </div>

    <?php }

    protected function renderContainer(&$options = null)
    {
        echo "<div id=\"" . $this->id . "\">";
        $this->renderAJAX();
        echo "</div>";

        $this->view->jsSource(function () { ?>
            <script>

                setInterval(function () {
                    <?php $this->renderAJAXRequestPost(); ?>
                }, 5000);

            </script>
        <?php }, "load");
    }

}
